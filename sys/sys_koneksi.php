<?php
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$databaseConfig = array(
  'host' => getenv('KASUARI_DB_HOST') ?: 'localhost',
  'username' => getenv('KASUARI_DB_USER') ?: 'u1483703_dbptapapuabarat',
  'password' => getenv('KASUARI_DB_PASSWORD') ?: 'PtaPabar@2024Jaya!',
  'database' => getenv('KASUARI_DB_NAME') ?: 'u1483703_db_kasuari',
  'port' => getenv('KASUARI_DB_PORT') ?: 3306,
);

// Konfigurasi lokal/server tidak dilacak Git agar tidak tertimpa saat git pull.
$localConfigPath = __DIR__ . '/sys_koneksi.local.php';
if (is_file($localConfigPath)) {
  $localConfig = include $localConfigPath;
  if (is_array($localConfig)) {
    $databaseConfig = array_merge($databaseConfig, $localConfig);
  }
}

$nama_host = trim((string) $databaseConfig['host']);
$user_host = trim((string) $databaseConfig['username']);
$password_host = (string) $databaseConfig['password'];
$db_host = trim((string) $databaseConfig['database']);
$databasePort = (int) $databaseConfig['port'];
if ($databasePort <= 0) {
  $databasePort = 3306;
}

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @mysqli_connect($nama_host, $user_host, $password_host, $db_host, $databasePort);
if (!$koneksi) {
  if (function_exists('http_response_code')) {
    http_response_code(500);
  }
  $connectionError = mysqli_connect_error();
  error_log('Koneksi database Kasuari gagal: '.$connectionError);
  $message = "Koneksi database Kasuari gagal. Pastikan konfigurasi MySQL pada sys/sys_koneksi.local.php sudah sesuai.";
  if ($kasuariDebugMode) {
    $message .= " Detail: ".$connectionError;
  }
  die($message);
}

mysqli_set_charset($koneksi, "utf8mb4");
$conn = $koneksi;
?>
