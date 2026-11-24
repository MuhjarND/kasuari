<?php
date_default_timezone_set('Asia/Jayapura');
mysqli_report(MYSQLI_REPORT_OFF);
@set_time_limit(0);

require_once(__DIR__ . "/../sys/sys_koneksi.php");
require_once(__DIR__ . "/../sys/sys_config.php");
require_once(__DIR__ . "/../sys/sys_fungsi.php");

ini_set('display_errors', '0');
error_reporting(E_ALL);

function kasuariSyncFail($message, $statusCode = 422)
{
  if (!headers_sent()) {
    if (function_exists('http_response_code')) {
      http_response_code($statusCode);
    } else {
      $statusText = 'Unprocessable Entity';
      if ($statusCode === 500) {
        $statusText = 'Internal Server Error';
      } elseif ($statusCode === 502) {
        $statusText = 'Bad Gateway';
      }
      header('HTTP/1.1 ' . (int) $statusCode . ' ' . $statusText);
    }
  }
  echo 'SYNC_ERROR|' . $message;
  exit;
}

function kasuariSqlInt($value)
{
  $value = trim((string) $value);
  if ($value === '' || !preg_match('/^\d+$/', $value)) {
    return 'NULL';
  }
  $value = ltrim($value, '0');
  return $value === '' ? '0' : $value;
}

function kasuariSqlDate($value)
{
  $value = trim((string) $value);
  if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
    return 'NULL';
  }
  $date = substr($value, 0, 10);
  $parts = explode('-', $date);
  if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
    return 'NULL';
  }
  return "'" . $date . "'";
}

function kasuariSqlDateTime($value)
{
  $value = trim((string) $value);
  if ($value === '' || strpos($value, '0000-00-00') === 0) {
    return 'NULL';
  }
  $date = substr($value, 0, 10);
  $parts = explode('-', $date);
  if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
    return 'NULL';
  }
  $time = preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/', $value)
    ? substr($value, 11, 8)
    : '00:00:00';
  return "'" . $date . ' ' . $time . "'";
}

function kasuariSqlText($connection, $value)
{
  if ($value === null) {
    return 'NULL';
  }
  return "'" . mysqli_real_escape_string($connection, (string) $value) . "'";
}

function kasuariSqlValue($connection, $value, $type)
{
  if ($type === 'int') {
    return kasuariSqlInt($value);
  }
  if ($type === 'date') {
    return kasuariSqlDate($value);
  }
  if ($type === 'datetime') {
    return kasuariSqlDateTime($value);
  }
  return kasuariSqlText($connection, $value);
}

function kasuariRequiredValueValid($value, $type)
{
  if ($type === 'int') {
    return kasuariSqlInt($value) !== 'NULL';
  }
  if ($type === 'date') {
    return kasuariSqlDate($value) !== 'NULL';
  }
  return trim((string) $value) !== '';
}

function kasuariEncodeBatch($query)
{
  $encoding = 'raw';
  $binary = $query;
  if (!empty($GLOBALS['kasuariUseGzip']) && function_exists('gzencode')) {
    $compressed = gzencode($query, 6);
    if ($compressed !== false) {
      $encoding = 'gzip';
      $binary = $compressed;
    }
  }
  return array(
    'encoding' => $encoding,
    'payload' => base64_encode($binary),
    'hash' => hash('sha256', $query),
    'bytes' => strlen($query)
  );
}

function kasuariFlushRows(&$batches, &$rows, $prefix, $suffix)
{
  if (count($rows) === 0) {
    return;
  }
  $query = $prefix . implode(', ', $rows) . $suffix;
  $batch = kasuariEncodeBatch($query);
  $batch['rows'] = count($rows);
  $batches[] = $batch;
  $rows = array();
}

