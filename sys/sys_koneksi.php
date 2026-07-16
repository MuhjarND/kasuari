<?php
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$nama_host = "localhost";
$user_host = "dok_banding";
$password_host = "123456";
$db_host = "dok_banding";

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @mysqli_connect($nama_host, $user_host, $password_host, $db_host);
if (!$koneksi) {
  if (function_exists('http_response_code')) {
    http_response_code(500);
  }
  $connectionError = mysqli_connect_error();
  error_log('Koneksi database Kasuari gagal: '.$connectionError);
  $message = "Koneksi database Kasuari gagal. Pastikan database dan akun MySQL pada sys/sys_koneksi.php sudah sesuai.";
  if ($kasuariDebugMode) {
    $message .= " Detail: ".$connectionError;
  }
  die($message);
}

mysqli_set_charset($koneksi, "utf8mb4");
$conn = $koneksi;
?>
