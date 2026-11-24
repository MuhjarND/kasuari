<?php
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit;
}
require_once __DIR__ . '/../../deploy/satker-network/sys/sys_transport.php';
require_once __DIR__ . '/../../deploy/satker-network/sys/sys_fungsi.php';
function network_assert($test, $label)
{
  if (!$test) {
    throw new RuntimeException($label);
  }
  echo 'PASS: ' . $label . "\n";
}
$oldEndpoint = getenv('KASUARI_SYNC_ENDPOINT');
$oldFamily = getenv('KASUARI_SYNC_IP_FAMILY');
$oldState = getenv('KASUARI_NETWORK_TEST_STATE');
$server = null;
$pipes = array();
$state = tempnam(sys_get_temp_dir(), 'kasuari-net-');
try {
  putenv('KASUARI_SYNC_ENDPOINT');
  putenv('KASUARI_SYNC_IP_FAMILY');
  network_assert(kasuariSatkerSyncEndpoint('https://example.test/') === 'https://example.test/sync', 'Endpoint root dinormalisasi.');
  network_assert(kasuariSatkerSyncEndpoint('https://example.test/kasuari/sync/') === 'https://example.test/kasuari/sync', 'Subfolder dan endpoint sync tidak digandakan.');
  putenv('KASUARI_SYNC_ENDPOINT=https://override.test/sync');
  network_assert(kasuariSatkerSyncEndpoint('https://example.test/') === 'https://override.test/sync', 'Override endpoint tetap konsisten.');
  putenv('KASUARI_SYNC_ENDPOINT');
  foreach (array(6, 7, 28) as $errno) {
    network_assert(kasuariSatkerCanRetryIpv4(array('errno' => $errno, 'status' => 0, 'connect_time' => 0)), 'Kegagalan koneksi ' . $errno . ' mengizinkan fallback IPv4.');
  }
  network_assert(!kasuariSatkerCanRetryIpv4(array('errno' => 28, 'status' => 0, 'connect_time' => 0.1)), 'Timeout setelah TCP terhubung tidak mengubah keluarga IP.');
  network_assert(!kasuariSatkerCanRetryIpv4(array('errno' => 60, 'status' => 0, 'connect_time' => 0)), 'Kesalahan sertifikat tidak diabaikan.');
  $options = kasuariSatkerCurlOptions('https://example.test/sync', array('action' => 'health'), 15, 180, true);
  network_assert($options[CURLOPT_SSL_VERIFYPEER] && $options[CURLOPT_SSL_VERIFYHOST] === 2, 'Verifikasi HTTPS tetap aktif.');
  network_assert($options[CURLOPT_IPRESOLVE] === CURL_IPRESOLVE_V4 && $options[CURLOPT_URL] === 'https://example.test/sync', 'IPv4 tetap memakai hostname asli untuk TLS.');
  network_assert(!$options[CURLOPT_FOLLOWLOCATION], 'POST tidak diteruskan ke redirect lain.');
  $invalid = kasuariSatkerHttpRequest('https://username:secret@example.test/', array(), array());
  network_assert($invalid['status'] === 0 && strpos($invalid['error'], 'secret') === false, 'URL berkredensial ditolak tanpa membocorkannya.');

  $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
  if (!$socket) {
    throw new RuntimeException($error);
  }
  $address = stream_socket_get_name($socket, false);
  fclose($socket);
  $url = 'http://' . $address;
  file_put_contents($state, '{}');
  putenv('KASUARI_NETWORK_TEST_STATE=' . $state);
  $server = proc_open(array(PHP_BINARY, '-S', $address, __DIR__ . '/fixtures/satker_network_router.php'),
    array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
  if (!is_resource($server)) {
    throw new RuntimeException('Server pengujian gagal dimulai.');
  }
  for ($i = 0; $i < 50; $i++) {
    $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
    if ($probe) {
      fclose($probe);
      break;
    }
    usleep(100000);
  }
  $payload = array('action' => 'batch', 'request_id' => 'fixture-123', 'pn_id' => '439',
    'query_payload' => 'fixture+data/with=encoding');
  $result = kasuariSatkerHttpRequest($url . '/echo', $payload, array('timeout' => 3, 'connect_timeout' => 1));
  $echo = json_decode($result['body'], true);
  network_assert($result['status'] === 200 && $echo['payload'] === $payload && $echo['method'] === 'POST', 'Payload, kode Sorong, request_id dan metode POST tidak berubah.');
  $result = kasuariSatkerHttpRequest($url . '/retry', $payload, array('attempts' => 2));
  $echo = json_decode($result['body'], true);
  network_assert($result['status'] === 200 && $echo['payload'] === $payload, 'HTTP 503 dicoba ulang dengan payload identik.');
  $result = kasuariSatkerHttpRequest($url . '/forbidden', $payload, array('attempts' => 3));
  $counts = json_decode(file_get_contents($state), true);
  network_assert($result['status'] === 403 && $counts['/forbidden'] === 1 && strpos($result['error'], '403') !== false, 'HTTP 403 dilaporkan dan tidak diulang.');
  $result = kasuariSatkerHttpRequest($url . '/redirect', $payload, array('attempts' => 3));
  $counts = json_decode(file_get_contents($state), true);
  network_assert($result['status'] === 302 && $counts['/redirect'] === 1 && $counts['/echo'] === 1, 'Redirect tidak menerima data sinkronisasi.');
  putenv('KASUARI_SYNC_IP_FAMILY=ipv4');
  $result = kasuariSatkerHttpRequest($url . '/echo', array('action' => 'health'), array());
  network_assert($result['status'] === 200 && $result['ipv4'], 'Override IPv4 bekerja pada permintaan nyata.');
  putenv('KASUARI_SYNC_ENDPOINT=' . $url . '/echo');
  $monitoring = kasuariSatkerReportStatus('https://unused.test/', '439', 'test', array(
    'local_perkara_count' => 5870, 'local_data_changed_at' => '2026-10-28 10:00:00',
    'local_signature' => 'fixture'));
  network_assert($monitoring['ok'] && $monitoring['data']['payload']['pn_id'] === '439'
    && $monitoring['data']['payload']['action'] === 'status', 'Pelaporan monitoring memakai endpoint dan transport yang sama.');
  $legacy = json_decode(curl($url . '/echo', array('pn_id' => '439')), true);
  network_assert($legacy['payload']['pn_id'] === '439', 'Helper lama tetap mengirim data dengan benar.');
  putenv('KASUARI_SYNC_IP_FAMILY');
  $closed = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
  $closedAddress = stream_socket_get_name($closed, false);
  fclose($closed);
  $failed = kasuariSatkerHttpRequest('http://' . $closedAddress . '/sync', array('action' => 'health'),
    array('attempts' => 2, 'connect_timeout' => 1, 'timeout' => 2));
  network_assert($failed['status'] === 0 && in_array($failed['errno'], array(7, 28), true) && $failed['ipv4']
    && strpos($failed['error'], 'firewall') !== false, 'Koneksi TCP ditolak benar-benar mencoba IPv4 dan menampilkan arahan jaringan.');
  echo "ALL SATKER NETWORK CHECKS PASSED\n";
} finally {
  if (is_resource($server)) {
    proc_terminate($server);
    foreach ($pipes as $pipe) {
      fclose($pipe);
    }
    proc_close($server);
  }
  if (is_file($state)) {
    unlink($state);
  }
  foreach (array('KASUARI_SYNC_ENDPOINT' => $oldEndpoint, 'KASUARI_SYNC_IP_FAMILY' => $oldFamily,
      'KASUARI_NETWORK_TEST_STATE' => $oldState) as $key => $value) {
    putenv($value === false ? $key : $key . '=' . $value);
  }
}
