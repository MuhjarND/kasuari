<?php
if (!isset($_SESSION)) {
  session_start();
}

include_once(__DIR__ . '/../sys/sys_chatbot.php');

function kasuari_autologin_error($message)
{
  http_response_code(401);
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Link Login Tidak Valid</title>
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #111827;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }
      .panel {
        width: min(560px, calc(100% - 32px));
        padding: 32px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 20px 45px rgba(15, 23, 42, .12);
      }
      h1 {
        margin: 0 0 12px;
        font-size: 24px;
        line-height: 1.25;
      }
      p {
        margin: 0 0 24px;
        color: #374151;
        line-height: 1.6;
      }
      a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 18px;
        border-radius: 6px;
        color: #fff;
        background: #2563eb;
        text-decoration: none;
        font-weight: 600;
      }
    </style>
  </head>
  <body>
    <main class="panel">
      <h1>Link login tidak valid</h1>
      <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
      <a href="login">Kembali ke login</a>
    </main>
  </body>
  </html>
  <?php
  exit;
}

function kasuari_autologin_token_from_query()
{
  $rawQuery = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';

  foreach (explode('&', $rawQuery) as $part) {
    if ($part === '') {
      continue;
    }

    $pair = array_pad(explode('=', $part, 2), 2, '');
    if (rawurldecode($pair[0]) === 'token') {
      return rawurldecode($pair[1]);
    }
  }

  return isset($_GET['token']) ? (string) $_GET['token'] : '';
}

function kasuari_autologin_safe_redirect($path)
{
  $path = trim(rawurldecode((string) $path));

  if ($path === '') {
    return 'beranda';
  }

  if (preg_match('/[\r\n]/', $path)) {
    return 'beranda';
  }

  if (strpos($path, '://') !== false || strpos($path, '//') === 0) {
    return 'beranda';
  }

  $path = ltrim($path, '/');
  if (strpos($path, 'kasuari/') === 0) {
    $path = substr($path, strlen('kasuari/'));
  }

  if (strpos($path, '?') === 0) {
    parse_str(ltrim($path, '?'), $query);
    $path = isset($query['modul']) ? (string) $query['modul'] : '';
  }

  $module = preg_split('/[?\/#]/', $path, 2);
  $module = isset($module[0]) ? trim($module[0]) : '';

  if ($module === '' || in_array($module, array('autologin', 'validateAuth', 'login'), true)) {
    return 'beranda';
  }

  if (!preg_match('/^[A-Za-z0-9_ -]+$/', $module)) {
    return 'beranda';
  }

  if (!file_exists(__DIR__ . '/' . $module . '.php')) {
    return 'beranda';
  }

  return $path;
}

function kasuari_autologin_array_get($array, $path)
{
  $segments = explode('.', $path);
  $value = $array;

  foreach ($segments as $segment) {
    if (!is_array($value) || !array_key_exists($segment, $value)) {
      return null;
    }

    $value = $value[$segment];
  }

  return $value;
}

function kasuari_autologin_is_valid_response($data)
{
  $valid = kasuari_autologin_array_get($data, 'valid');
  $success = kasuari_autologin_array_get($data, 'success');
  $status = strtolower((string) kasuari_autologin_array_get($data, 'status'));

  return $valid === true
    || $valid === 1
    || $valid === '1'
    || strtolower((string) $valid) === 'true'
    || $success === true
    || $success === 1
    || $success === '1'
    || strtolower((string) $success) === 'true'
    || in_array($status, array('valid', 'success', 'ok'), true);
}

function kasuari_autologin_extract_app_user_id($data)
{
  $paths = array(
    'app_user_id',
    'data.app_user_id',
    'user.app_user_id',
    'data.user.app_user_id',
    'user_id',
    'data.user_id',
    'id',
    'data.id',
    'email',
    'data.email',
    'username',
    'data.username',
  );

  foreach ($paths as $path) {
    $value = kasuari_autologin_array_get($data, $path);
    if (!empty($value)) {
      return (string) $value;
    }
  }

  return '';
}

