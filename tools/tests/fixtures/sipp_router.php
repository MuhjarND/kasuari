<?php
if (!getenv('KASUARI_SIPP_TEST_DB')) { http_response_code(403); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($path, '/assets/') === 0) {
    $root = realpath(getenv('KASUARI_SIPP_TEST_REPO') . '/assets');
    $file = realpath($root . '/' . substr($path, 8));
    if (!$file || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) { http_response_code(404); exit; }
    $type = pathinfo($file, PATHINFO_EXTENSION);
    $types = array('css'=>'text/css','js'=>'application/javascript','png'=>'image/png','woff2'=>'font/woff2','svg'=>'image/svg+xml');
    if (!isset($types[$type])) { http_response_code(404); exit; }
    header('Content-Type: ' . $types[$type]); readfile($file); exit;
}
return false;
