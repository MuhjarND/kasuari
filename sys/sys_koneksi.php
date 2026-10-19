<?php
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/sys_database_config.php';
try {
  $databaseConfig = kasuari_database_config();
} catch (Exception $error) {
  error_log('Konfigurasi database Kasuari gagal: ' . $error->getMessage());
  http_response_code(500);
  exit('Konfigurasi database Kasuari belum lengkap. Isi sys/sys_koneksi.local.php atau environment KASUARI_DB_ pada server.');
}

$nama_host = $databaseConfig['host'];
$user_host = $databaseConfig['username'];
$password_host = $databaseConfig['password'];
$db_host = $databaseConfig['database'];
$databasePort = $databaseConfig['port'];

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @mysqli_connect($nama_host, $user_host, $password_host, $db_host, $databasePort);
if (!$koneksi) {
  http_response_code(500);
  error_log('Koneksi database Kasuari gagal: ' . mysqli_connect_error());
  exit('Koneksi database Kasuari gagal. Pastikan konfigurasi MySQL pada sys/sys_koneksi.local.php sudah sesuai.');
}

if (!mysqli_set_charset($koneksi, 'utf8mb4')) {
  error_log('Koneksi database Kasuari gagal mengatur charset: ' . mysqli_error($koneksi));
  http_response_code(500);
  exit('Koneksi database Kasuari gagal mengatur charset.');
}
$conn = $koneksi;
