<?php
// Compatible with the older PHP runtime used on SIPP servers.
function kasuariSatkerSyncEndpoint($configuredUrl)
{
  $override = getenv('KASUARI_SYNC_ENDPOINT');
  if ($override !== false && trim($override) !== '') {
    $configuredUrl = trim($override);
  }
  $endpoint = rtrim(trim((string) $configuredUrl), '/');
  return substr($endpoint, -5) === '/sync' ? $endpoint : $endpoint . '/sync';
}

function kasuariSatkerCanRetryIpv4($result)
{
  // Retry another address family only before TCP connected, never mid-upload.
  return in_array((int) $result['errno'], array(6, 7, 28), true)
    && (int) $result['status'] === 0 && (float) $result['connect_time'] <= 0;
}

function kasuariSatkerCurlOptions($url, $data, $connectTimeout, $timeout, $ipv4)
{
  $options = array(
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data, '', '&') : $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => $connectTimeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => array('Expect:'),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FOLLOWLOCATION => false
  );
  if ($ipv4 && defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
    $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
  }
  return $options;
}

function kasuariSatkerTransportMessage($url, $result)
{
  $host = parse_url($url, PHP_URL_HOST);
  $port = parse_url($url, PHP_URL_PORT);
  if (!$port) {
    $port = strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80;
  }
  $target = (string) $host . ':' . $port;
  $number = (int) $result['errno'];
  if ($number === 6) {
    return 'DNS server satker tidak dapat menemukan ' . $target . '. Periksa DNS dan alamat pusat. [cURL 6]';
  }
  if ($number === 7 || ($number === 28 && (float) $result['connect_time'] <= 0)) {
    return 'Koneksi dari server satker ke ' . $target . ' gagal atau melewati batas waktu. '
      . 'Periksa internet, DNS, proxy, firewall, dan pemblokiran IP publik satker di hosting pusat. '
      . '[cURL ' . $number . '; ' . ($result['ipv4'] ? 'IPv4' : 'otomatis') . ']';
  }
  if ($number === 28) {
    return 'Server pusat belum menyelesaikan respons dalam batas waktu. '
      . 'Periksa jaringan dan log pusat sebelum mencoba lagi. [cURL 28]';
  }
  if (in_array($number, array(35, 51, 58, 60, 77), true)) {
    return 'Koneksi HTTPS ke ' . $target . ' gagal diverifikasi. '
      . 'Periksa tanggal server, sertifikat CA PHP/cURL, dan versi TLS. [cURL ' . $number . ']';
  }
  if ($number !== 0) {
    return 'Koneksi ke ' . $target . ' gagal. [cURL ' . $number . ']';
  }
  return 'Pusat mengembalikan HTTP ' . (int) $result['status'] . ' dari ' . $target . '.';
}

function kasuariSatkerHttpRequest($url, $data, $settings)
{
  $result = array('status' => 0, 'body' => '', 'error' => '', 'errno' => 0,
    'connect_time' => 0, 'total_time' => 0, 'primary_ip' => '', 'ipv4' => false);
  if (!function_exists('curl_init')) {
    $result['error'] = 'Ekstensi cURL belum aktif pada PHP satker.';
    return $result;
  }
  $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
  if (!in_array($scheme, array('https', 'http'), true) || !parse_url($url, PHP_URL_HOST)
      || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) {
    $result['error'] = 'Alamat pusat harus berupa URL HTTP/HTTPS tanpa kredensial.';
    return $result;
  }
  $attempts = isset($settings['attempts']) ? max(1, min(3, (int) $settings['attempts'])) : 2;
  $connectTimeout = isset($settings['connect_timeout']) ? max(1, (int) $settings['connect_timeout']) : 15;
  $timeout = isset($settings['timeout']) ? max($connectTimeout, (int) $settings['timeout']) : 180;
  $ipv4 = strtolower(trim((string) getenv('KASUARI_SYNC_IP_FAMILY'))) === 'ipv4';
  for ($attempt = 1; $attempt <= $attempts; $attempt++) {
    $ch = curl_init();
    curl_setopt_array($ch, kasuariSatkerCurlOptions($url, $data, $connectTimeout, $timeout, $ipv4));
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $result = array(
      'status' => (int) $info['http_code'],
      'body' => $body === false ? '' : trim($body),
      'errno' => curl_errno($ch),
      'connect_time' => (float) $info['connect_time'],
      'total_time' => (float) $info['total_time'],
      'primary_ip' => isset($info['primary_ip']) ? $info['primary_ip'] : '',
      'ipv4' => $ipv4,
      'error' => ''
    );
    curl_close($ch);
    if ($body !== false && $result['status'] >= 200 && $result['status'] < 300) {
      return $result;
    }
    $result['error'] = kasuariSatkerTransportMessage($url, $result);
    // Certificate/auth/validation errors will not improve with a repeated POST.
    if (($result['errno'] !== 0 && !in_array($result['errno'], array(6, 7, 28), true))
        || ($result['status'] >= 400 && $result['status'] < 500
          && !in_array($result['status'], array(408, 429), true))
        || ($result['status'] >= 300 && $result['status'] < 400)) {
      break;
    }
    if (kasuariSatkerCanRetryIpv4($result)) {
      $ipv4 = true;
    }
    if ($attempt < $attempts) {
      usleep(500000 * $attempt);
    }
  }
  return $result;
}
