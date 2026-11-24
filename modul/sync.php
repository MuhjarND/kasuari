<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

include_once("sys/sys_config.php");
include_once("sys/sys_monitoring.php");
include_once("sys/sys_akta_cerai.php");
include_once("sys/sys_sipp.php");

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
  $requiredTables = array('perkara', 'perkara_banding', 'perkara_banding_detil', 'perkara_putusan', 'pihak', 'sync_monitoring');
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
$monitoringReady = $action === 'batch' ? true : kasuari_monitoring_ensure_schema($koneksi);

if ($action === 'health') {
  $aktaReady = kasuari_akta_schema_ready($koneksi, true);
  $sippReady = kasuari_sipp_schema($koneksi, true);
  header('Content-Type: application/json; charset=UTF-8');
  $maxPacket = kasuari_sync_max_packet($koneksi);
  $issues = kasuari_sync_schema_issues($koneksi);
  echo json_encode(array(
    'status' => empty($issues) ? 'ok' : 'error',
    'protocol' => 2,
    'akta_cerai_status' => $aktaReady ? 1 : 0,
    'sipp_portal' => $sippReady ? 1 : 0,
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

if ($action === 'status') {
  header('Content-Type: application/json; charset=UTF-8');
  if (!$monitoringReady) {
    kasuari_sync_error('Tabel monitoring sinkronisasi belum tersedia di pusat.', 500);
  }

  $statusPnId = isset($_POST['pn_id']) ? trim((string) $_POST['pn_id']) : '';
  $localCount = isset($_POST['local_perkara_count']) ? trim((string) $_POST['local_perkara_count']) : '';
  $localChangedAt = isset($_POST['local_data_changed_at']) ? trim((string) $_POST['local_data_changed_at']) : '';
  $localSignature = isset($_POST['local_signature']) ? strtolower(trim((string) $_POST['local_signature'])) : '';
  $appVersion = isset($_POST['app_version']) ? trim((string) $_POST['app_version']) : '';

  if (!preg_match('/^\d+$/', $statusPnId) || (int) $statusPnId <= 0) {
    kasuari_sync_error('Kode satker tidak valid.', 400);
  }
  if (!preg_match('/^\d+$/', $localCount)) {
    kasuari_sync_error('Jumlah perkara lokal tidak valid.', 400);
  }
  if (!preg_match('/^[a-f0-9]{64}$/', $localSignature)) {
    kasuari_sync_error('Jejak perubahan data lokal tidak valid.', 400);
  }
  if ($localChangedAt !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $localChangedAt) || strtotime($localChangedAt) === false)) {
    kasuari_sync_error('Waktu perubahan data lokal tidak valid.', 400);
  }
  $appVersion = substr($appVersion, 0, 30);
  $statusPnId = (int) $statusPnId;
  $localCount = (int) $localCount;

  $satkerCheck = mysqli_prepare($koneksi, "SELECT id FROM pengadilan_agama WHERE id=? AND aktif='Y' LIMIT 1");
  if (!$satkerCheck) {
    kasuari_sync_db_failure($koneksi, 'pemeriksaan identitas satker');
  }
  mysqli_stmt_bind_param($satkerCheck, 'i', $statusPnId);
  mysqli_stmt_execute($satkerCheck);
  mysqli_stmt_store_result($satkerCheck);
  $satkerExists = mysqli_stmt_num_rows($satkerCheck) > 0;
  mysqli_stmt_close($satkerCheck);
  if (!$satkerExists) {
    kasuari_sync_error('Satker tidak terdaftar atau tidak aktif di Kasuari Pusat.', 403);
  }

  $seenAt = date('Y-m-d H:i:s');
  $statusStatement = mysqli_prepare(
    $koneksi,
    "INSERT INTO sync_monitoring
      (pn_id, last_seen_at, local_perkara_count, local_data_changed_at, local_signature, app_version)
     VALUES (?, ?, ?, NULLIF(?, ''), ?, ?)
     ON DUPLICATE KEY UPDATE
      last_seen_at=VALUES(last_seen_at),
      local_perkara_count=VALUES(local_perkara_count),
      local_data_changed_at=VALUES(local_data_changed_at),
      local_signature=VALUES(local_signature),
      app_version=VALUES(app_version)"
  );
  if (!$statusStatement) {
    kasuari_sync_db_failure($koneksi, 'status monitoring satker');
  }
  mysqli_stmt_bind_param($statusStatement, 'isisss', $statusPnId, $seenAt, $localCount, $localChangedAt, $localSignature, $appVersion);
  if (!mysqli_stmt_execute($statusStatement)) {
    $statementError = mysqli_stmt_error($statusStatement);
    mysqli_stmt_close($statusStatement);
    error_log('Kasuari sync gagal menyimpan heartbeat: ' . $statementError);
    kasuari_sync_error('Gagal menyimpan status monitoring satker.', 500);
  }
  mysqli_stmt_close($statusStatement);

  // Bentuk baseline pertama untuk riwayat lama jika data belum berubah sejak sinkronisasi terakhir.
  $baselineStatement = mysqli_prepare(
    $koneksi,
    "UPDATE sync_monitoring
     SET last_sync_signature=local_signature
     WHERE pn_id=?
       AND last_sync_at IS NOT NULL
       AND last_sync_signature IS NULL
       AND local_perkara_count=last_sync_count
       AND (local_data_changed_at IS NULL OR local_data_changed_at<=last_sync_at)"
  );
  if ($baselineStatement) {
    mysqli_stmt_bind_param($baselineStatement, 'i', $statusPnId);
    if (!mysqli_stmt_execute($baselineStatement)) {
      error_log('Kasuari sync gagal membentuk baseline monitoring: ' . mysqli_stmt_error($baselineStatement));
    }
    mysqli_stmt_close($baselineStatement);
  }

  $monitorResult = mysqli_query(
    $koneksi,
    "SELECT last_sync_at, last_sync_count, last_sync_signature
     FROM sync_monitoring WHERE pn_id=" . $statusPnId . " LIMIT 1"
  );
  $monitorRow = $monitorResult ? mysqli_fetch_assoc($monitorResult) : array();
  $syncState = 'belum_sinkron';
  if (!empty($monitorRow['last_sync_at'])) {
    $syncState = 'sinkron';
    $signatureChanged = !empty($monitorRow['last_sync_signature'])
      ? !hash_equals($monitorRow['last_sync_signature'], $localSignature)
      : ($localChangedAt !== '' && $localChangedAt > $monitorRow['last_sync_at']);
    if ((int) $monitorRow['last_sync_count'] !== $localCount || $signatureChanged) {
      $syncState = 'perlu_sinkronisasi';
    }
  }

  echo json_encode(array(
    'status' => 'ok',
    'sync_state' => $syncState,
    'last_seen_at' => $seenAt,
    'last_sync_at' => isset($monitorRow['last_sync_at']) ? $monitorRow['last_sync_at'] : null
  ));
  exit;
}

list($pnId, $requestId) = kasuari_sync_validate_identity();

if ($action === 'sipp_begin') {
  if (!kasuari_sipp_schema($koneksi) || !kasuari_sipp_auth($koneksi, $pnId, $_POST['sipp_token'] ?? '')) kasuari_sync_error('Token atau skema SIPP tidak valid.', 403);
  mysqli_begin_transaction($koneksi);
  try {
    kasuari_sipp_lock_request($koneksi, $pnId, $requestId);
    mysqli_commit($koneksi);
  } catch (Exception $error) {
    mysqli_rollback($koneksi);
    kasuari_sync_error('Proses SIPP sudah ditutup. Gunakan pengiriman baru.', 409);
  }
  echo 'SIPP_BEGIN_OK';
  exit;
}

if ($action === 'sipp_batch') {
  if (!kasuari_sipp_schema($koneksi)) kasuari_sync_error('Skema portal SIPP belum tersedia.', 503);
  $token = isset($_POST['sipp_token']) ? $_POST['sipp_token'] : '';
  if (!kasuari_sipp_auth($koneksi, $pnId, $token)) kasuari_sync_error('Token SIPP satker tidak valid.', 403);
  $dataset = isset($_POST['dataset']) && is_string($_POST['dataset']) ? $_POST['dataset'] : '';
  $json = isset($_POST['rows_json']) && is_string($_POST['rows_json']) ? $_POST['rows_json'] : '';
  $hash = isset($_POST['rows_hash']) && is_string($_POST['rows_hash']) ? $_POST['rows_hash'] : '';
  if (strlen($json) > kasuari_sync_max_query_bytes($koneksi) || !hash_equals(hash('sha256', $json), $hash)) {
    kasuari_sync_error('Integritas atau ukuran paket SIPP tidak valid.', 400);
  }
  if (!mysqli_begin_transaction($koneksi)) kasuari_sync_db_failure($koneksi, 'portal SIPP');
  try {
    kasuari_sipp_stage($koneksi, $pnId, $requestId, $dataset, json_decode($json, true));
    if (!mysqli_commit($koneksi)) throw new RuntimeException(mysqli_error($koneksi));
  } catch (Exception $error) {
    mysqli_rollback($koneksi);
    error_log('Paket portal SIPP gagal: ' . $error->getMessage());
    kasuari_sync_error('Data modul SIPP tidak dapat diterima. Periksa versi paket dan log pusat.', 422);
  }
  echo 'SIPP_BATCH_OK';
  exit;
}

if ($action === 'akta_cerai_batch') {
  if (!kasuari_akta_schema_ready($koneksi)) {
    kasuari_sync_error('Skema akta cerai pusat belum tersedia. Jalankan database/akta_cerai.sql.', 503);
  }
  $json = isset($_POST['akta_rows']) && is_string($_POST['akta_rows']) ? $_POST['akta_rows'] : '';
  $hash = isset($_POST['akta_hash']) && is_string($_POST['akta_hash']) ? $_POST['akta_hash'] : '';
  if (strlen($json) > kasuari_sync_max_query_bytes($koneksi) || !hash_equals(hash('sha256', $json), $hash)) {
    kasuari_sync_error('Integritas atau ukuran paket status akta cerai tidak valid.', 400);
  }
  $aktaRows = json_decode($json, true);
  if (!is_array($aktaRows) || !$aktaRows || count($aktaRows) > 250 || array_keys($aktaRows) !== range(0, count($aktaRows) - 1)) {
    kasuari_sync_error('Paket status akta cerai tidak valid.', 400);
  }
  if (!mysqli_begin_transaction($koneksi)) kasuari_sync_db_failure($koneksi, 'status akta cerai');
  try {
    kasuari_akta_stage_rows($koneksi, $pnId, $requestId, $aktaRows);
    if (!mysqli_commit($koneksi)) throw new RuntimeException(mysqli_error($koneksi));
  } catch (Exception $error) {
    mysqli_rollback($koneksi);
    error_log('Kasuari paket akta cerai gagal: ' . $error->getMessage());
    kasuari_sync_error('Paket status akta cerai gagal disimpan. Periksa log pusat.', 422);
  }
  echo 'AKTA_BATCH_OK';
  exit;
}

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
  $sippIncluded = isset($_POST['sipp_included']) && (string) $_POST['sipp_included'] === '1';
  if ($sippIncluded && (!kasuari_sipp_schema($koneksi) || !kasuari_sipp_auth($koneksi, $pnId, $_POST['sipp_token'] ?? ''))) {
    kasuari_sync_error('Token atau skema portal SIPP belum siap.', 403);
  }
  if (!mysqli_begin_transaction($koneksi)) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }

  $existingStatement = mysqli_prepare(
    $koneksi,
    "SELECT jumlah_perkara, pn_id FROM sync_requests WHERE request_id=? LIMIT 1"
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
  mysqli_stmt_bind_result($existingStatement, $existingCount, $existingPnId);
  if (mysqli_stmt_fetch($existingStatement)) {
    mysqli_stmt_close($existingStatement);
    if ((int) $existingPnId !== (int) $pnId) {
      mysqli_rollback($koneksi);
      kasuari_sync_error('Identitas proses sudah dipakai satker lain.', 409);
    }
    if ($sippIncluded) {
      $receipt = kasuari_sipp_select($koneksi, 'SELECT state, manifest_hash FROM kasuari_sipp_requests WHERE pn_id=? AND request_id=?', 'is', array($pnId, $requestId));
      $manifest = isset($_POST['sipp_manifest']) && is_string($_POST['sipp_manifest']) ? json_decode($_POST['sipp_manifest'], true) : null;
      if (!$receipt || $receipt[0]['state'] !== 'completed' || !is_array($manifest) || !hash_equals((string) $receipt[0]['manifest_hash'], kasuari_sipp_manifest_hash($manifest))) {
        mysqli_rollback($koneksi);
        kasuari_sync_error('Finalisasi sebelumnya tidak memuat snapshot SIPP yang sama. Kirim ulang dengan proses baru.', 409);
      }
    }
    if (!mysqli_commit($koneksi)) {
      kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
    }
    echo 'SYNC_OK|Pengiriman data sudah tercatat sebelumnya. ' . number_format((int) $existingCount, 0, ',', '.') . ' perkara tersedia di Kasuari Pusat.';
    exit;
  }
  mysqli_stmt_close($existingStatement);

  if (!$sippIncluded && kasuari_sipp_schema($koneksi)) {
    $pendingSipp = kasuari_sipp_select($koneksi, 'SELECT state FROM kasuari_sipp_requests WHERE pn_id=? AND request_id=?', 'is', array($pnId, $requestId));
    if ($pendingSipp) {
      mysqli_rollback($koneksi);
      kasuari_sync_error('Proses ini memuat paket SIPP. Finalisasi wajib menyertakan manifest dan token SIPP.', 409);
    }
  }

  $countResult = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM perkara WHERE pn_id=" . $pnId);
  if (!$countResult) {
    kasuari_sync_db_failure($koneksi, 'penghitungan perkara');
  }
  $countRow = mysqli_fetch_assoc($countResult);
  $jumlahPerkara = isset($countRow['jumlah']) ? (int) $countRow['jumlah'] : 0;
  $tanggal = date('Y-m-d H:i:s');
  if ($sippIncluded) {
    try {
      $sippManifest = isset($_POST['sipp_manifest']) && is_string($_POST['sipp_manifest']) ? json_decode($_POST['sipp_manifest'], true) : null;
      kasuari_sipp_publish($koneksi, $pnId, $requestId, $sippManifest, $tanggal);
    } catch (Exception $error) {
      mysqli_rollback($koneksi);
      error_log('Finalisasi portal SIPP gagal: ' . $error->getMessage());
      kasuari_sync_error('Snapshot modul SIPP belum lengkap. Kirim ulang dari satker.', 422);
    }
  }
  if (isset($_POST['akta_status_included']) && (string) $_POST['akta_status_included'] === '1') {
    $aktaCountInput = isset($_POST['akta_row_count']) ? (string) $_POST['akta_row_count'] : '';
    if (!preg_match('/^[0-9]{1,9}$/D', $aktaCountInput) || !kasuari_akta_schema_ready($koneksi)) {
      mysqli_rollback($koneksi);
      kasuari_sync_error('Jumlah atau skema status akta cerai tidak valid.', 400);
    }
    try {
      kasuari_akta_publish($koneksi, $pnId, $requestId, (int) $aktaCountInput, $tanggal);
    } catch (Exception $error) {
      mysqli_rollback($koneksi);
      error_log('Kasuari finalisasi akta cerai gagal: ' . $error->getMessage());
      kasuari_sync_error('Status akta cerai belum dapat dipublikasikan. Kirim ulang data dari satker.', 422);
    }
  }
  $localCount = isset($_POST['local_perkara_count']) && preg_match('/^\d+$/', (string) $_POST['local_perkara_count'])
    ? (int) $_POST['local_perkara_count']
    : $jumlahPerkara;
  $localChangedAt = isset($_POST['local_data_changed_at']) ? trim((string) $_POST['local_data_changed_at']) : '';
  if ($localChangedAt !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $localChangedAt) || strtotime($localChangedAt) === false)) {
    $localChangedAt = '';
  }
  $localSignature = isset($_POST['local_signature']) ? strtolower(trim((string) $_POST['local_signature'])) : '';
  if (!preg_match('/^[a-f0-9]{64}$/', $localSignature)) {
    $localSignature = '';
  }
  $appVersion = isset($_POST['app_version']) ? substr(trim((string) $_POST['app_version']), 0, 30) : '';

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

  if ($monitoringReady) {
    $monitorStatement = mysqli_prepare(
      $koneksi,
      "INSERT INTO sync_monitoring
        (pn_id, last_seen_at, local_perkara_count, local_data_changed_at, local_signature,
         last_sync_at, last_sync_count, last_sync_signature, app_version)
       VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, NULLIF(?, ''), ?)
       ON DUPLICATE KEY UPDATE
        last_seen_at=VALUES(last_seen_at),
        local_perkara_count=VALUES(local_perkara_count),
        local_data_changed_at=VALUES(local_data_changed_at),
        local_signature=COALESCE(VALUES(local_signature), sync_monitoring.local_signature),
        last_sync_at=VALUES(last_sync_at),
        last_sync_count=VALUES(last_sync_count),
        last_sync_signature=COALESCE(VALUES(last_sync_signature), sync_monitoring.last_sync_signature),
        app_version=COALESCE(NULLIF(VALUES(app_version), ''), sync_monitoring.app_version)"
    );
    if (!$monitorStatement) {
      kasuari_sync_db_failure($koneksi, 'monitoring sinkronisasi');
    }
    mysqli_stmt_bind_param(
      $monitorStatement,
      'isisssiss',
      $pnId,
      $tanggal,
      $localCount,
      $localChangedAt,
      $localSignature,
      $tanggal,
      $localCount,
      $localSignature,
      $appVersion
    );
    if (!mysqli_stmt_execute($monitorStatement)) {
      $statementError = mysqli_stmt_error($monitorStatement);
      mysqli_stmt_close($monitorStatement);
      @mysqli_rollback($koneksi);
      error_log('Kasuari sync gagal menyimpan monitoring: ' . $statementError);
      kasuari_sync_error('Gagal menyimpan monitoring sinkronisasi.', 500);
    }
    mysqli_stmt_close($monitorStatement);
  }

  if (!mysqli_commit($koneksi)) {
    kasuari_sync_db_failure($koneksi, 'finalisasi sinkronisasi');
  }

  echo 'SYNC_OK|Pengiriman data berhasil. ' . number_format($jumlahPerkara, 0, ',', '.') . ' perkara tercatat di Kasuari Pusat.';
  exit;
}

kasuari_sync_error('Aksi sinkronisasi tidak dikenal.', 400);
?>
