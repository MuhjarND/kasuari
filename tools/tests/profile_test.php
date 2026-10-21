<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

function profile_assert($condition, $description) {
    if (!$condition) { throw new RuntimeException($description); }
    echo 'PASS: ' . $description . PHP_EOL;
}
function profile_port() {
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    return (int) substr(strrchr($address, ':'), 1);
}
function profile_batch($db, $sql) {
    if (!mysqli_multi_query($db, $sql)) { throw new RuntimeException(mysqli_error($db)); }
    do {
        $result = mysqli_store_result($db);
        if ($result) { mysqli_free_result($result); }
        if (!mysqli_more_results($db)) { break; }
        if (!mysqli_next_result($db)) { throw new RuntimeException(mysqli_error($db)); }
    } while (true);
}
function profile_http($base, $jar, $path, $data = null) {
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_TIMEOUT => 15));
    if ($data !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    curl_close($curl);
    return array('status' => $status, 'body' => $body);
}
function profile_token($response, $name = 'csrf_token') {
    if (!preg_match('/name="' . $name . '" value="([^"]+)"/', $response['body'], $match)) {
        throw new RuntimeException('Token formulir tidak ditemukan.');
    }
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}
function profile_login($base, $jar, $username, $password) {
    $page = profile_http($base, $jar, '/login');
    return profile_http($base, $jar, '/validateAuth', http_build_query(array(
        'aksi' => base64_encode('login'), 'username' => $username, 'password' => $password,
        'crsf' => profile_token($page, 'crsf'))));
}

