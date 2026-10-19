<?php
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit;
}
require_once __DIR__ . '/../../sys/sys_database_config.php';

function hosting_assert($condition, $description)
{
  if (!$condition) {
    throw new RuntimeException($description);
  }
  echo "PASS: " . $description . "\n";
}

function hosting_run($arguments)
{
  $process = proc_open($arguments, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
  if (!is_resource($process)) {
    throw new RuntimeException('Tidak dapat menjalankan proses pengujian.');
  }
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $status = proc_close($process);
  if ($status !== 0) {
    throw new RuntimeException($stdout . $stderr);
  }
  return $stdout;
}

function hosting_remove_fixture($path)
{
  if (is_dir($path) && !is_link($path)) {
    foreach (scandir($path) as $entry) {
      if ($entry !== '.' && $entry !== '..') {
        hosting_remove_fixture($path . '/' . $entry);
      }
    }
    rmdir($path);
  } else {
    unlink($path);
  }
}

$repo = realpath(__DIR__ . '/../..');
$fixture = sys_get_temp_dir() . '/kasuari-hosting-test-' . uniqid();
mkdir($fixture);
$environment = array('KASUARI_DB_HOST', 'KASUARI_DB_USER', 'KASUARI_DB_PASSWORD', 'KASUARI_DB_NAME', 'KASUARI_DB_PORT');
$saved = array();
$apache = null;
$apachePipes = array();
try {
  foreach ($environment as $key) {
    $saved[$key] = getenv($key);
    putenv($key);
  }
  try {
    kasuari_database_config($fixture . '/missing.php');
    throw new RuntimeException('Konfigurasi kosong seharusnya ditolak.');
  } catch (RuntimeException $error) {
    hosting_assert(strpos($error->getMessage(), 'belum lengkap') !== false, 'Kredensial tidak memiliki fallback bawaan.');
  }
  putenv('KASUARI_DB_USER=fixture_user');
  putenv('KASUARI_DB_PASSWORD=0');
  putenv('KASUARI_DB_NAME=fixture_database');
  $config = kasuari_database_config($fixture . '/missing.php');
  hosting_assert($config['password'] === '0', 'Password bernilai 0 tidak diganti dengan fallback.');
  putenv('KASUARI_DB_PASSWORD=');
  $config = kasuari_database_config($fixture . '/missing.php');
  hosting_assert($config['password'] === '', 'Password kosong yang eksplisit didukung.');
  file_put_contents($fixture . '/private.php', "<?php return array('username' => 'private_fixture_user', 'password' => '  fixture  ');");
  $config = kasuari_database_config($fixture . '/private.php');
  hosting_assert($config['username'] === 'private_fixture_user' && $config['password'] === '  fixture  ', 'File privat diprioritaskan dan password tidak di-trim.');
  putenv('KASUARI_DB_PORT=70000');
  try {
    kasuari_database_config($fixture . '/missing.php');
    throw new RuntimeException('Port invalid seharusnya ditolak.');
  } catch (RuntimeException $error) {
    hosting_assert(strpos($error->getMessage(), 'Port database') !== false, 'Port database invalid ditolak.');
  }

  $root = $fixture . '/public_html';
  $app = $root . '/kasuari';
  foreach (array($root, $app, $app . '/sys', $app . '/.git', $app . '/assets', $root . '/.well-known') as $directory) {
    mkdir($directory);
  }
  file_put_contents($root . '/.htaccess', "# Existing hosting rules\n");

  $apacheRoot = getenv('KASUARI_TEST_APACHE_ROOT') ?: 'C:/xampp/apache';
  $apacheBin = $apacheRoot . '/bin/httpd.exe';
  if (!is_file($apacheBin)) {
    echo "SKIP: Uji HTTP memerlukan Apache XAMPP Windows atau KASUARI_TEST_APACHE_ROOT.\n";
  } else {
    copy($repo . '/.htaccess', $app . '/.htaccess');
    copy($repo . '/sys/.htaccess', $app . '/sys/.htaccess');
    file_put_contents($app . '/index.php', '<?php header("Content-Type: application/json"); echo json_encode(array("method" => $_SERVER["REQUEST_METHOD"], "query" => $_GET, "body" => $_POST));');
    file_put_contents($app . '/assets/check.css', 'fixture-static');
    file_put_contents($app . '/manifest.webmanifest', '{"name":"fixture"}');
    file_put_contents($app . '/sys/sys_koneksi.local.php', '<?php echo "PRIVATE_FIXTURE";');
    file_put_contents($app . '/sys/sys_koneksi.local.php.bak', 'PRIVATE_BACKUP_FIXTURE');
    file_put_contents($app . '/.git/config', 'PRIVATE_GIT_FIXTURE');
    file_put_contents($root . '/.well-known/check', 'fixture-acme');
    $port = 18765;
    $phpRoot = str_replace('\\', '/', dirname(PHP_BINARY));
    $serverRoot = str_replace('\\', '/', $apacheRoot);
    $documentRoot = str_replace('\\', '/', $root);
    $testRoot = str_replace('\\', '/', $fixture);
    $apacheConfig = 'ServerRoot "' . $serverRoot . '"' . "\n"
      . 'Listen 127.0.0.1:' . $port . "\nServerName localhost\n"
      . 'PidFile "' . $testRoot . '/apache.pid"' . "\n"
      . 'ErrorLog "' . $testRoot . '/apache-error.log"' . "\n"
      . 'LoadModule authz_core_module modules/mod_authz_core.so' . "\n"
      . 'LoadModule authz_host_module modules/mod_authz_host.so' . "\n"
      . 'LoadModule access_compat_module modules/mod_access_compat.so' . "\n"
      . 'LoadModule rewrite_module modules/mod_rewrite.so' . "\n"
      . 'LoadModule mime_module modules/mod_mime.so' . "\n"
      . 'LoadModule dir_module modules/mod_dir.so' . "\n"
      . 'LoadModule php7_module "' . $phpRoot . '/php7apache2_4.dll"' . "\n"
      . 'PHPIniDir "' . $phpRoot . '"' . "\n"
      . 'AddType application/x-httpd-php .php' . "\nDirectoryIndex index.php\n"
      . 'TypesConfig conf/mime.types' . "\n"
      . 'DocumentRoot "' . $documentRoot . '"' . "\n"
      . '<Directory "' . $documentRoot . '">' . "\nOptions FollowSymLinks\nAllowOverride All\nRequire all granted\n</Directory>\n";
    file_put_contents($fixture . '/httpd.conf', $apacheConfig);
    hosting_run(array($apacheBin, '-t', '-f', $fixture . '/httpd.conf'));
    $apache = proc_open(array($apacheBin, '-X', '-f', $fixture . '/httpd.conf'), array(0 => array('pipe', 'r'), 1 => array('file', $fixture . '/stdout.log', 'a'), 2 => array('file', $fixture . '/stderr.log', 'a')), $apachePipes);
    if (!is_resource($apache)) {
      throw new RuntimeException('Apache pengujian gagal dijalankan.');
    }
    $ready = false;
    for ($attempt = 0; $attempt < 40; $attempt++) {
      $socket = @fsockopen('127.0.0.1', $port, $number, $message, 0.2);
      if ($socket) {
        fclose($socket);
        $ready = true;
        break;
      }
      usleep(100000);
    }
    hosting_assert($ready, 'Apache pengujian aktif.');
    $request = function ($path, $body = null) use ($port) {
      $options = array('method' => $body === null ? 'GET' : 'POST', 'header' => "Host: kasuari.pta-papuabarat.go.id\r\nConnection: close\r\n", 'ignore_errors' => true, 'timeout' => 5);
      if ($body !== null) {
        $options['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['content'] = $body;
      }
      $content = file_get_contents('http://127.0.0.1:' . $port . $path, false, stream_context_create(array('http' => $options)));
      preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches);
      return array('status' => (int) $matches[1], 'body' => $content);
    };
    foreach (array('/kasuari/') as $path) {
      $response = $request($path);
      hosting_assert($response['status'] === 200 && json_decode($response['body'], true) !== null, 'Halaman aplikasi: ' . $path);
    }
    $response = $request('/kasuari/login');
    $data = json_decode($response['body'], true);
    hosting_assert($response['status'] === 200 && $data['query']['modul'] === 'login', 'Routing login pada /kasuari/.');
    $response = $request('/kasuari/index.php?modul=laporan_perkara_banding&jenis=4');
    $data = json_decode($response['body'], true);
    hosting_assert($data['query']['modul'] === 'laporan_perkara_banding' && $data['query']['jenis'] === '4', 'Query endpoint laporan dipertahankan.');
    $response = $request('/kasuari/sync?check=1', 'action=health&protocol=2');
    $data = json_decode($response['body'], true);
    hosting_assert($response['status'] === 200 && $data['method'] === 'POST' && $data['body']['action'] === 'health' && $data['query']['check'] === '1' && $data['query']['modul'] === 'sync', 'POST dan query sinkronisasi dipertahankan.');
    foreach (array('/kasuari/assets/check.css' => 'fixture-static', '/.well-known/check' => 'fixture-acme') as $path => $expected) {
      $response = $request($path);
      hosting_assert($response['status'] === 200 && $response['body'] === $expected, 'File statis: ' . $path);
    }
    $response = $request('/kasuari/manifest.webmanifest');
    hosting_assert($response['status'] === 200 && json_decode($response['body'], true)['name'] === 'fixture', 'Manifest dapat diakses dari /kasuari/.');
    foreach (array('/kasuari/sys/sys_koneksi.local.php', '/kasuari/sys/sys_koneksi.local.php.bak', '/kasuari/.git/config') as $path) {
      $response = $request($path);
      hosting_assert($response['status'] === 403, 'Akses privat ditolak: ' . $path);
    }
    hosting_assert(file_get_contents($root . '/.htaccess') === "# Existing hosting rules\n", 'Aturan domain utama tidak diubah.');
  }
} finally {
  if (is_resource($apache)) {
    proc_terminate($apache);
    if (isset($apachePipes[0]) && is_resource($apachePipes[0])) {
      fclose($apachePipes[0]);
    }
    proc_close($apache);
  }
  foreach ($saved as $key => $value) {
    putenv($value === false ? $key : $key . '=' . $value);
  }
  hosting_remove_fixture($fixture);
}
