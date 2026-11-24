<?php
$dbName = getenv('KASUARI_SIPP_TEST_DB');
if (!is_string($dbName) || !preg_match('/^kasuari_sipp_http_[0-9]+$/D', $dbName)) { http_response_code(500); exit('Invalid test database.'); }
$koneksi = mysqli_connect('127.0.0.1', 'root', '', $dbName, (int) getenv('KASUARI_TEST_DB_PORT'));
mysqli_set_charset($koneksi, 'utf8mb4');
require_once __DIR__ . '/sys_fungsi.php';
date_default_timezone_set('Asia/Jayapura');
