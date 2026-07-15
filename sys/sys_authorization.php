<?php
if (!isset($_SESSION)) {
  session_start();
}

if (!function_exists('kasuari_is_admin')) {
  function kasuari_is_admin()
  {
    if (!isset($_SESSION['group'])) {
      return false;
    }

    $group = strtolower(trim((string) $_SESSION['group']));
    return $group === '0' || $group === 'admin' || $group === 'administrator';
  }
}

if (!function_exists('kasuari_require_admin')) {
  function kasuari_require_admin()
  {
    if (kasuari_is_admin()) {
      return;
    }

    http_response_code(403);
    include __DIR__ . '/../modul/akses_ditolak.php';
    exit;
  }
}

