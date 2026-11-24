<?php
ob_start();
require_once __DIR__ . '/../sys/sys_session.php';
require_once __DIR__ . '/../sys/sys_authorization.php';
kasuari_require_admin();
require_once __DIR__ . '/../sys/sys_sipp_view.php';
$pn = isset($_GET['satker']) && is_scalar($_GET['satker']) ? (int) $_GET['satker'] : 439;
if (!isset(kasuari_sipp_courts()[$pn])) { http_response_code(404); exit('Satker tidak dikenal.'); }
$section = isset($_GET['bagian']) && is_string($_GET['bagian']) ? $_GET['bagian'] : '';
$filter = array();
foreach (array('q', 'from', 'to', 'case_id', 'group') as $field) $filter[$field] = isset($_GET[$field]) && is_string($_GET[$field]) ? trim($_GET[$field]) : '';
$filter['q'] = mb_substr($filter['q'], 0, 150);
foreach (array('from', 'to') as $field) {
    if ($filter[$field] !== '' && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $filter[$field], $parts) || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]))) {
        http_response_code(400); exit('Tanggal filter tidak valid.');
    }
}
if ($filter['from'] && $filter['to'] && $filter['from'] > $filter['to']) { http_response_code(400); exit('Tanggal dari harus sebelum tanggal sampai.'); }
if ($filter['case_id'] !== '' && !preg_match('/^[0-9]{1,20}$/D', $filter['case_id'])) { http_response_code(400); exit('ID perkara tidak valid.'); }
$filter['size'] = isset($_GET['size']) && is_scalar($_GET['size']) ? (int) $_GET['size'] : 25;
if (!in_array($filter['size'], array(25,50,100), true)) $filter['size'] = 25;
$filter['page'] = isset($_GET['page']) && is_scalar($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$key = isset($_GET['key']) && is_string($_GET['key']) ? $_GET['key'] : '';
if ($key !== '' && !preg_match('/^[a-f0-9]{64}$/D', $key)) { http_response_code(400); exit('Identitas record tidak valid.'); }
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!isset(kasuari_sipp_catalog()[$section]) || !kasuari_sipp_schema($koneksi)) { http_response_code(404); exit('Modul belum tersedia.'); }
    $spec = kasuari_sipp_catalog()[$section];
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sipp_' . $pn . '_' . $section . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w'); echo "\xef\xbb\xbf";
    fputcsv($out, array_values($spec['fields']));
    $filter['size'] = 100; $filter['page'] = 1;
    do {
        $page = kasuari_sipp_page($koneksi, $pn, $section, $filter);
        foreach ($page['rows'] as $row) {
            $line = array();
            foreach ($spec['fields'] as $field => $label) {
                $value = kasuari_sipp_value($row['data'][$field] ?? null, $field);
                if (preg_match('/^[\s]*[=+@-]/u', $value)) $value = "'" . $value;
                $line[] = $value;
            }
            fputcsv($out, $line);
        }
        $filter['page']++;
    } while ($filter['page'] <= $page['pages']);
    fclose($out); exit;
}
if (empty($_SESSION['sipp_csrf'])) $_SESSION['sipp_csrf'] = bin2hex(random_bytes(24));
$nama_halaman = 'SIPP ' . kasuari_sipp_courts()[$pn];
include_once __DIR__ . '/../sys/header.php';
try { kasuari_sipp_render($koneksi, $pn, $section, $filter, $key); }
catch (Exception $error) {
    error_log('Portal SIPP gagal dimuat: ' . $error->getMessage());
    echo '<div class="app-content"><div class="container-fluid"><div class="alert alert-danger">Data SIPP belum dapat dimuat. Periksa migrasi database dan pengiriman terakhir satker.</div></div></div>';
}
echo '<script src="assets/js/kasuari-sipp.js?v=' . (int) filemtime(__DIR__ . '/../assets/js/kasuari-sipp.js') . '" defer></script>';
include_once __DIR__ . '/../sys/footer.php';
