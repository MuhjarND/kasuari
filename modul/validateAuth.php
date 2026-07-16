<?php
if(!isset($_SESSION)){session_start();} 
function ke($url){
	echo '<script>window.location = "'.$url.'";</script>';
}
//echo "mode 1<br>";
if(isset($_GET['aksi'])){
	if($_GET['aksi']=='keluar'){
		session_unset();
        session_destroy();
		ke("login");
	}
} 
//echo "mode 2<br>";
//echo base64_encode(md5("hanoman18"));
foreach($_POST as $key=>$value) {$$key=$value;}
//echo "$password";
if(!isset($_POST["aksi"])){
	$_SESSION['pesan_error_login']= 'Error';
	ke("login");
	exit;
}else{

	//echo "mode 3<br>";
	include('sys/sys_config.php');
	if($aksi==base64_encode("login")){
		if(!isset($_POST["username"]) OR !isset($_POST["password"]) OR !isset($_POST["crsf"])  ){
			$_SESSION['pesan_error_login']= 'Error';
			ke("login");
			exit;
		}

	//echo "mode 4<br>";
		if(@$_SESSION['login_cek'] <> $crsf){
			$_SESSION['pesan_error_login']= 'Error';
			ke("login");
			exit;
		}
		$username = strtolower(trim((string) $username));
		$stmt = mysqli_prepare(
			$koneksi,
			"SELECT userid, fullname, username, password, `group`, email, block
			 FROM sys_users WHERE username = ? LIMIT 1"
		);
		if (!$stmt) {
			error_log('KASUARI login prepare failed: ' . mysqli_error($koneksi));
			$_SESSION['pesan_error_login'] = 'Layanan login sedang bermasalah. Silakan coba kembali.';
			ke("login");
			exit;
		}
		mysqli_stmt_bind_param($stmt, 's', $username);
		if (!mysqli_stmt_execute($stmt)) {
			error_log('KASUARI login execute failed: ' . mysqli_stmt_error($stmt));
			mysqli_stmt_close($stmt);
			$_SESSION['pesan_error_login'] = 'Layanan login sedang bermasalah. Silakan coba kembali.';
			ke("login");
			exit;
		}
		mysqli_stmt_store_result($stmt);

		if (mysqli_stmt_num_rows($stmt) === 0) {
			mysqli_stmt_close($stmt);
			$_SESSION['pesan_error_login'] = "Nama User Belum Terdaftar!<br>Silahkan Hubungi Administrator";
			ke("login");
			exit;
		}

		mysqli_stmt_bind_result($stmt, $useridDb, $fullnameDb, $usernameDb, $passwordDb, $groupDb, $emailDb, $blockDb);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);

		if ((int) $blockDb === 1) {
			$_SESSION['pesan_error_login'] = "Akun dinonaktifkan. Silahkan Hubungi Administrator";
			ke("login");
			exit;
		}

		$legacyPassword = base64_encode(md5($password));
		$legacyValid = hash_equals((string) $passwordDb, $legacyPassword);
		$passwordValid = password_verify($password, (string) $passwordDb) || $legacyValid;

		if (!$passwordValid) {
			$_SESSION['pesan_error_login'] = "Kata Sandi Salah";
			ke("login");
			exit;
		}

		if ($legacyValid) {
			$newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
			$upgrade = mysqli_prepare($koneksi, "UPDATE sys_users SET password = ? WHERE userid = ?");
			if ($upgrade) {
				mysqli_stmt_bind_param($upgrade, 'si', $newPasswordHash, $useridDb);
				if (!mysqli_stmt_execute($upgrade)) {
					error_log('KASUARI password upgrade failed: ' . mysqli_stmt_error($upgrade));
				}
				mysqli_stmt_close($upgrade);
			} else {
				error_log('KASUARI password upgrade prepare failed: ' . mysqli_error($koneksi));
			}
		}

		session_regenerate_id(true);
		$_SESSION['userid'] = $useridDb;
		$_SESSION['fullname'] = $fullnameDb;
		$_SESSION['username'] = $usernameDb;
		$_SESSION['group'] = $groupDb;
		$_SESSION['email'] = $emailDb;
		ke("beranda");
		exit;
	}
}
?>
