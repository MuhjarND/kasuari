<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../../sys/sys_akta_cerai.php';
function akta_http_assert($value, $label) {
    if (!$value) throw new RuntimeException($label);
    echo 'PASS: ' . $label . "\n";
}
function akta_http_post($url, $data) {
    $curl = curl_init($url);
    curl_setopt_array($curl, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10));
    $body = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return array($status, $body);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$port = (int) (getenv('KASUARI_TEST_DB_PORT') ?: 3306);
$db = mysqli_connect('127.0.0.1', 'root', '', '', $port);
$name = 'kasuari_akta_http_test_' . getmypid();
$root = sys_get_temp_dir() . '/kasuari-akta-http-' . getmypid();
$repo = dirname(__DIR__, 2);
$process = null; $pipes = array(); $created = array();
mysqli_query($db, 'CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4');
mysqli_select_db($db, $name);
try {
    kasuari_akta_schema_ready($db, true);
    mysqli_query($db, 'CREATE TABLE perkara (pn_id INT, perkara_id BIGINT) ENGINE=InnoDB');
    mysqli_query($db, 'CREATE TABLE log_singkron (id INT AUTO_INCREMENT PRIMARY KEY, id_satker INT, tanggal DATETIME, perkara INT) ENGINE=InnoDB');
    mysqli_query($db, 'CREATE TABLE sync_requests (request_id CHAR(40) PRIMARY KEY, pn_id INT, completed_at DATETIME, jumlah_perkara INT) ENGINE=InnoDB');
    foreach (array('sys', 'modul', 'database') as $dir) mkdir($root . '/' . $dir, 0775, true);
    $sources = array('index.php', 'modul/sync.php', 'sys/sys_authorization.php', 'sys/sys_monitoring.php', 'sys/sys_akta_cerai.php', 'sys/sys_sipp.php', 'sys/sys_sipp_catalog.php', 'database/akta_cerai.sql', 'database/sipp_portal.sql');
    foreach ($sources as $file) { copy($repo . '/' . $file, $root . '/' . $file); $created[] = $root . '/' . $file; }
    copy(__DIR__ . '/fixtures/akta_sync_config.php', $root . '/sys/sys_config.php');
    $created[] = $root . '/sys/sys_config.php';
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    $address = stream_socket_get_name($socket, false); fclose($socket);
    putenv('KASUARI_AKTA_TEST_DB=' . $name);
    putenv('KASUARI_TEST_DB_PORT=' . $port);
    $process = proc_open(array(PHP_BINARY, '-S', $address, '-t', $root),
        array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Test HTTP server failed.');
    for ($i = 0; $i < 50; $i++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($probe) { fclose($probe); break; }
        usleep(100000);
    }
    $url = 'http://' . $address . '/index.php?modul=sync';
    $base = array('protocol' => 2, 'pn_id' => 439, 'request_id' => str_repeat('a', 40));
    $rows = array(array('perkara_id' => '10', 'nomor_akta_cerai' => '10/AC/2026/PA.Test', 'tgl_akta_cerai' => '2026-10-31',
        'tgl_penyerahan_akta_cerai' => null, 'tgl_penyerahan_akta_cerai_pihak2' => null, 'pihak2_tersedia' => 1));
    $json = json_encode($rows);
    $batch = array_merge($base, array('action' => 'akta_cerai_batch', 'akta_rows' => $json, 'akta_hash' => hash('sha256', $json)));
    $response = akta_http_post($url, $batch);
    akta_http_assert($response[0] === 200 && $response[1] === 'AKTA_BATCH_OK', 'Real endpoint accepts structured akta batch: ' . $response[0] . ' ' . $response[1]);
    $response = akta_http_post($url, $batch);
    akta_http_assert($response[0] === 200, 'HTTP batch retry is idempotent.');
    $finalize = array_merge($base, array('action' => 'finalize', 'akta_status_included' => 1, 'akta_row_count' => 2));
    $response = akta_http_post($url, $finalize);
    akta_http_assert($response[0] === 422, 'HTTP finalize rejects incomplete akta snapshot.');
    akta_http_assert(mysqli_num_rows(mysqli_query($db, 'SELECT * FROM sync_requests')) === 0, 'Failed finalize does not write success log.');
    $finalize['akta_row_count'] = 1;
    $response = akta_http_post($url, $finalize);
    akta_http_assert($response[0] === 200 && strpos($response[1], 'SYNC_OK|') === 0, 'Real endpoint commits snapshot and success together.');
    akta_http_assert(mysqli_num_rows(mysqli_query($db, 'SELECT * FROM kasuari_akta_cerai WHERE pn_id=439')) === 1, 'Published row belongs to sending satker.');
    $response = akta_http_post($url, $finalize);
    akta_http_assert($response[0] === 200 && mysqli_num_rows(mysqli_query($db, 'SELECT * FROM log_singkron')) === 1, 'Finalize replay does not duplicate logs.');
    $legacy = array('protocol' => 2, 'pn_id' => 431, 'request_id' => str_repeat('b', 40), 'action' => 'finalize');
    $response = akta_http_post($url, $legacy);
    akta_http_assert($response[0] === 200 && mysqli_num_rows(mysqli_query($db, 'SELECT * FROM kasuari_akta_cerai_sync WHERE pn_id=431')) === 0, 'Old satker finalizes without falsely claiming akta sync.');
    $batch['akta_hash'] = str_repeat('0', 64);
    akta_http_assert(akta_http_post($url, $batch)[0] === 400, 'HTTP endpoint rejects altered payload.');
    echo "ALL AKTA HTTP CHECKS PASSED\n";
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        proc_close($process);
    }
    putenv('KASUARI_AKTA_TEST_DB');
    mysqli_query($db, 'DROP DATABASE `' . $name . '`'); mysqli_close($db);
    foreach ($created as $file) if (is_file($file)) unlink($file);
    foreach (array('sys', 'modul', 'database') as $dir) if (is_dir($root . '/' . $dir)) rmdir($root . '/' . $dir);
    if (is_dir($root)) rmdir($root);
}