function kasuari_autologin_validate_token($token)
{
  global $chatbot_gateway_validate_url, $chatbot_gateway_internal_api_key, $chatbot_application_code;

  if (!$chatbot_gateway_validate_url || !$chatbot_gateway_internal_api_key || $chatbot_gateway_internal_api_key === 'isi_internal_api_key') {
    error_log('Kasuari chatbot autologin gagal: konfigurasi gateway belum lengkap.');
    return array('valid' => false, 'reason' => 'missing_config');
  }

  $payload = http_build_query(array(
    'token' => $token,
    'application_code' => $chatbot_application_code,
  ));

  $responseBody = false;
  $statusCode = 0;

  if (function_exists('curl_init')) {
    $ch = curl_init($chatbot_gateway_validate_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'X-INTERNAL-API-KEY: ' . $chatbot_gateway_internal_api_key,
      'Accept: application/json',
      'Content-Type: application/x-www-form-urlencoded',
    ));
    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($responseBody === false) {
      error_log('Kasuari chatbot autologin gagal: ' . curl_error($ch));
    }
    curl_close($ch);
  } else {
    $context = stream_context_create(array(
      'http' => array(
        'method' => 'POST',
        'timeout' => 8,
        'header' => implode("\r\n", array(
          'X-INTERNAL-API-KEY: ' . $chatbot_gateway_internal_api_key,
          'Accept: application/json',
          'Content-Type: application/x-www-form-urlencoded',
        )),
        'content' => $payload,
        'ignore_errors' => true,
      ),
    ));
    $responseBody = @file_get_contents($chatbot_gateway_validate_url, false, $context);
    if (isset($http_response_header) && is_array($http_response_header)) {
      foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $match)) {
          $statusCode = (int) $match[1];
          break;
        }
      }
    }
  }

  if ($responseBody === false || $statusCode < 200 || $statusCode >= 300) {
    error_log('Kasuari chatbot autologin gagal: gateway HTTP status ' . $statusCode);
    return array('valid' => false, 'reason' => 'gateway_http_error');
  }

  $data = json_decode((string) $responseBody, true);
  if (!is_array($data)) {
    return array('valid' => false, 'reason' => 'invalid_json');
  }

  $appUserId = kasuari_autologin_extract_app_user_id($data);
  if (!kasuari_autologin_is_valid_response($data) || $appUserId === '') {
    return array('valid' => false, 'reason' => 'invalid_payload');
  }

  return array('valid' => true, 'app_user_id' => $appUserId);
}

function kasuari_autologin_find_user($connection, $appUserId)
{
  if (ctype_digit($appUserId)) {
    $userid = (int) $appUserId;
    $stmt = mysqli_prepare($connection, "SELECT userid, fullname, username, `group`, email, block FROM sys_users WHERE userid = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $userid);
  } elseif (filter_var($appUserId, FILTER_VALIDATE_EMAIL)) {
    $email = strtolower(trim($appUserId));
    $stmt = mysqli_prepare($connection, "SELECT userid, fullname, username, `group`, email, block FROM sys_users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
  } else {
    $username = strtolower(trim($appUserId));
    $stmt = mysqli_prepare($connection, "SELECT userid, fullname, username, `group`, email, block FROM sys_users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
  }

  if (!$stmt) {
    return null;
  }

  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);

  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    return null;
  }

  mysqli_stmt_bind_result($stmt, $userid, $fullname, $username, $group, $email, $block);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ((int) $block === 1) {
    return null;
  }

  return array(
    'userid' => $userid,
    'fullname' => $fullname,
    'username' => $username,
    'group' => $group,
    'email' => $email,
  );
}

$errorMessage = isset($chatbot_autologin_error_message) ? $chatbot_autologin_error_message : 'Link login tidak valid atau sudah kedaluwarsa.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $token = kasuari_autologin_token_from_query();
  $redirect = kasuari_autologin_safe_redirect(isset($_GET['redirect']) ? (string) $_GET['redirect'] : '');

  if ($token === '') {
    kasuari_autologin_error($errorMessage);
  }

  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Memproses Login</title>
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #111827;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }
      .panel {
        width: min(520px, calc(100% - 32px));
        padding: 32px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 20px 45px rgba(15, 23, 42, .12);
      }
      h1 {
        margin: 0 0 12px;
        font-size: 24px;
        line-height: 1.25;
      }
      p {
        margin: 0;
        color: #4b5563;
        line-height: 1.6;
      }
    </style>
  </head>
  <body>
    <main class="panel">
      <h1>Memproses login</h1>
      <p>Mohon tunggu sebentar.</p>
      <form id="autologin-form" method="post" action="autologin">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">
      </form>
    </main>
    <script>
      document.getElementById('autologin-form').submit();
    </script>
  </body>
  </html>
  <?php
  exit;
}

$token = isset($_POST['token']) ? (string) $_POST['token'] : '';
$redirect = kasuari_autologin_safe_redirect(isset($_POST['redirect']) ? (string) $_POST['redirect'] : '');

if ($token === '') {
  kasuari_autologin_error($errorMessage);
}

$validation = kasuari_autologin_validate_token($token);
if (empty($validation['valid']) || empty($validation['app_user_id'])) {
  if (isset($_SESSION['userid'])) {
    header('Location: ' . $redirect);
    exit;
  }

  kasuari_autologin_error($errorMessage);
}

include_once(__DIR__ . '/../sys/sys_config.php');

$user = kasuari_autologin_find_user($koneksi, (string) $validation['app_user_id']);
if (!$user) {
  error_log('Kasuari chatbot autologin gagal: user tidak ditemukan untuk app_user_id hash ' . substr(hash('sha256', (string) $validation['app_user_id']), 0, 16));
  kasuari_autologin_error($errorMessage);
}

session_regenerate_id(true);
$_SESSION['userid'] = $user['userid'];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['username'] = $user['username'];
$_SESSION['group'] = $user['group'];
$_SESSION['email'] = $user['email'];

header('Location: ' . $redirect);
exit;