$repo = realpath(__DIR__ . '/../..');
$fixture = $repo . '/output/profile-tests/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
mkdir($fixture . '/app', 0755, true);
$processes = array();
$db = null;
$base = '';
try {
    $databasePort = profile_port(); $appPort = profile_port(); $gatewayPort = profile_port();
    $mysqlRoot = getenv('KASUARI_TEST_MYSQL_ROOT') ?: 'C:/xampp/mysql';
    $install = proc_open(array($mysqlRoot . '/bin/mysql_install_db.exe', '--datadir=' . $fixture . '/mysql', '--port=' . $databasePort, '--silent'),
        array(0 => array('pipe', 'r'), 1 => array('file', $fixture . '/mysql-install.log', 'w'), 2 => array('file', $fixture . '/mysql-install-error.log', 'w')), $pipes);
    fclose($pipes[0]);
    if (proc_close($install) !== 0) { throw new RuntimeException('Inisialisasi database fixture gagal: ' . $fixture); }
    $processes[] = proc_open(array($mysqlRoot . '/bin/mysqld.exe', '--no-defaults', '--basedir=' . $mysqlRoot,
        '--datadir=' . $fixture . '/mysql', '--port=' . $databasePort, '--bind-address=127.0.0.1',
        '--innodb-buffer-pool-size=32M', '--console'),
        array(0 => array('file', 'NUL', 'r'), 1 => array('file', $fixture . '/mysql.log', 'a'), 2 => array('file', $fixture . '/mysql.log', 'a')), $pipes);
    mysqli_report(MYSQLI_REPORT_OFF);
    for ($i = 0; $i < 80; $i++) {
        $db = @mysqli_connect('127.0.0.1', 'root', '', '', $databasePort);
        if ($db) { break; }
        usleep(100000);
    }
    if (!$db) { throw new RuntimeException('Database fixture tidak aktif: ' . $fixture); }
    profile_batch($db, 'CREATE DATABASE kasuari_profile_fixture CHARACTER SET utf8mb4; USE kasuari_profile_fixture;
        CREATE TABLE sys_users (userid INT PRIMARY KEY, fullname VARCHAR(255), username VARCHAR(30), password VARCHAR(100),
        `group` INT, email VARCHAR(100), no_wa VARCHAR(20), block INT DEFAULT 0, diinput_oleh VARCHAR(50),
        diinput_tanggal DATETIME, diedit_oleh VARCHAR(50), diedit_tanggal DATETIME) ENGINE=InnoDB;');
    foreach (array(array(1, 'Administrator Fixture', 'admin', 0, 0), array(2, 'Pegawai Fixture', 'pegawai', 1, 0),
        array(3, 'Nonaktif Fixture', 'nonaktif', 1, 1), array(4, 'Legacy Fixture', 'legacy', 1, 0)) as $record) {
        $hash = $record[0] === 4 ? base64_encode(md5('FixturePass!2026')) : password_hash('FixturePass!2026', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($db, 'INSERT INTO sys_users(userid,fullname,username,password,`group`,email,block) VALUES(?,?,?,?,?,?,?)');
        $email = $record[2] . '@example.test';
        mysqli_stmt_bind_param($stmt, 'isssisi', $record[0], $record[1], $record[2], $hash, $record[3], $email, $record[4]);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    }
    profile_batch($db, file_get_contents($repo . '/database/user_profiles.sql'));
    profile_batch($db, file_get_contents($repo . '/database/user_profiles.sql'));
    profile_assert(mysqli_num_rows(mysqli_query($db, 'SELECT * FROM sys_users WHERE block=1')) === 1,
        'Migrasi dapat diulang tanpa mengaktifkan akun nonaktif.');

    foreach (array('sys', 'modul', 'assets/css', 'uploads/profiles', 'sessions') as $directory) { mkdir($fixture . '/app/' . $directory, 0755, true); }
    $files = array('index.php', 'modul/404.php', 'modul/profil.php', 'modul/login.php', 'modul/validateAuth.php', 'modul/autologin.php', 'modul/akses_ditolak.php',
        'sys/sys_session.php', 'sys/sys_authorization.php', 'sys/sys_config.php', 'sys/sys_fungsi.php', 'sys/sys_koneksi.php',
        'sys/sys_database_config.php', 'sys/sys_password.php', 'sys/sys_profile.php', 'sys/header.php', 'sys/footer.php', 'assets/css/kasuari-profile.css');
    foreach ($files as $file) { copy($repo . '/' . $file, $fixture . '/app/' . $file); }
    file_put_contents($fixture . '/app/sys/sys_koneksi.local.php', '<?php return ' . var_export(array(
        'host' => '127.0.0.1', 'username' => 'root', 'password' => '', 'database' => 'kasuari_profile_fixture', 'port' => $databasePort), true) . ';');
    file_put_contents($fixture . '/app/sys/sys_chatbot.php', '<?php $chatbot_gateway_validate_url="http://127.0.0.1:' . $gatewayPort . '/";
        $chatbot_gateway_internal_api_key="fixture-key"; $chatbot_application_code="kasuari";
        $chatbot_autologin_error_message="Link login tidak valid.";');
    file_put_contents($fixture . '/gateway.php', '<?php header("Content-Type: application/json");
        echo json_encode(array("valid" => true, "app_user_id" => ($_POST["token"] === "blocked" ? "3" : "2")));');
    file_put_contents($fixture . '/app/router.php', '<?php
        $path=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH);
        $assetRoot=' . var_export($repo . '/assets/', true) . ';
        if(strpos($path,"/assets/")===0 && strpos($path,"..")===false){
          $file=$assetRoot.substr($path,8);
          if(is_file($file)){ $ext=pathinfo($file,PATHINFO_EXTENSION);
            $mimes=array("css"=>"text/css","js"=>"application/javascript","png"=>"image/png","jpg"=>"image/jpeg","woff2"=>"font/woff2");
            header("Content-Type: ".($mimes[$ext]??"application/octet-stream"));readfile($file);return; }
        }
        if($path==="/beranda"){header("Location: /profil");return;}
        if(is_file(__DIR__.$path)){return false;}
        $_GET["modul"]=ltrim($path,"/")?:"profil";chdir(__DIR__);require __DIR__."/index.php";');
    foreach (array(array($gatewayPort, $fixture, $fixture . '/gateway.php'), array($appPort, $fixture . '/app', $fixture . '/app/router.php')) as $server) {
        $processes[] = proc_open(array(PHP_BINARY, '-d', 'session.save_path=' . $fixture . '/app/sessions', '-S', '127.0.0.1:' . $server[0],
            '-t', $server[1], $server[2]), array(0 => array('file', 'NUL', 'r'), 1 => array('file', $fixture . '/php-' . $server[0] . '.log', 'a'),
            2 => array('file', $fixture . '/php-' . $server[0] . '.log', 'a')), $pipes);
    }
    $base = 'http://127.0.0.1:' . $appPort;
    for ($i = 0; $i < 50; $i++) {
        $socket = @fsockopen('127.0.0.1', $appPort, $errorCode, $errorMessage, 0.1);
        if ($socket) { fclose($socket); break; } usleep(100000);
    }
    $jar = $fixture . '/owner.cookies'; $blockedJar = $fixture . '/blocked.cookies'; $legacyJar = $fixture . '/legacy.cookies';
    $page = profile_http($base, $jar, '/profil');
    profile_assert(strpos($page['body'], '<h1') === false, 'Profil menolak pengunjung tanpa login.');
    $forgedSession = array('userid' => 1, 'fullname' => 'Administrator Fixture', 'username' => 'admin', 'group' => 0, 'email' => 'admin@example.test');
    $page = profile_http($base, $jar, '/profil?' . http_build_query(array('_SESSION' => $forgedSession)));
    profile_assert(strpos($page['body'], '<h1') === false, 'Parameter URL tidak dapat memalsukan sesi pengguna.');
    profile_http($base, $jar, '/validateAuth', http_build_query(array('aksi' => 'invalid', '_SESSION' => $forgedSession)));
    $page = profile_http($base, $jar, '/profil');
    profile_assert(strpos($page['body'], '<h1') === false, 'Parameter form login tidak dapat memalsukan sesi pengguna.');
    profile_login($base, $blockedJar, 'nonaktif', 'FixturePass!2026');
    $page = profile_http($base, $blockedJar, '/login');
    profile_assert(strpos($page['body'], 'Akun dinonaktifkan') !== false, 'Login password menolak akun nonaktif.');
    profile_login($base, $legacyJar, 'legacy', 'FixturePass!2026');
    $hash = mysqli_fetch_assoc(mysqli_query($db, 'SELECT password FROM sys_users WHERE userid=4'))['password'];
    profile_assert(password_verify('FixturePass!2026', $hash), 'Password lama tetap dapat login dan dimigrasikan ke hash modern.');
    profile_login($base, $jar, 'pegawai', 'FixturePass!2026');
    $page = profile_http($base, $jar, '/profil'); $csrf = profile_token($page);
    profile_assert($page['status'] === 200 && strpos($page['body'], 'Profil Saya') !== false, 'Pegawai biasa dapat membuka profil sendiri.');
    $bad = profile_http($base, $jar, '/profil', http_build_query(array('action' => 'identity')));
    profile_assert($bad['status'] === 403, 'Perubahan tanpa CSRF ditolak.');
    $identity = array('action' => 'identity', 'csrf_token' => $csrf, 'fullname' => 'Pegawai Diperbarui', 'username' => 'pegawai.baru',
        'email' => 'baru@example.test', 'current_password' => 'wrong', 'userid' => '1', 'group' => '0', 'block' => '0');
    profile_assert(profile_http($base, $jar, '/profil', http_build_query($identity))['status'] === 422, 'Perubahan identitas memerlukan password saat ini.');
    $identity['current_password'] = 'FixturePass!2026'; $identity['username'] = 'admin';
    profile_assert(profile_http($base, $jar, '/profil', http_build_query($identity))['status'] === 422, 'Username akun lain tidak dapat dipakai.');
    $identity['username'] = 'pegawai.baru';
    profile_assert(profile_http($base, $jar, '/profil', http_build_query($identity))['status'] === 303, 'Pemilik akun dapat menyimpan identitasnya.');
    $user = mysqli_fetch_assoc(mysqli_query($db, 'SELECT * FROM sys_users WHERE userid=2'));
    $admin = mysqli_fetch_assoc(mysqli_query($db, 'SELECT * FROM sys_users WHERE userid=1'));
    profile_assert($user['username'] === 'pegawai.baru' && (int) $user['group'] === 1 && $admin['username'] === 'admin',
        'userid, group, dan block dari formulir tidak dapat mengubah akun lain atau menaikkan hak akses.');
    $csrf = profile_token(profile_http($base, $jar, '/profil'));
    $password = array('action' => 'password', 'csrf_token' => $csrf, 'current_password' => 'FixturePass!2026',
        'password' => 'ChangedPass!2026', 'password_confirmation' => 'wrong');
    profile_assert(profile_http($base, $jar, '/profil', http_build_query($password))['status'] === 422, 'Konfirmasi password yang berbeda ditolak.');
    $password['password_confirmation'] = 'ChangedPass!2026';
    profile_assert(profile_http($base, $jar, '/profil', http_build_query($password))['status'] === 303, 'Password baru berhasil disimpan.');
    $newJar = $fixture . '/new-login.cookies';
    profile_login($base, $newJar, 'pegawai.baru', 'FixturePass!2026');
    $page = profile_http($base, $newJar, '/profil');
    profile_assert(strpos($page['body'], '<h1') === false, 'Password lama tidak dapat dipakai setelah diganti.');
    profile_login($base, $newJar, 'pegawai.baru', 'ChangedPass!2026');
    profile_assert(profile_http($base, $newJar, '/profil')['status'] === 200, 'Login memakai username dan password baru berhasil.');
    $csrf = profile_token(profile_http($base, $jar, '/profil'));
    file_put_contents($fixture . '/fake.png', '<?php echo "invalid";');
    $invalid = profile_http($base, $jar, '/profil', array('action' => 'photo', 'csrf_token' => $csrf,
        'photo' => new CURLFile($fixture . '/fake.png', 'image/png', 'photo.png')));
    profile_assert($invalid['status'] === 422, 'Berkas PHP yang menyamar sebagai foto ditolak.');
    $image = imagecreatetruecolor(900, 600); imagefill($image, 0, 0, imagecolorallocate($image, 30, 160, 120));
    imagepng($image, $fixture . '/photo.png'); imagedestroy($image);
    $upload = profile_http($base, $jar, '/profil', array('action' => 'photo', 'csrf_token' => $csrf,
        'photo' => new CURLFile($fixture . '/photo.png', 'image/png', 'photo.png')));
    profile_assert($upload['status'] === 303, 'Foto profil dapat diunggah.');
    $photo = mysqli_fetch_assoc(mysqli_query($db, 'SELECT photo_path FROM sys_user_profiles WHERE userid=2'))['photo_path'];
    $size = getimagesize($fixture . '/app/' . $photo);
    profile_assert($size['mime'] === 'image/jpeg' && max($size[0], $size[1]) <= 512
        && profile_http($base, $jar, '/' . $photo)['status'] === 200, 'Foto diubah menjadi JPEG dan diperkecil, lalu dapat ditampilkan.');
    $csrf = profile_token(profile_http($base, $jar, '/profil'));
    profile_assert(profile_http($base, $jar, '/profil', http_build_query(array('action' => 'remove_photo', 'csrf_token' => $csrf)))['status'] === 303
        && !is_file($fixture . '/app/' . $photo), 'Foto profil dapat dihapus.');
    mysqli_query($db, 'UPDATE sys_users SET block=1 WHERE userid=2');
    $page = profile_http($base, $jar, '/profil');
    profile_assert($page['status'] === 302, 'Sesi yang sudah login dicabut saat akun dinonaktifkan.');
    mysqli_query($db, 'UPDATE sys_users SET block=0 WHERE userid=2');
    $autoJar = $fixture . '/autologin.cookies';
    $response = profile_http($base, $autoJar, '/autologin', http_build_query(array('token' => 'active', 'redirect' => 'profil')));
    profile_assert($response['status'] === 302 && profile_http($base, $autoJar, '/profil')['status'] === 200,
        'Autologin memakai ID akun tetap berhasil setelah username dan password diubah.');
    $response = profile_http($base, $blockedJar, '/autologin', http_build_query(array('token' => 'blocked', 'redirect' => 'profil')));
    profile_assert(strpos($response['body'], 'Link login tidak valid') !== false, 'Autologin menolak akun nonaktif.');
    profile_assert((int) mysqli_fetch_assoc(mysqli_query($db, 'SELECT block FROM sys_users WHERE userid=3'))['block'] === 1,
        'Akun nonaktif tetap nonaktif setelah seluruh pengujian.');
    $mappingFile = dirname($repo) . '/chatbot/database/sql/map_kasuari_all_users.sql';
    if (is_file($mappingFile)) {
        profile_batch($db, 'CREATE TABLE employees (id INT PRIMARY KEY, name VARCHAR(255), nip VARCHAR(18), is_active INT);
            CREATE TABLE applications (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) UNIQUE, name VARCHAR(255), base_url VARCHAR(255),
                is_active INT, created_at DATETIME, updated_at DATETIME);
            CREATE TABLE employee_app_accounts (employee_id INT, application_code VARCHAR(50), app_user_id VARCHAR(255), is_active INT,
                created_at DATETIME, updated_at DATETIME, UNIQUE KEY(employee_id,application_code));');
        $mappingSql = file_get_contents($mappingFile);
        $prefix = substr($mappingSql, 0, strpos($mappingSql, 'INSERT INTO applications'));
        profile_batch($db, $prefix);
        profile_batch($db, "INSERT INTO employees SELECT CAST(app_user_id AS UNSIGNED)+100, nama,
            nip,1 FROM tmp_kasuari_all;
            UPDATE employees SET name='Administrator Fixture Actual' WHERE id=101;");
        profile_batch($db, $mappingSql);
        profile_assert((int) mysqli_fetch_row(mysqli_query($db, 'SELECT COUNT(*) FROM employee_app_accounts'))[0] === 22,
            'SQL memetakan 22 akun menggunakan NIP, termasuk Administrator dan dua pegawai terbaru.');
        profile_assert((int) mysqli_fetch_row(mysqli_query($db, 'SELECT COUNT(*) FROM employee_app_accounts WHERE is_active=0'))[0] === 3,
            'Tiga mapping akun Kasuari yang diblokir tetap nonaktif.');
        profile_batch($db, "UPDATE employee_app_accounts SET is_active=0 WHERE app_user_id='18';");
        profile_batch($db, $mappingSql);
        profile_assert((int) mysqli_fetch_row(mysqli_query($db, "SELECT is_active FROM employee_app_accounts WHERE app_user_id='18'"))[0] === 0,
            'Pengulangan mapping tidak mengaktifkan kembali mapping yang sudah dinonaktifkan.');
        profile_batch($db, "UPDATE employees SET is_active=0 WHERE id=102;");
        profile_batch($db, $mappingSql);
        profile_assert((int) mysqli_fetch_row(mysqli_query($db, "SELECT is_active FROM employee_app_accounts WHERE app_user_id='2'"))[0] === 0,
            'Pegawai nonaktif tidak memperoleh mapping aktif.');
    }
    echo 'ALL PROFILE CHECKS PASSED' . PHP_EOL;
    if (in_array('--preview', $argv, true)) {
        file_put_contents($repo . '/output/profile-tests/preview.json', json_encode(array('url' => $base . '/profil',
            'login' => $base . '/login', 'username' => 'pegawai.baru', 'password' => 'ChangedPass!2026', 'fixture' => $fixture), JSON_PRETTY_PRINT));
        echo 'PREVIEW_READY ' . $base . '/profil' . PHP_EOL;
        for ($i = 0; $i < 28800 && !is_file($fixture . '/stop'); $i++) { sleep(1); }
    }
} finally {
    if ($db) { mysqli_close($db); }
    foreach (array_reverse($processes) as $process) {
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    }
}
