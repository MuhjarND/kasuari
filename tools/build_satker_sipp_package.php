<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi ZIP belum aktif.');
$repo = dirname(__DIR__);
$client = isset($argv[1]) ? realpath($argv[1]) : realpath(dirname($repo) . '/kasuari_satker');
if (!$client || !is_dir($client)) throw new RuntimeException('Folder client satker tidak ditemukan.');
$source = $repo . '/deploy/satker-sipp';
$files = array('sys/sys_sipp.php', 'sys/sys_akta_cerai.php', 'sys/sys_fungsi.php', 'sys/sys_transport.php',
    'sys/sys_sipp.local.example.php', 'sys/.htaccess', 'modul/sync_lokal.php', 'modul/data.php', 'tools/cek_koneksi.php');
foreach ($files as $file) {
    if (!is_file($client . '/' . $file)) throw new RuntimeException('File client hilang: ' . $file);
    $target = $source . '/' . $file;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true)) throw new RuntimeException('Folder paket gagal dibuat.');
    if (!copy($client . '/' . $file, $target)) throw new RuntimeException('Salin paket gagal: ' . $file);
}
if (!copy($repo . '/sys/sys_sipp_catalog.php', $source . '/sys/sys_sipp_catalog.php')) throw new RuntimeException('Katalog gagal disalin.');
$files[] = 'sys/sys_sipp_catalog.php'; $files[] = 'README.md';
$output = $repo . '/output/satker-sipp';
if (!is_dir($output) && !mkdir($output, 0775, true)) throw new RuntimeException('Output gagal dibuat.');
$path = $output . '/kasuari_satker_portal_sipp.zip';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('ZIP gagal dibuat.');
foreach ($files as $file) if (!$zip->addFile($source . '/' . $file, $file)) throw new RuntimeException('Isi ZIP gagal ditambah.');
if (!$zip->close()) throw new RuntimeException('ZIP gagal disimpan.');
echo $path . "\n";
