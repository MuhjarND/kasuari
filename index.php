<?php
header('Access-Control-Allow-Origin: *');
if(!isset($_SESSION)){
	session_start();
}

include_once("sys/sys_authorization.php");
 
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
foreach($_GET as $key=>$value){$$key=$value;}
if(isset($modul)){
	$adminModules = array('pengguna', 'blangko', 'jenis_blangko', 'variabel', 'identitas_satker');
	if (isset($_SESSION['userid']) && in_array($modul, $adminModules, true) && !kasuari_is_admin()) {
		http_response_code(403);
		include "modul/akses_ditolak.php";
		exit;
	}
	if(!file_exists("modul/".$modul.".php"))die(include "modul/404.php");
	include "modul/".$modul.".php";
}else{
	if(!file_exists("modul/beranda.php"))die(include "modul/404.php");
	include "modul/beranda.php";
}
?>
