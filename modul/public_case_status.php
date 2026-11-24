<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header_remove('Access-Control-Allow-Origin');

function kasuari_case_reply($status, $state)
{
    http_response_code($status);
    echo json_encode(array('version'=>1, 'state'=>$state, 'case'=>null, 'synced_at'=>null));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    kasuari_case_reply(405, 'unavailable');
}
$expected = getenv('KASUARI_CASE_STATUS_TOKEN');
$private = __DIR__.'/../sys/sys_public_case_status.local.php';
if (is_file($private)) {
    $config = include $private;
    if (is_array($config) && isset($config['token'])) $expected = $config['token'];
}
if (!is_string($expected) || strlen($expected) < 48) kasuari_case_reply(503, 'unavailable');
$provided = $_SERVER['HTTP_X_PTSP_KEY'] ?? '';
if (!is_string($provided) || !hash_equals($expected, $provided)) kasuari_case_reply(401, 'unavailable');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once __DIR__.'/../sys/sys_public_case_status.php';
$court = $_POST['court'] ?? null;
$number = kasuari_public_case_number($_POST['case_number'] ?? null);
if (!is_string($court) || trim($court) === '' || strlen($court) > 150 || !kasuari_public_case_number_valid($number)) {
    kasuari_case_reply(422, 'unavailable');
}
try {
    require_once __DIR__.'/../sys/sys_database_config.php';
    $dbConfig = kasuari_database_config();
    $db = new PDO('mysql:host='.$dbConfig['host'].';port='.$dbConfig['port'].';dbname='.$dbConfig['database'].';charset=utf8mb4',
        $dbConfig['username'], $dbConfig['password'], array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_TIMEOUT=>3));
    $db->exec('SET SESSION TRANSACTION READ ONLY');
    $db->beginTransaction();
    $result = kasuari_public_case_lookup($db, $court, $number);
    $db->rollBack();
    http_response_code($result['state'] === 'unavailable' ? 503 : 200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    // Never expose SQL, connection details, request data, or token in the response/log.
    kasuari_case_reply(503, 'unavailable');
}
