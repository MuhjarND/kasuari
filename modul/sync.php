<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

include_once("sys/sys_config.php");

header('Content-Type: text/plain; charset=UTF-8');
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Jayapura');
mysqli_set_charset($koneksi, 'utf8mb4');

function kasuari_sync_error($message, $statusCode = 422)
{
  http_response_code($statusCode);
  echo 'SYNC_ERROR|' . $message;
  exit;
}

function kasuari_sync_database_message($message, $code)
{
  if (preg_match("/Data too long for column '([^']+)' at row ([0-9]+)/i", $message, $match)) {
    return 'Data kolom ' . $match[1] . ' terlalu panjang pada baris paket ' . $match[2] . '.';
  }
  if (preg_match("/Incorrect (?:integer|string) value:.*for column '([^']+)' at row ([0-9]+)/i", $message, $match)) {
    return 'Nilai kolom ' . $match[1] . ' tidak sesuai pada baris paket ' . $match[2] . '.';
  }
  if (stripos($message, 'max_allowed_packet') !== false || stripos($message, 'packet bigger') !== false) {
    return 'Ukuran query melampaui max_allowed_packet database pusat.';
  }
  if (preg_match("/Duplicate entry .* for key '([^']+)'/i", $message, $match)) {
    return 'Terdapat data ganda pada indeks ' . $match[1] . '.';
  }

  return 'Database pusat menolak paket (kode ' . (int) $code . ').';
}

function kasuari_sync_db_failure($connection, $section)
{
  $message = mysqli_error($connection);
  $code = mysqli_errno($connection);
  @mysqli_rollback($connection);
  error_log('Kasuari sync gagal pada ' . $section . ': ' . $message);
  kasuari_sync_error('Gagal menyimpan ' . $section . '. ' . kasuari_sync_database_message($message, $code));
}

function kasuari_sync_max_packet($connection)
{
  $result = mysqli_query($connection, "SELECT @@max_allowed_packet AS nilai");
  if (!$result) {
    return 1048576;
  }
  $row = mysqli_fetch_assoc($result);
  return isset($row['nilai']) ? max(131072, (int) $row['nilai']) : 1048576;
}

function kasuari_sync_ini_bytes($value)
{
  $value = trim((string) $value);
  if ($value === '' || $value === '-1') {
    return 0;
  }
  $unit = strtolower(substr($value, -1));
  $number = (float) $value;
  if ($unit === 'g') {
    $number *= 1024;
    $unit = 'm';
  }
  if ($unit === 'm') {
    $number *= 1024;
    $unit = 'k';
  }
  if ($unit === 'k') {
    $number *= 1024;
  }
  return max(0, (int) floor($number));
}

function kasuari_sync_max_query_bytes($connection)
{
  $maxBytes = (int) floor(kasuari_sync_max_packet($connection) * 0.65);
  $postMaxBytes = kasuari_sync_ini_bytes(ini_get('post_max_size'));
  if ($postMaxBytes > 0) {
    // Payload dikirim base64 sehingga membutuhkan ruang sekitar 4/3 ukuran query.
    $maxBytes = min($maxBytes, (int) floor($postMaxBytes * 0.65));
  }
  return max(65536, $maxBytes);
}