function kasuariBuildBatches($connection, $querySql, $table, $schema, $required, $maxQueryBytes, $label)
{
  $result = mysqli_query($connection, $querySql);
  if (!$result) {
    error_log('Kasuari Satker gagal membaca ' . $label . ': ' . mysqli_error($connection));
    return array('error' => 'Gagal membaca ' . $label . ' dari database SIPP lokal.');
  }

  $columns = array_keys($schema);
  $allColumns = array_merge(array('pn_id'), $columns);
  $updates = array();
  foreach ($columns as $column) {
    $updates[] = $column . '=VALUES(' . $column . ')';
  }
  $prefix = 'INSERT INTO ' . $table . ' (' . implode(', ', $allColumns) . ') VALUES ';
  $suffix = ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
  $batches = array();
  $rows = array();
  $currentBytes = strlen($prefix) + strlen($suffix);
  $rowCount = 0;
  $skipped = 0;

  while ($data = mysqli_fetch_assoc($result)) {
    $valid = true;
    foreach ($required as $requiredColumn) {
      $value = isset($data[$requiredColumn]) ? $data[$requiredColumn] : null;
      $type = isset($schema[$requiredColumn]) ? $schema[$requiredColumn] : 'text';
      if (!kasuariRequiredValueValid($value, $type)) {
        $valid = false;
        break;
      }
    }
    if (!$valid) {
      $skipped++;
      continue;
    }

    $values = array(kasuariSqlInt($GLOBALS['pn_id']));
    foreach ($schema as $column => $type) {
      $value = isset($data[$column]) ? $data[$column] : null;
      $values[] = kasuariSqlValue($connection, $value, $type);
    }
    $row = '(' . implode(', ', $values) . ')';
    $rowBytes = strlen($row) + (count($rows) > 0 ? 2 : 0);

    if (count($rows) > 0 && $currentBytes + $rowBytes > $maxQueryBytes) {
      kasuariFlushRows($batches, $rows, $prefix, $suffix);
      $currentBytes = strlen($prefix) + strlen($suffix);
      $rowBytes = strlen($row);
    }

    if ($currentBytes + $rowBytes > $maxQueryBytes) {
      mysqli_free_result($result);
      return array(
        'error' => 'Satu record ' . $label . ' berukuran ' . number_format(strlen($row), 0, ',', '.') .
          ' byte, melebihi kemampuan database pusat. Tingkatkan max_allowed_packet.'
      );
    }

    $rows[] = $row;
    $currentBytes += $rowBytes;
    $rowCount++;
  }
  mysqli_free_result($result);
  kasuariFlushRows($batches, $rows, $prefix, $suffix);

  if ($skipped > 0) {
    return array('error' => $skipped . ' record ' . $label . ' tidak memiliki identitas wajib yang valid.');
  }

  return array(
    'batches' => $batches,
    'rows' => $rowCount,
    'label' => $label
  );
}

function kasuariRemoteRequest($url, $data, $attempts)
{
  return kasuariSatkerHttpRequest($url, $data, array('attempts' => $attempts, 'timeout' => 180));
}

if (!($koneksi_sipp instanceof mysqli)) {
  kasuariSyncFail('Database SIPP lokal belum terhubung.', 500);
}
if (!function_exists('curl_init') || !function_exists('json_decode')) {
  kasuariSyncFail('Ekstensi cURL dan JSON wajib aktif pada PHP satker.', 500);
}

$pn_id = trim((string) $pn_id);
if (!preg_match('/^\d+$/', $pn_id) || (int) $pn_id <= 0) {
  kasuariSyncFail('Kode satker tidak ditemukan pada sys_config SIPP.', 500);
}

$url_api = kasuariSatkerSyncEndpoint($url_api);
$health = kasuariRemoteRequest($url_api, array('action' => 'health', 'protocol' => 2), 2);
if ($health['status'] !== 200 || $health['body'] === '') {
  kasuariSyncFail('Kasuari Pusat tidak dapat dihubungi untuk pemeriksaan awal. ' . $health['error'], 502);
}
$healthData = json_decode($health['body'], true);
if (!is_array($healthData) || !isset($healthData['protocol']) || (int) $healthData['protocol'] !== 2) {
  kasuariSyncFail('Modul sinkronisasi Kasuari Pusat belum menggunakan protokol terbaru.', 502);
}
if (!isset($healthData['status']) || $healthData['status'] !== 'ok') {
  $issues = isset($healthData['issues']) && is_array($healthData['issues']) ? $healthData['issues'] : array();
  kasuariSyncFail('Database Kasuari Pusat belum siap. ' . implode(' ', $issues), 502);
}
$kasuariUseGzip = !empty($healthData['gzip']);
if (empty($healthData['akta_cerai_status'])) {
    kasuariSyncFail('Pusat belum siap menerima status akta cerai. Perbarui Kasuari Pusat dan jalankan database/akta_cerai.sql.', 502);
}

