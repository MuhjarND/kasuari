<?php
include_once("sys/sys_config.php");
?>
<!DOCTYPE html>
<html lang="id">
<title><?php echo @$nama_halaman; ?><?php if(@$nama_halaman && @$nama_app) echo ' · '; ?><?php echo @$nama_app; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#214a9a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="application-name" content="KASUARI">
<meta name="apple-mobile-web-app-title" content="KASUARI">
<link rel="icon" type="image/png" sizes="32x32" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>">
<link rel="icon" type="image/png" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>">
<link rel="shortcut icon" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>">
<link rel="apple-touch-icon" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>">
<link rel="manifest" href="manifest.webmanifest?v=<?php echo @filemtime('manifest.webmanifest'); ?>">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

<!-- W3.CSS -->
<link rel="stylesheet" href="assets/css/w3.css">
<link rel="stylesheet" href="assets/css/w3-theme-teal.css">
<link rel="stylesheet" href="assets/css/costum.css">
<link rel="stylesheet" href="assets/css/inc/ionicons/css/ionicons.min.css">

<!-- Kasuari custom (overrides W3 styles) -->
<link rel="stylesheet" href="assets/css/kasuari-modern.css">

<style>
  /* ── Legacy sys_header.php mini-styles ───────────────────── */
  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #f0f4fa;
    margin: 0;
    padding: 0;
  }

  /* Top bar (minimal, just brand + back button) */
  .ks-legacy-topbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background: #0f1f3d;
    height: 52px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(15,31,61,0.18);
  }

  .ks-legacy-topbar .brand {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.2px;
  }

  .ks-legacy-topbar .brand-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #3b82f6;
    display: inline-block;
  }

  .ks-legacy-topbar .back-btn {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 8px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.80);
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    transition: all .18s ease;
    font-family: 'Inter', sans-serif;
  }

  .ks-legacy-topbar .back-btn:hover {
    background: rgba(255,255,255,0.14);
    color: #fff;
  }

  /* Page wrap */
  #AppContent {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 16px 40px;
    background: #f0f4fa;
    min-height: calc(100vh - 52px);
  }

  /* Content card wrapping w3 content */
  .w3-container.w3-margin-bottom {
    background: #fff;
    border: 1px solid #e1e8f0;
    border-radius: 14px;
    padding: 20px 20px 16px !important;
    box-shadow: 0 2px 6px rgba(15,31,61,0.06);
    margin-bottom: 16px !important;
  }

  .w3-row.w3-margin {
    background: #fff;
    border: 1px solid #e1e8f0;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 2px 6px rgba(15,31,61,0.06);
  }
</style>

<body class="kasuari-legacy">
<!-- Preloader -->
<div id="loader" class="loading">Loading…</div>

<!-- Top bar -->
<div class="ks-legacy-topbar">
  <a href="beranda" class="brand">
    <img src="assets/logo_teks.png" alt="" class="ks-legacy-brand-logo" aria-hidden="true">
    <?php echo htmlspecialchars(@$nama_app ?: 'KASUARI', ENT_QUOTES, 'UTF-8'); ?>
  </a>
  <a href="beranda" class="back-btn">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<script type="text/javascript">
  function openNav() {
    var x = document.getElementById("navDemo");
    if (x.className.indexOf("w3-show") == -1) {
      x.className += " w3-show";
    } else {
      x.className = x.className.replace(" w3-show", "");
    }
  }
</script>