function kasuari_sync_schema_issues($connection)
{
  $issues = array();
  $requiredTables = array('perkara', 'perkara_banding', 'perkara_banding_detil', 'perkara_putusan', 'pihak');
  $escapedTables = array();
  foreach ($requiredTables as $table) {
    $escapedTables[] = "'" . mysqli_real_escape_string($connection, $table) . "'";
  }

  $tableResult = mysqli_query(
    $connection,
    "SELECT TABLE_NAME, TABLE_COLLATION, ENGINE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (" . implode(',', $escapedTables) . ")"
  );
  $foundTables = array();
  if ($tableResult) {
    while ($row = mysqli_fetch_assoc($tableResult)) {
      $foundTables[$row['TABLE_NAME']] = true;
      if (stripos((string) $row['TABLE_COLLATION'], 'utf8mb4') !== 0) {
        $issues[] = 'Tabel ' . $row['TABLE_NAME'] . ' belum menggunakan utf8mb4.';
      }
      if (strtoupper((string) $row['ENGINE']) !== 'INNODB') {
        $issues[] = 'Tabel ' . $row['TABLE_NAME'] . ' belum menggunakan InnoDB.';
      }
    }
  }
  foreach ($requiredTables as $table) {
    if (!isset($foundTables[$table])) {
      $issues[] = 'Tabel ' . $table . ' tidak ditemukan.';
    }
  }

  $longTextColumns = array(
    'perkara' => array('pihak1_text', 'pihak2_text', 'para_pihak', 'posita', 'petitum'),
    'perkara_banding' => array('pemohon_banding', 'para_pihak', 'amar_putusan_banding'),
    'perkara_putusan' => array('amar_putusan')
  );
  foreach ($longTextColumns as $table => $columns) {
    foreach ($columns as $column) {
      $sql = "SELECT DATA_TYPE FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA=DATABASE()
                AND TABLE_NAME='" . mysqli_real_escape_string($connection, $table) . "'
                AND COLUMN_NAME='" . mysqli_real_escape_string($connection, $column) . "'";
      $result = mysqli_query($connection, $sql);
      $row = $result ? mysqli_fetch_assoc($result) : false;
      if (!$row || strtolower((string) $row['DATA_TYPE']) !== 'longtext') {
        $issues[] = 'Kolom ' . $table . '.' . $column . ' belum bertipe LONGTEXT.';
      }
    }
  }

  $requiredColumns = array(
    'perkara_banding' => array(
      'hakim1_banding', 'hakim2_banding', 'hakim3_banding', 'hakim4_banding', 'hakim5_banding',
      'sumber_hukum_id', 'status_putusan_banding_id'
    ),
    'pihak' => array(
      'rtrw', 'kelurahan', 'kecamatan', 'kabupaten_id', 'kabupaten', 'propinsi_id', 'propinsi',
      'telepon', 'fax', 'email', 'agama_id'
    )
  );
  foreach ($requiredColumns as $table => $columns) {
    foreach ($columns as $column) {
      $sql = "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA=DATABASE()
                AND TABLE_NAME='" . mysqli_real_escape_string($connection, $table) . "'
                AND COLUMN_NAME='" . mysqli_real_escape_string($connection, $column) . "'";
      $result = mysqli_query($connection, $sql);
      $row = $result ? mysqli_fetch_assoc($result) : false;
      if (!$row) {
        $issues[] = 'Kolom ' . $table . '.' . $column . ' belum tersedia.';
      } elseif ($table === 'perkara_banding' && strpos($column, 'hakim') === 0 &&
                (int) $row['CHARACTER_MAXIMUM_LENGTH'] < 255) {
        $issues[] = 'Kolom ' . $table . '.' . $column . ' harus berukuran VARCHAR(255).';
      }
    }
  }

  $requestTable = mysqli_query(
    $connection,
    "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sync_requests'"
  );
  if (!$requestTable || mysqli_num_rows($requestTable) === 0) {
    $issues[] = 'Tabel sync_requests belum tersedia.';
  }

  return $issues;
}

function kasuari_sync_decode_query($payload, $encoding)
{
  $binary = base64_decode((string) $payload, true);
  if ($binary === false) {
    return false;
  }
  if ($encoding === 'gzip') {
    if (!function_exists('gzdecode')) {
      return false;
    }
    return gzdecode($binary);
  }
  if ($encoding === 'raw') {
    return $binary;
  }
  return false;
}

function kasuari_sync_validate_identity()
{
  $pnId = isset($_POST['pn_id']) ? trim((string) $_POST['pn_id']) : '';
  $requestId = isset($_POST['request_id']) ? strtolower(trim((string) $_POST['request_id'])) : '';
  if (!preg_match('/^\d+$/', $pnId) || (int) $pnId <= 0) {
    kasuari_sync_error('Kode satker tidak valid.', 400);
  }
  if (!preg_match('/^[a-f0-9]{40}$/', $requestId)) {
    kasuari_sync_error('Identitas proses sinkronisasi tidak valid.', 400);
  }
  return array((int) $pnId, $requestId);
}

