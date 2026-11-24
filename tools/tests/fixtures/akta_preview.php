<?php
// Preview fixture: no production DB/session is loaded.
if (PHP_SAPI !== 'cli-server' || getenv('KASUARI_AKTA_PREVIEW') !== '1'
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', array('127.0.0.1', '::1'), true)) { http_response_code(403); exit; }
require_once __DIR__ . '/../../../sys/sys_authorization.php';
require_once __DIR__ . '/../../../sys/sys_fungsi.php';
require_once __DIR__ . '/../../../sys/sys_akta_cerai.php';
$_SESSION['group'] = isset($_GET['nonadmin']) ? '1' : 'administrator';
$case = array('pn_id' => 439, 'perkara_id' => 10, 'jenis_perkara_nama' => 'Cerai Gugat');
$map = array('439:10' => array('state' => 'recorded', 'nomor_akta_cerai' => '123/AC/2026/PA.Test',
    'tgl_akta_cerai' => '2026-10-31', 'tgl_penyerahan_akta_cerai' => '2026-11-01',
    'tgl_penyerahan_akta_cerai_pihak2' => null, 'pihak2_tersedia' => 1, 'synced_at' => '2026-11-01 15:00:00'));
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akta status fixture</title>
<link rel="stylesheet" href="/assets/adminlte4.3/css/adminlte.css">
<link rel="stylesheet" href="/assets/css/kasuari-modern.css">
</head><body><main class="container-fluid py-3">
<h1 class="fs-5">Status Perkara</h1><div class="row g-3"><div class="col-lg-4">
<div class="ks-detail-card"><div class="ks-detail-card-header"><h3>Status Perkara</h3></div><div class="ks-detail-card-body">
<span class="ks-status-pill putusan">Putusan</span>
<?php echo kasuari_akta_html($case, $map, true); ?>
</div></div></div></div><div class="kasuari-panel kasuari-panel-table mt-3">
<table class="table ks-satker-table"><thead><tr><th>No</th><th>Nomor Perkara</th><th>Satker</th><th>Jenis Perkara</th><th>Tanggal Daftar</th><th>Tanggal Putusan</th><th>Tahapan Terakhir</th><th>Proses Terakhir</th><th>Aksi</th></tr></thead><tbody>
<?php foreach (array('recorded', 'not_recorded', 'unavailable') as $state):
    $map['439:10']['state'] = $state;
    if ($state !== 'recorded') { $map['439:10']['nomor_akta_cerai'] = ''; $map['439:10']['tgl_akta_cerai'] = null; $map['439:10']['tgl_penyerahan_akta_cerai'] = null; }
?><tr><td>1</td><td>123/Pdt.G/2026/PA.Test</td><td>PA SORONG</td><td>Cerai Gugat</td><td>1 Oktober 2026</td><td>31 Oktober 2026</td><td>Putusan</td><td><span class="ks-status-pill putusan">Putusan</span><?php echo kasuari_akta_html($case, $map); ?></td><td>-</td></tr><?php endforeach; ?>
</tbody></table></div></main></body></html>
