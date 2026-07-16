<?php if(!isset($_SESSION)){session_start();}
$kasuariDebugMode = getenv('KASUARI_DEBUG') === '1';
ini_set('display_errors', $kasuariDebugMode ? '1' : '0');
ini_set('display_startup_errors', $kasuariDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set("Asia/Jakarta"); 
//include('sys_koneksi.php'); 
  
if(!isset($_SESSION['userid']) OR !isset($_SESSION['fullname']) OR !isset($_SESSION['username']) OR !isset($_SESSION['group']) OR !isset($_SESSION['email'])){ 
 	echo '<script>window.location = "login";</script>';
	exit();
}

include_once(__DIR__ . '/sys_koneksi.php');
$sessionUserId = (int) $_SESSION['userid'];
$sessionUser = mysqli_prepare(
  $koneksi,
  "SELECT fullname, username, `group`, email, block FROM sys_users WHERE userid = ? LIMIT 1"
);
if ($sessionUser === false) {
  error_log('Validasi sesi gagal dipersiapkan: '.mysqli_error($koneksi));
  session_unset();
  session_destroy();
  header('Location: login');
  exit;
}
mysqli_stmt_bind_param($sessionUser, 'i', $sessionUserId);
if (!mysqli_stmt_execute($sessionUser)) {
  error_log('Validasi sesi gagal dijalankan: '.mysqli_stmt_error($sessionUser));
  mysqli_stmt_close($sessionUser);
  session_unset();
  session_destroy();
  header('Location: login');
  exit;
}
mysqli_stmt_bind_result($sessionUser, $sessionFullname, $sessionUsername, $sessionGroup, $sessionEmail, $sessionBlock);
$sessionUserFound = mysqli_stmt_fetch($sessionUser);
mysqli_stmt_close($sessionUser);

if (!$sessionUserFound || (int) $sessionBlock === 1) {
  session_unset();
  session_destroy();
  header('Location: login');
  exit;
}

$_SESSION['fullname'] = $sessionFullname;
$_SESSION['username'] = $sessionUsername;
$_SESSION['group'] = $sessionGroup;
$_SESSION['email'] = $sessionEmail;
 
//include "sys_koneksi.php"; 
$sekarang=date("Y-m-d H:i:s");
?>  
