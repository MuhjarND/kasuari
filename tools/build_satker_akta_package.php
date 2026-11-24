<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!class_exists('ZipArchive')) { fwrite(STDERR, "Ekstensi ZIP PHP belum aktif.\n"); exit(1); }
$repo = dirname(__DIR__);
$source = $repo . '/deploy/satker-akta-cerai';
$files = array('sys/sys_akta_cerai.php', 'sys/sys_fungsi.php', 'sys/sys_transport.php',
    'modul/sync_lokal.php', 'modul/data.php', 'tools/cek_koneksi.php', 'README.md');
$output = $repo . '/output/satker-akta-cerai';
if (!is_dir($output) && !mkdir($output, 0775, true)) throw new RuntimeException('Folder output gagal dibuat.');
$path = $output . '/kasuari_satker_status_akta_cerai.zip';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('ZIP gagal dibuat.');
foreach ($files as $file) {
    if (!is_file($source . '/' . $file) || !$zip->addFile($source . '/' . $file, $file)) {
        $zip->close();
        throw new RuntimeException('File paket tidak tersedia: ' . $file);
    }
}
if (!$zip->close()) throw new RuntimeException('ZIP gagal disimpan.');
echo $path . "\n";
