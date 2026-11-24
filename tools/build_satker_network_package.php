<?php
if (PHP_SAPI !== 'cli') {
  if (function_exists('http_response_code')) {
    http_response_code(403);
  }
  exit;
}
if (!class_exists('ZipArchive')) {
  fwrite(STDERR, "Ekstensi ZIP PHP belum aktif.\n");
  exit(1);
}
$repo = dirname(__DIR__);
$source = $repo . '/deploy/satker-network';
$output = $repo . '/output/satker-network';
$files = array('sys/sys_transport.php', 'sys/sys_fungsi.php', 'modul/sync_lokal.php',
  'modul/data.php', 'tools/cek_koneksi.php', 'README.md');
foreach ($files as $file) {
  if (!is_file($source . '/' . $file)) {
    fwrite(STDERR, 'File paket tidak ditemukan: ' . $file . "\n");
    exit(1);
  }
}
if (!is_dir($output) && !mkdir($output, 0775, true)) {
  fwrite(STDERR, "Folder keluaran tidak dapat dibuat.\n");
  exit(1);
}
$path = $output . '/kasuari_satker_perbaikan_koneksi.zip';
$archive = new ZipArchive();
if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  fwrite(STDERR, "Paket ZIP tidak dapat dibuat.\n");
  exit(1);
}
foreach ($files as $file) {
  if (!$archive->addFile($source . '/' . $file, $file)) {
    $archive->close();
    fwrite(STDERR, 'Gagal menambahkan ' . $file . "\n");
    exit(1);
  }
}
if (!$archive->close()) {
  fwrite(STDERR, "Paket ZIP tidak dapat disimpan.\n");
  exit(1);
}
echo 'Paket dibuat: ' . $path . "\n";
