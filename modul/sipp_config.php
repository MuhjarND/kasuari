<?php
ob_start();
require_once __DIR__ . '/../sys/sys_session.php';
require_once __DIR__ . '/../sys/sys_authorization.php';
kasuari_require_admin();
require_once __DIR__ . '/../sys/sys_sipp.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf']) || !is_string($_POST['csrf']) ||
    !isset($_SESSION['sipp_csrf']) || !hash_equals($_SESSION['sipp_csrf'], $_POST['csrf'])) {
    http_response_code(403); exit('Sesi formulir tidak valid. Muat ulang halaman SIPP.');
}
$pn = isset($_POST['satker']) ? (int) $_POST['satker'] : 0;
if (!isset(kasuari_sipp_courts()[$pn])) { http_response_code(400); exit('Satker tidak dikenal.'); }
if (!kasuari_sipp_schema($koneksi, true)) { http_response_code(503); exit('Jalankan database/sipp_portal.sql pada database pusat.'); }
try {
    if (($_POST['aksi'] ?? '') === 'token') {
        $token = kasuari_sipp_issue_token($koneksi, $pn, (int) $_SESSION['userid']);
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="sys_sipp.local.php"');
        header('Cache-Control: no-store');
        echo "<?php\nreturn array('token' => " . var_export($token, true) . ");\n";
        exit;
    }
    if (($_POST['aksi'] ?? '') === 'url') {
        $url = isset($_POST['source_url']) && is_string($_POST['source_url']) ? trim($_POST['source_url']) : '';
        $parts = parse_url($url);
        if ($url !== '' && (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true) || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || strlen($url) > 480)) {
            throw new InvalidArgumentException('Alamat SIPP harus URL HTTP/HTTPS tanpa kredensial atau parameter.');
        }
        $existing = kasuari_sipp_select($koneksi, 'SELECT pn_id FROM kasuari_sipp_tokens WHERE pn_id=?', 'i', array($pn));
        if (!$existing) throw new InvalidArgumentException('Buat konfigurasi token satker terlebih dahulu.');
        $stmt = kasuari_sipp_statement($koneksi, 'UPDATE kasuari_sipp_tokens SET source_url=? WHERE pn_id=?', 'si', array($url === '' ? '' : rtrim($url, '/') . '/', $pn));
        mysqli_stmt_close($stmt);
        $_SESSION['sipp_notice'] = 'Alamat SIPP satker disimpan.';
    }
} catch (Exception $error) {
    error_log('Konfigurasi SIPP gagal: ' . $error->getMessage());
    $_SESSION['sipp_error'] = $error instanceof InvalidArgumentException ? $error->getMessage() : 'Konfigurasi belum dapat disimpan. Periksa log pusat.';
}
header('Location: ' . kasuari_sipp_url($pn, 'integrasi'));
exit;
