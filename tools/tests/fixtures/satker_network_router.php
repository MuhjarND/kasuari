<?php
$file = getenv('KASUARI_NETWORK_TEST_STATE');
$counts = is_file($file) ? json_decode(file_get_contents($file), true) : array();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$counts[$path] = isset($counts[$path]) ? $counts[$path] + 1 : 1;
file_put_contents($file, json_encode($counts));
if ($path === '/forbidden') {
  http_response_code(403);
  exit('blocked');
}
if ($path === '/redirect') {
  header('Location: /echo', true, 302);
  exit;
}
if ($path === '/retry' && $counts[$path] === 1) {
  http_response_code(503);
  exit('temporary');
}
header('Content-Type: application/json');
echo json_encode(array('status' => 'ok', 'protocol' => 2,
  'method' => $_SERVER['REQUEST_METHOD'], 'payload' => $_POST));