$maxQueryBytes = isset($healthData['max_query_bytes']) ? (int) $healthData['max_query_bytes'] : 450000;
$maxQueryBytes = max(65536, min(700000, $maxQueryBytes));
$aktaPayloads = kasuariAktaBuildPayloads($koneksi_sipp, $maxQueryBytes);
if (isset($aktaPayloads['error'])) kasuariSyncFail($aktaPayloads['error']);
$syncSnapshot = kasuariLocalSyncSnapshot($koneksi_sipp);
if (isset($syncSnapshot['error'])) {
  kasuariSyncFail($syncSnapshot['error']);
}
$requestId = sha1($pn_id . '|' . microtime(true) . '|' . mt_rand());
$startedAt = microtime(true);

$sections = array();
$sections['perkara_banding'] = kasuariBuildBatches(
  $koneksi_sipp,
  "SELECT * FROM perkara_banding WHERE permohonan_banding>='2023-01-01'",
  'perkara_banding',
  array(
    'perkara_id' => 'int', 'jenis_banding' => 'text', 'alur_perkara_id' => 'int',
    'nomor_perkara_pn' => 'text',
    'putusan_pn' => 'date', 'pihak_pembanding' => 'int', 'permohonan_banding' => 'date',
    'pemohon_banding' => 'text', 'para_pihak' => 'text', 'pemberitahuan_putusan_pn' => 'date',
    'pemberitahuan_permohonan_banding' => 'date', 'penerimaan_memori_banding' => 'date',
    'penyerahan_memori_banding' => 'date', 'penerimaan_kontra_banding' => 'date',
    'penyerahan_kontra_banding' => 'date', 'pemberitahuan_inzage' => 'date',
    'pemberitahuan_inzage_pembanding' => 'date', 'pemberitahuan_inzage_terbanding' => 'date',
    'pelaksanaan_inzage' => 'date', 'pelaksanaan_inzage_pembanding' => 'date',
    'pelaksanaan_inzage_terbanding' => 'date', 'pengiriman_berkas_banding' => 'date',
    'nomor_surat_pengiriman_berkas_banding' => 'text', 'penerimaan_kembali_berkas_banding' => 'date',
    'nomor_urut_register' => 'int', 'tanggal_pendaftaran_banding' => 'date',
    'nomor_perkara_banding' => 'text', 'panitera_pembuat_akta_banding' => 'int',
    'hakim1_banding_id' => 'int', 'hakim1_banding' => 'text',
    'hakim2_banding_id' => 'int', 'hakim2_banding' => 'text',
    'hakim3_banding_id' => 'int', 'hakim3_banding' => 'text',
    'hakim4_banding_id' => 'int', 'hakim4_banding' => 'text',
    'hakim5_banding_id' => 'int', 'hakim5_banding' => 'text',
    'majelis_hakim_banding' => 'text', 'panitera_pengganti_banding_id' => 'int',
    'panitera_pengganti_banding' => 'text', 'tanggal_penetapan_sidang_pertama' => 'date',
    'tanggal_sidang_pertama' => 'date', 'putusan_banding' => 'date',
    'sumber_hukum_id' => 'int', 'status_putusan_banding_id' => 'int',
    'status_putusan_banding_text' => 'text', 'nomor_putusan_banding' => 'text',
    'amar_putusan_banding' => 'text', 'amar_putusan_banding_dok' => 'text',
    'tgl_kirim_salinan_putusan' => 'date', 'minutasi_banding' => 'date', 'tgl_minutasi' => 'date',
    'tgl_pengiriman_berkas_putusan' => 'date', 'pemberitahuan_putusan_banding' => 'date',
    'pemberitahuan_putusan_banding_pembanding' => 'date',
    'pemberitahuan_putusan_banding_terbanding' => 'date', 'tgl_pemberitahuan_putusan' => 'date',
    'catatan_banding' => 'text', 'prodeo_banding' => 'int', 'status_banding_id' => 'int',
    'status_banding_text' => 'text', 'tanggal_cabut' => 'date',
    'diedit_oleh' => 'text', 'diedit_tanggal' => 'datetime', 'diinput_oleh' => 'text',
    'diinput_tanggal' => 'datetime', 'diperbaharui_oleh' => 'text',
    'diperbaharui_tanggal' => 'datetime'
  ),
  array('perkara_id'),
  $maxQueryBytes,
  'perkara banding'
);

