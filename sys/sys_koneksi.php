<?php
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
  die(
    "Koneksi database Kasuari gagal. Pastikan database dan akun MySQL pada " .
    "sys/sys_koneksi.php sudah sesuai. Detail: " . mysqli_connect_error()
  );
}

mysqli_set_charset($koneksi, "utf8mb4");
$conn = $koneksi;
?>