$action = isset($_POST['action']) ? strtolower(trim((string) $_POST['action'])) : '';
$protocol = isset($_POST['protocol']) ? (int) $_POST['protocol'] : 0;

if ($action === 'health') {
  header('Content-Type: application/json; charset=UTF-8');
  $maxPacket = kasuari_sync_max_packet($koneksi);
  $issues = kasuari_sync_schema_issues($koneksi);
  echo json_encode(array(
    'status' => empty($issues) ? 'ok' : 'error',
    'protocol' => 2,
    'max_allowed_packet' => $maxPacket,
    'max_query_bytes' => kasuari_sync_max_query_bytes($koneksi),
    'post_max_size' => ini_get('post_max_size'),
    'post_max_bytes' => kasuari_sync_ini_bytes(ini_get('post_max_size')),
    'gzip' => function_exists('gzdecode'),
    'issues' => $issues
  ));
  exit;
}

if ($protocol !== 2) {
  kasuari_sync_error('Versi Kasuari Satker perlu diperbarui sebelum sinkronisasi.', 426);
}

list($pnId, $requestId) = kasuari_sync_validate_identity();

if ($action === 'batch') {
  $sections = array(
    'perkara_banding' => array('table' => 'perkara_banding', 'label' => 'perkara banding'),
    'perkara_banding_detil' => array('table' => 'perkara_banding_detil', 'label' => 'detail perkara banding'),
    'perkara' => array('table' => 'perkara', 'label' => 'perkara satker'),
    'perkara_putusan' => array('table' => 'perkara_putusan', 'label' => 'putusan perkara'),
    'pihak' => array('table' => 'pihak', 'label' => 'data pihak')
  );
  $section = isset($_POST['section']) ? trim((string) $_POST['section']) : '';
  if (!isset($sections[$section])) {
    kasuari_sync_error('Bagian data sinkronisasi tidak dikenal.', 400);
  }

  $batchIndex = isset($_POST['batch_index']) ? (int) $_POST['batch_index'] : -1;
  $batchTotal = isset($_POST['batch_total']) ? (int) $_POST['batch_total'] : 0;
  if ($batchIndex < 0 || $batchTotal < 1 || $batchIndex >= $batchTotal || $batchTotal > 10000) {
    kasuari_sync_error('Urutan batch tidak valid.', 400);
  }

  $encoding = isset($_POST['query_encoding']) ? trim((string) $_POST['query_encoding']) : '';
  $syncQuery = kasuari_sync_decode_query(isset($_POST['query_payload']) ? $_POST['query_payload'] : '', $encoding);
  if ($syncQuery === false || trim($syncQuery) === '') {
    kasuari_sync_error('Isi batch tidak dapat dibaca.', 400);
  }

  $queryHash = isset($_POST['query_hash']) ? strtolower(trim((string) $_POST['query_hash'])) : '';
  if (!preg_match('/^[a-f0-9]{64}$/', $queryHash) || !hash_equals($queryHash, hash('sha256', $syncQuery))) {
    kasuari_sync_error('Integritas batch tidak valid.', 400);
  }

  $maxQueryBytes = kasuari_sync_max_query_bytes($koneksi);
  if (strlen($syncQuery) > $maxQueryBytes) {
    kasuari_sync_error('Ukuran batch melampaui kemampuan database pusat.', 413);
  }

  $table = $sections[$section]['table'];
  if (!preg_match('/^INSERT\s+INTO\s+' . preg_quote($table, '/') . '\s*\(/i', ltrim($syncQuery)) ||
      stripos($syncQuery, ' ON DUPLICATE KEY UPDATE ') === false) {
    kasuari_sync_error('Format query batch tidak sesuai dengan bagian data.', 400);
  }

  if (!mysqli_begin_transaction($koneksi)) {
    kasuari_sync_db_failure($koneksi, $sections[$section]['label']);
  }
  if (!mysqli_query($koneksi, $syncQuery)) {
    kasuari_sync_db_failure($koneksi, $sections[$section]['label']);
  }
  if (!mysqli_commit($koneksi)) {
    kasuari_sync_db_failure($koneksi, $sections[$section]['label']);
  }

  echo 'BATCH_OK|' . $section . '|' . $batchIndex;
  exit;
}