$sections['perkara_banding_detil'] = kasuariBuildBatches(
  $koneksi_sipp,
  "SELECT * FROM perkara_banding_detil WHERE perkara_id IN
    (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01')",
  'perkara_banding_detil',
  array(
    'id' => 'int', 'perkara_id' => 'int', 'status_pihak_id' => 'int', 'status_pihak_text' => 'text',
    'urutan_banding' => 'int', 'permohonan_banding' => 'date', 'pihak_asal' => 'int',
    'pihak_asal_text' => 'text', 'pihak_id' => 'int', 'pihak_nama' => 'text',
    'pihak_diwakili' => 'text', 'pemohon_id' => 'int', 'pemohon_nama' => 'text',
    'pemohon_pekerjaan' => 'text', 'pemohon_alamat' => 'text', 'pemohon_tanggal_surat' => 'date',
    'pemohon_nomor_surat' => 'text', 'pemohon_banding' => 'text', 'pemberitahuan_putusan_pn' => 'date',
    'pemberitahuan_permohonan_banding' => 'date', 'penerimaan_memori_banding' => 'date',
    'penyerahan_memori_banding' => 'date', 'penerimaan_kontra_banding' => 'date',
    'penyerahan_kontra_banding' => 'date', 'pemberitahuan_inzage' => 'date',
    'pelaksanaan_inzage' => 'date', 'pemberitahuan_putusan_banding' => 'date',
    'tanggal_kirim_salinan_putusan' => 'date', 'tanggal_cabut' => 'date'
  ),
  array('id', 'perkara_id'),
  $maxQueryBytes,
  'detail perkara banding'
);

$sections['perkara'] = kasuariBuildBatches(
  $koneksi_sipp,
  "SELECT perkara_id, jenis_acara, alur_perkara_id, tanggal_pendaftaran, jenis_perkara_id,
    jenis_perkara_kode, jenis_perkara_nama, jenis_perkara_text, nomor_urut_register,
    nomor_urut_perkara, nomor_perkara, nomor_indeks, tanggal_surat, nomor_surat, pihak1_text,
    pengacara_pihak1, pihak2_text, pengacara_pihak2, pihak3_text, pengacara_pihak3,
    pihak4_text, pengacara_pihak4, para_pihak, pihak_dipublikasikan, posita, petitum,
    prodeo, terdakwa_anak, tahapan_terakhir_id, tahapan_terakhir_text, proses_terakhir_id,
    proses_terakhir_text, nilai_sengketa, diedit_oleh, diedit_tanggal, diinput_oleh,
    diinput_tanggal, diperbaharui_oleh, diperbaharui_tanggal FROM perkara",
  'perkara',
  array(
    'perkara_id' => 'int', 'jenis_acara' => 'text', 'alur_perkara_id' => 'int',
    'tanggal_pendaftaran' => 'date', 'jenis_perkara_id' => 'int', 'jenis_perkara_kode' => 'text',
    'jenis_perkara_nama' => 'text', 'jenis_perkara_text' => 'text', 'nomor_urut_register' => 'int',
    'nomor_urut_perkara' => 'int', 'nomor_perkara' => 'text', 'nomor_indeks' => 'text',
    'tanggal_surat' => 'date', 'nomor_surat' => 'text', 'pihak1_text' => 'text',
    'pengacara_pihak1' => 'text', 'pihak2_text' => 'text', 'pengacara_pihak2' => 'text',
    'pihak3_text' => 'text', 'pengacara_pihak3' => 'text', 'pihak4_text' => 'text',
    'pengacara_pihak4' => 'text', 'para_pihak' => 'text', 'pihak_dipublikasikan' => 'text',
    'posita' => 'text', 'petitum' => 'text', 'prodeo' => 'int', 'terdakwa_anak' => 'int',
    'tahapan_terakhir_id' => 'int', 'tahapan_terakhir_text' => 'text',
    'proses_terakhir_id' => 'int', 'proses_terakhir_text' => 'text', 'nilai_sengketa' => 'int',
    'diedit_oleh' => 'text', 'diedit_tanggal' => 'datetime', 'diinput_oleh' => 'text',
    'diinput_tanggal' => 'datetime', 'diperbaharui_oleh' => 'text',
    'diperbaharui_tanggal' => 'datetime'
  ),
  array('perkara_id', 'nomor_perkara'),
  $maxQueryBytes,
  'perkara'
);

$sections['perkara_putusan'] = kasuariBuildBatches(
  $koneksi_sipp,
  "SELECT pp.perkara_id, pp.amar_putusan, pp.tanggal_putusan
    FROM perkara_putusan pp
    INNER JOIN (
      SELECT perkara_id, MAX(tanggal_putusan) AS tanggal_putusan
      FROM perkara_putusan
      WHERE tanggal_putusan IS NOT NULL AND tanggal_putusan <> '0000-00-00'
      GROUP BY perkara_id
    ) terbaru ON terbaru.perkara_id=pp.perkara_id AND terbaru.tanggal_putusan=pp.tanggal_putusan",
  'perkara_putusan',
  array('perkara_id' => 'int', 'tanggal_putusan' => 'date', 'amar_putusan' => 'text'),
  array('perkara_id', 'tanggal_putusan'),
  $maxQueryBytes,
  'putusan perkara'
);

$sections['pihak'] = kasuariBuildBatches(
  $koneksi_sipp,
  "SELECT * FROM pihak WHERE id IN
    (SELECT pihak_id FROM perkara_banding_detil WHERE perkara_id IN
      (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01'))
    OR id IN
    (SELECT pemohon_id FROM perkara_banding_detil WHERE perkara_id IN
      (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01'))",
  'pihak',
  array(
    'id' => 'int', 'jenis_pihak_id' => 'int', 'jenis_indentitas' => 'text',
    'nomor_indentitas' => 'text', 'nama' => 'text', 'tempat_lahir' => 'text',
    'tanggal_lahir' => 'date', 'jenis_kelamin' => 'text', 'golongan_darah' => 'text',
    'alamat' => 'text', 'rtrw' => 'text', 'kelurahan' => 'text', 'kecamatan' => 'text',
    'kabupaten_id' => 'int', 'kabupaten' => 'text', 'propinsi_id' => 'int', 'propinsi' => 'text',
    'telepon' => 'text', 'fax' => 'text', 'email' => 'text', 'agama_id' => 'int',
    'agama_nama' => 'text', 'status_kawin' => 'text', 'pekerjaan' => 'text',
    'pendidikan_id' => 'int', 'pendidikan' => 'text', 'warga_negara_id' => 'int',
    'warga_negara' => 'text', 'nama_ayah' => 'text', 'nama_ibu' => 'text',
    'keterangan' => 'text', 'foto' => 'text', 'difabel' => 'text',
    'diedit_oleh' => 'text', 'diedit_tanggal' => 'datetime', 'diinput_oleh' => 'text',
    'diinput_tanggal' => 'datetime', 'diperbaharui_oleh' => 'text',
    'diperbaharui_tanggal' => 'datetime'
  ),
  array('id'),
  $maxQueryBytes,
  'data pihak'
);

$totalBatches = 0;
$totalRows = 0;
foreach ($sections as $sectionResult) {
  if (isset($sectionResult['error'])) {
    kasuariSyncFail($sectionResult['error']);
  }
  $totalBatches += count($sectionResult['batches']);
  $totalRows += (int) $sectionResult['rows'];
}

if (PHP_SAPI === 'cli' && getenv('KASUARI_SYNC_DRY_RUN') === '1') {
  $largestBatch = 0;
  $compressedBytes = 0;
  foreach ($sections as $sectionResult) {
    foreach ($sectionResult['batches'] as $batch) {
      $largestBatch = max($largestBatch, (int) $batch['bytes']);
      $compressedBytes += strlen($batch['payload']);
    }
  }
  echo json_encode(array(
    'status' => 'dry-run',
    'pn_id' => (int) $pn_id,
    'rows' => $totalRows,
    'batches' => $totalBatches,
    'largest_query_bytes' => $largestBatch,
    'encoded_payload_bytes' => $compressedBytes,
    'max_query_bytes' => $maxQueryBytes,
    'akta_rows' => $aktaPayloads['rows'],
    'akta_batches' => count($aktaPayloads['batches'])
  ));
  exit;
}

$sentBatches = 0;
foreach ($sections as $section => $sectionResult) {
  $batchTotal = count($sectionResult['batches']);
  foreach ($sectionResult['batches'] as $batchIndex => $batch) {
    $response = kasuariRemoteRequest($url_api, array(
      'action' => 'batch',
      'protocol' => 2,
      'pn_id' => $pn_id,
      'request_id' => $requestId,
      'section' => $section,
      'batch_index' => $batchIndex,
      'batch_total' => $batchTotal,
      'query_encoding' => $batch['encoding'],
      'query_payload' => $batch['payload'],
      'query_hash' => $batch['hash'],
      'row_count' => $batch['rows']
    ), 3);

    if ($response['status'] !== 200 || strpos($response['body'], 'BATCH_OK|') !== 0) {
      $message = $response['body'] !== '' ? preg_replace('/^SYNC_ERROR\|/', '', $response['body']) : $response['error'];
      kasuariSyncFail(
        'Pengiriman ' . $sectionResult['label'] . ' batch ' . ($batchIndex + 1) . '/' . $batchTotal .
        ' gagal. ' . $message,
        502
      );
    }
    $sentBatches++;
  }
}

foreach ($aktaPayloads['batches'] as $aktaIndex => $aktaJson) {
    $aktaResponse = kasuariRemoteRequest($url_api, array(
        'action' => 'akta_cerai_batch', 'protocol' => 2, 'pn_id' => $pn_id,
        'request_id' => $requestId, 'akta_rows' => $aktaJson, 'akta_hash' => hash('sha256', $aktaJson)
    ), 3);
    if ($aktaResponse['status'] !== 200 || $aktaResponse['body'] !== 'AKTA_BATCH_OK') {
        $aktaMessage = $aktaResponse['body'] !== '' ? preg_replace('/^SYNC_ERROR\|/', '', $aktaResponse['body']) : $aktaResponse['error'];
        kasuariSyncFail('Pengiriman status akta cerai paket ' . ($aktaIndex + 1) . ' gagal. ' . $aktaMessage, 502);
    }
}

$sippSync = kasuariSippSync($koneksi_sipp, $url_api, $pn_id, $requestId, $healthData, $maxQueryBytes);
if (isset($sippSync['error'])) kasuariSyncFail($sippSync['error'], 502);

$finalPayload = array(
  'action' => 'finalize',
  'protocol' => 2,
  'pn_id' => $pn_id,
  'request_id' => $requestId,
  'batch_count' => $totalBatches,
  'row_count' => $totalRows,
  'akta_status_included' => 1,
  'akta_row_count' => $aktaPayloads['rows'],
  'local_perkara_count' => $syncSnapshot['local_perkara_count'],
  'local_data_changed_at' => $syncSnapshot['local_data_changed_at'],
  'local_signature' => $syncSnapshot['local_signature'],
  'app_version' => $kasuari_satker_version
);
if ($sippSync['included']) {
  $sippManifestJson = json_encode((object) $sippSync['manifest']);
  if ($sippManifestJson === false) kasuariSyncFail('JSON manifest SIPP tidak valid.');
  $finalPayload['sipp_included'] = 1;
  $finalPayload['sipp_manifest'] = $sippManifestJson;
  $finalPayload['sipp_token'] = $sippSync['token'];
}
$finalResponse = kasuariRemoteRequest($url_api, $finalPayload, 3);
if (isset($sippSync['token'])) {
  $finalResponse['body'] = str_replace($sippSync['token'], '[REDACTED]', $finalResponse['body']);
  $finalResponse['error'] = str_replace($sippSync['token'], '[REDACTED]', $finalResponse['error']);
  unset($sippSync['token'], $finalPayload['sipp_token']);
}

if ($finalResponse['status'] !== 200 || strpos($finalResponse['body'], 'SYNC_OK|') !== 0) {
  $message = $finalResponse['body'] !== '' ? preg_replace('/^SYNC_ERROR\|/', '', $finalResponse['body']) : $finalResponse['error'];
  kasuariSyncFail('Finalisasi sinkronisasi gagal. ' . $message, 502);
}

$elapsed = number_format(microtime(true) - $startedAt, 2, ',', '.');
echo $finalResponse['body'] . ' ' . number_format($sentBatches, 0, ',', '.') .
  ' batch (' . number_format($totalRows, 0, ',', '.') . ' baris) dan ' . number_format($aktaPayloads['rows'], 0, ',', '.') .
  ' status akta cerai diproses dalam ' . $elapsed . ' detik.';
if ($sippSync['warning'] !== '') echo ' ' . $sippSync['warning'];
?>
