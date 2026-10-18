<?php
if(!isset($_SESSION)){session_start();}
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");
include_once("sys/sys_koneksi.php");
/*$sql="SELECT * FROM dt_config WHERE id=1"; 

$query=mysqli_query($koneksi,$sql);
$cek = mysqli_num_rows($query);
$isi="";
while($data=mysqli_fetch_assoc($query)){
    foreach($data as $key=>$value) {$$key=$value;}
  }*/
$kode_perkara="PTA.Pb";
$url_app="https://kasuari.pta-papuabarat.go.id/";
$url_api="https://kasuari.pta-papuabarat.go.id/";
$nama_app="KASUARI";
$nama_panjang_app="Kanal Asisten Terintegrasi";
$deskripsi_app="Sistem manajemen dan layanan digital terpadu untuk mendukung tupoksi Pengadilan Tinggi Agama Papua Barat";
$url_apa=$url_app."/_kirim_email";

include_once("sys/sys_fungsi.php");
?>
