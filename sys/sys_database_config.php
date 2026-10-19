<?php
function kasuari_database_config($localConfigPath = null)
{
  $environmentKeys = array(
    'host' => 'KASUARI_DB_HOST',
    'username' => 'KASUARI_DB_USER',
    'password' => 'KASUARI_DB_PASSWORD',
    'database' => 'KASUARI_DB_NAME',
    'port' => 'KASUARI_DB_PORT',
  );
  $config = array('host' => 'localhost', 'username' => '', 'password' => null, 'database' => '', 'port' => 3306);
  foreach ($environmentKeys as $key => $variable) {
    $value = getenv($variable);
    if ($value !== false) {
      $config[$key] = $value;
    }
  }

  $localConfigPath = $localConfigPath === null ? __DIR__ . '/sys_koneksi.local.php' : $localConfigPath;
  if (is_file($localConfigPath)) {
    $localConfig = include $localConfigPath;
    if (!is_array($localConfig)) {
      throw new RuntimeException('File konfigurasi privat harus mengembalikan array.');
    }
    $config = array_merge($config, array_intersect_key($localConfig, $config));
  }
  foreach (array('host', 'username', 'database') as $key) {
    if (!is_string($config[$key]) || trim($config[$key]) === '') {
      throw new RuntimeException('Konfigurasi database belum lengkap pada field ' . $key . '.');
    }
    $config[$key] = trim($config[$key]);
  }
  // Password kosong hanya diizinkan jika secara eksplisit dikonfigurasi, misalnya XAMPP lokal.
  if (!is_string($config['password'])) {
    throw new RuntimeException('Password database belum dikonfigurasi.');
  }
  $port = filter_var($config['port'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 65535)));
  if ($port === false) {
    throw new RuntimeException('Port database harus berupa angka 1 sampai 65535.');
  }
  $config['port'] = $port;
  return $config;
}
