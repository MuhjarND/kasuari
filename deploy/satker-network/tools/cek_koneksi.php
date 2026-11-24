<?php
if (PHP_SAPI !== 'cli') {
  if (function_exists('http_response_code')) {
    http_response_code(403);
  }
  exit('Jalankan pemeriksaan ini dari terminal server satker.');
}
require_once __DIR__ . '/../sys/sys_transport.php';

$configured = '';
$configFile = __DIR__ . '/../sys/sys_config.php';
if (is_file($configFile)) {
  $tokens = token_get_all(file_get_contents($configFile));
  $significant = array();
  foreach ($tokens as $token) {
    if (is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
      continue;
    }
    $significant[] = $token;
  }
  for ($i = 0; $i + 2 < count($significant); $i++) {
    if (is_array($significant[$i]) && $significant[$i][0] === T_VARIABLE
        && $significant[$i][1] === '$url_api' && $significant[$i + 1] === '='
        && is_array($significant[$i + 2]) && $significant[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING) {
      $configured = substr($significant[$i + 2][1], 1, -1);
    }
  }
}
if (isset($argv[1])) {
  $configured = trim($argv[1]);
}
if ($configured === '') {
  fwrite(STDERR, "Alamat tidak terbaca. Jalankan: php tools/cek_koneksi.php https://kasuari.pta-papuabarat.go.id/\n");
  exit(1);
}
$endpoint = kasuariSatkerSyncEndpoint($configured);
$parts = parse_url($endpoint);
if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
  fwrite(STDERR, "Alamat pusat tidak valid; jangan sertakan username/password dalam URL.\n");
  exit(1);
}
echo "Pemeriksaan koneksi Kasuari Satker (tanpa membaca/mengirim data SIPP)\n";
echo 'Waktu UTC: ' . gmdate('Y-m-d H:i:s') . "\nPHP: " . PHP_VERSION . "\n";
echo 'Endpoint efektif: ' . $endpoint . "\n";
echo 'Environment endpoint: ' . (getenv('KASUARI_SYNC_ENDPOINT') ? 'aktif; mengalahkan sys_config.php' : 'tidak diatur') . "\n";
echo 'Mode IP: ' . (getenv('KASUARI_SYNC_IP_FAMILY') === 'ipv4' ? 'IPv4' : 'otomatis, fallback IPv4') . "\n";
if (strtolower($parts['host']) !== 'kasuari.pta-papuabarat.go.id') {
  echo "PERINGATAN: tujuan bukan domain Kasuari saat ini. Periksa sys_config.php dan environment endpoint.\n";
}
$addresses = gethostbynamel($parts['host']);
echo 'DNS IPv4: ' . ($addresses === false ? 'tidak ditemukan' : implode(', ', $addresses)) . "\n";
if (function_exists('curl_version')) {
  $version = curl_version();
  echo 'cURL: ' . $version['version'] . '; TLS: ' . $version['ssl_version'] . "\n";
}
$result = kasuariSatkerHttpRequest($endpoint, array('action' => 'health', 'protocol' => 2),
  array('attempts' => 2, 'timeout' => 45));
echo 'HTTP: ' . $result['status'] . '; cURL: ' . $result['errno']
  . '; IP terhubung: ' . ($result['primary_ip'] === '' ? '-' : $result['primary_ip'])
  . '; durasi percobaan terakhir: ' . round($result['total_time'], 2) . " detik\n";
$health = json_decode($result['body'], true);
if ($result['status'] === 200 && is_array($health) && isset($health['status'], $health['protocol'])
    && $health['status'] === 'ok' && (int) $health['protocol'] === 2) {
  echo "HASIL: koneksi dan protokol pusat siap. Klik Kirimkan Data untuk sinkronisasi.\n";
  exit(0);
}
if ($result['error'] !== '') {
  echo 'HASIL: ' . $result['error'] . "\n";
} elseif (is_array($health) && isset($health['issues']) && is_array($health['issues'])) {
  echo 'HASIL: pusat belum siap: ' . implode(' ', $health['issues']) . "\n";
} else {
  echo "HASIL: respons bukan health protokol 2. Periksa alamat endpoint, redirect, atau halaman proteksi hosting.\n";
}
exit(1);
