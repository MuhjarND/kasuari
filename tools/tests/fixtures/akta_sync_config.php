<?php
// Hanya disalin oleh runner ke webroot sementara dengan database uji terisolasi.
$name = getenv('KASUARI_AKTA_TEST_DB');
if (!is_string($name) || !preg_match('/^kasuari_akta_http_test_[0-9]+$/D', $name)) {
    http_response_code(500);
    exit('Invalid test database.');
}
$koneksi = mysqli_connect('127.0.0.1', 'root', '', $name, (int) (getenv('KASUARI_TEST_DB_PORT') ?: 3306));
mysqli_set_charset($koneksi, 'utf8mb4');
