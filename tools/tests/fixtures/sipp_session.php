<?php
if (!isset($_SESSION)) session_start();
if (!getenv('KASUARI_SIPP_TEST_DB')) { http_response_code(403); exit; }
require_once __DIR__ . '/sys_config.php';
$_SESSION['userid'] = 1;
$_SESSION['group'] = isset($_GET['test_user']) ? '1' : 'administrator';
$_SESSION['sipp_csrf'] = 'test-csrf';