if ($action === 'finalize') {
  if (!mysqli_begin_transaction($koneksi)) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }

  $existingStatement = mysqli_prepare(
    $koneksi,
    "SELECT jumlah_perkara FROM sync_requests WHERE request_id=? LIMIT 1"
  );
  if (!$existingStatement) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }
  mysqli_stmt_bind_param($existingStatement, 's', $requestId);
  if (!mysqli_stmt_execute($existingStatement)) {
    $statementError = mysqli_stmt_error($existingStatement);
    $statementCode = mysqli_stmt_errno($existingStatement);
    mysqli_stmt_close($existingStatement);
    @mysqli_rollback($koneksi);
    error_log('Kasuari sync gagal saat memeriksa finalisasi: ' . $statementError);
    kasuari_sync_error('Gagal memeriksa finalisasi sinkronisasi. ' . kasuari_sync_database_message($statementError, $statementCode));
  }
  mysqli_stmt_bind_result($existingStatement, $existingCount);
  if (mysqli_stmt_fetch($existingStatement)) {
    mysqli_stmt_close($existingStatement);
    if (!mysqli_commit($koneksi)) {
      kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
    }
    echo 'SYNC_OK|Pengiriman data sudah tercatat sebelumnya. ' . number_format((int) $existingCount, 0, ',', '.') . ' perkara tersedia di Kasuari Pusat.';
    exit;
  }
  mysqli_stmt_close($existingStatement);

  $countResult = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM perkara WHERE pn_id=" . $pnId);
  if (!$countResult) {
    kasuari_sync_db_failure($koneksi, 'penghitungan perkara');
  }
  $countRow = mysqli_fetch_assoc($countResult);
  $jumlahPerkara = isset($countRow['jumlah']) ? (int) $countRow['jumlah'] : 0;
  $tanggal = date('Y-m-d H:i:s');

  $requestStatement = mysqli_prepare(
    $koneksi,
    "INSERT INTO sync_requests (request_id, pn_id, completed_at, jumlah_perkara) VALUES (?, ?, ?, ?)"
  );
  if (!$requestStatement) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }
  mysqli_stmt_bind_param($requestStatement, 'sisi', $requestId, $pnId, $tanggal, $jumlahPerkara);
  if (!mysqli_stmt_execute($requestStatement)) {
    $statementError = mysqli_stmt_error($requestStatement);
    $statementCode = mysqli_stmt_errno($requestStatement);
    mysqli_stmt_close($requestStatement);
    @mysqli_rollback($koneksi);
    error_log('Kasuari sync gagal pada finalisasi sinkronisasi: ' . $statementError);
    kasuari_sync_error('Gagal menyimpan finalisasi sinkronisasi. ' . kasuari_sync_database_message($statementError, $statementCode));
  }
  mysqli_stmt_close($requestStatement);

  $logStatement = mysqli_prepare($koneksi, "INSERT INTO log_singkron (id_satker, tanggal, perkara) VALUES (?, ?, ?)");
  if (!$logStatement) {
    kasuari_sync_db_failure($koneksi, 'pencatatan riwayat');
  }
  mysqli_stmt_bind_param($logStatement, 'isi', $pnId, $tanggal, $jumlahPerkara);
  if (!mysqli_stmt_execute($logStatement)) {
    $statementError = mysqli_stmt_error($logStatement);
    $statementCode = mysqli_stmt_errno($logStatement);
    mysqli_stmt_close($logStatement);
    @mysqli_rollback($koneksi);
    error_log('Kasuari sync gagal pada pencatatan riwayat: ' . $statementError);
    kasuari_sync_error('Gagal menyimpan pencatatan riwayat. ' . kasuari_sync_database_message($statementError, $statementCode));
  }
  mysqli_stmt_close($logStatement);

  if (!mysqli_commit($koneksi)) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }

  echo 'SYNC_OK|Pengiriman data berhasil. ' . number_format($jumlahPerkara, 0, ',', '.') . ' perkara tercatat di Kasuari Pusat.';
  exit;
}

kasuari_sync_error('Aksi sinkronisasi tidak dikenal.', 400);
?>
