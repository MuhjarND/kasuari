<?php
include_once("sys/sys_config.php");
include_once("sys/sys_authorization.php");
$currentUserFullname = isset($_SESSION['fullname']) && $_SESSION['fullname'] !== '' ? $_SESSION['fullname'] : 'Pengguna';
$currentUsername     = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'Operator';
$isAdministrator    = kasuari_is_admin();
$initials = strtoupper(function_exists('mb_substr') ? mb_substr($currentUserFullname, 0, 1) : substr($currentUserFullname, 0, 1));
$currentModule = isset($modul) ? $modul : (isset($_GET['modul']) ? $_GET['modul'] : 'beranda');
$isMenu = function ($items) use ($currentModule) {
  return in_array($currentModule, (array) $items, true);
};
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="color-scheme" content="light" />
  <meta name="theme-color" content="#0f1f3d" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="default" />
  <meta name="application-name" content="KASUARI" />
  <meta name="apple-mobile-web-app-title" content="KASUARI" />
  <meta name="description" content="<?php echo htmlspecialchars(@$deskripsi_app ?: 'Sistem manajemen dan layanan digital terpadu untuk mendukung tupoksi Pengadilan Tinggi Agama Papua Barat', ENT_QUOTES, 'UTF-8'); ?>" />
  <title><?php echo @$nama_halaman; ?><?php if(@$nama_halaman && @$nama_app) echo ' - '; ?><?php echo @$nama_app; ?></title>

  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>" />
  <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>" />
  <link rel="shortcut icon" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>" />
  <link rel="apple-touch-icon" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>" />
  <link rel="manifest" href="manifest.webmanifest?v=<?php echo @filemtime('manifest.webmanifest'); ?>" />

  <!-- Force light mode before any paint -->
  <script>
    document.documentElement.setAttribute('data-bs-theme','light');
    document.documentElement.style.colorScheme = 'light';
    // Remove any stored dark preference
    try { localStorage.removeItem('lte-theme'); } catch(e) {}
  </script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" />

  <!-- OverlayScrollbars -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />

  <!-- AdminLTE -->
  <link rel="stylesheet" href="assets/adminlte4.3/css/adminlte.css" />

  <!-- Kasuari UI -->
  <link rel="stylesheet" href="assets/css/kasuari-modern.css?v=<?php echo @filemtime('assets/css/kasuari-modern.css'); ?>" />
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary kasuari-app">

  <!-- Ã¢â€â‚¬Ã¢â€â‚¬ Preloader Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ -->
  <div id="loader"
       class="position-fixed w-100 h-100 top-0 start-0 d-flex flex-column align-items-center justify-content-center bg-white"
       style="z-index:9999;transition:opacity .35s ease,visibility .35s ease;">
    <div class="text-center">
      <div class="spinner-border mb-3"
           role="status"
           style="width:2.6rem;height:2.6rem;border-width:0.2rem;color:#3b82f6;">
        <span class="visually-hidden">Memuat...</span>
      </div>
      <p class="mb-0 fw-semibold text-uppercase"
         style="font-size:0.7rem;letter-spacing:2px;color:#6b7280;font-family:'Inter',sans-serif;">
        Memuat Halaman...
      </p>
    </div>
  </div>

  <!-- Ã¢â€â‚¬Ã¢â€â‚¬ App Wrapper Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ -->
  <div class="app-wrapper">

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
         TOP NAVBAR
         Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid px-3 gap-2">

        <!-- Sidebar toggle -->
        <ul class="navbar-nav me-2">
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-center"
               style="width:36px;height:36px;border-radius:8px;"
               data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
              <i class="bi bi-list" style="font-size:1.25rem;"></i>
            </a>
          </li>
        </ul>

        <!-- Brand name in navbar -->
        <span class="navbar-brand-inline me-auto"
              style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:0.95rem;
                     color:#0f1f3d;display:flex;align-items:center;gap:8px;">
          <img src="assets/logo_teks.png" alt="" class="navbar-brand-logo" aria-hidden="true" />
          <?php echo htmlspecialchars(@$nama_app ?: 'KASUARI', ENT_QUOTES, 'UTF-8'); ?>
        </span>

        <!-- Right side -->
        <ul class="navbar-nav align-items-center gap-1">

          <!-- Fullscreen -->
          <li class="nav-item kasuari-fullscreen-item">
            <a class="nav-link d-flex align-items-center justify-content-center"
               style="width:36px;height:36px;border-radius:8px;"
               href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen">
              <i data-lte-icon="maximize"  class="bi bi-arrows-fullscreen" style="font-size:0.9rem;"></i>
              <i data-lte-icon="minimize"  class="bi bi-fullscreen-exit d-none" style="font-size:0.9rem;"></i>
            </a>
          </li>

          <!-- User dropdown -->
          <li class="nav-item dropdown user-menu">
            <a href="#"
               class="nav-link dropdown-toggle d-flex align-items-center"
               style="gap:9px;padding:5px 10px 5px 6px !important;
                      border:1px solid #e1e8f0;border-radius:999px;height:40px;"
               data-bs-toggle="dropdown">
              <!-- Avatar initials circle -->
              <span style="width:28px;height:28px;border-radius:50%;
                           background:linear-gradient(135deg,#3b82f6,#0f1f3d);
                           display:flex;align-items:center;justify-content:center;
                           color:#fff;font-size:0.7rem;font-weight:700;
                           font-family:'Plus Jakarta Sans',sans-serif;flex-shrink:0;">
                <?php echo $initials; ?>
              </span>
              <span class="d-none d-md-inline"
                    style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;
                           font-size:0.82rem;color:#0c1b35;">
                <?php echo htmlspecialchars($currentUserFullname, ENT_QUOTES, 'UTF-8'); ?>
              </span>
              <i class="bi bi-chevron-down d-none d-md-inline" style="font-size:0.62rem;color:#9ca3af;margin-left:1px;"></i>
            </a>

            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <!-- User header -->
              <li class="user-header" style="background:linear-gradient(135deg,#0f1f3d,#1a3260);">
                <div style="width:56px;height:56px;border-radius:50%;
                            background:linear-gradient(135deg,#3b82f6,#0f1f3d);
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 10px;font-size:1.4rem;font-weight:800;
                            color:#fff;font-family:'Plus Jakarta Sans',sans-serif;
                            border:2px solid rgba(255,255,255,0.2);">
                  <?php echo $initials; ?>
                </div>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;">
                  <?php echo htmlspecialchars($currentUserFullname, ENT_QUOTES, 'UTF-8'); ?>
                  <small style="font-family:'Inter',sans-serif;">
                    <?php echo htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?>
                  </small>
                </p>
              </li>
              <!-- Footer -->
              <li class="user-footer">
                <a href="validateAuth&aksi=keluar"
                   class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2">
                  <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
              </li>
            </ul>
          </li>

        </ul>
      </div>
    </nav>
    <!-- end::Header -->

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
         SIDEBAR
         Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <aside class="app-sidebar" data-bs-theme="dark">

      <!-- Brand -->
      <div class="sidebar-brand sidebar-brand-logo-only">
        <a href="beranda" class="brand-link sidebar-logo-only" aria-label="KASUARI - Beranda">
          <img src="assets/logo_icon.png?v=<?php echo @filemtime('assets/logo_icon.png'); ?>" alt="KASUARI - Kanal Asisten Terintegrasi" class="brand-image sidebar-brand-logo" />
        </a>
      </div>

      <!-- Nav -->
      <div class="sidebar-wrapper">
        <nav aria-label="Navigasi utama">
          <ul class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview" data-accordion="false"
              id="navigation" style="padding:0 12px;">

            <!-- Dashboard -->
            <li class="nav-item">
              <a href="beranda" class="nav-link <?php echo $isMenu('beranda') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-grid-1x2"></i>
                <p>Dashboard</p>
              </a>
            </li>

            <!-- Register Perkara -->
            <li class="nav-item <?php echo $isMenu(['register_perkara','register_perkara_satker','register_perkara_belum_dikirim','register_perkara_dicabut','perkara_detil_banding','perkara_detil_satker']) ? 'menu-open' : ''; ?>">
              <a href="#" class="nav-link <?php echo $isMenu(['register_perkara','register_perkara_satker','register_perkara_belum_dikirim','register_perkara_dicabut','perkara_detil_banding','perkara_detil_satker']) ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-folder2-open"></i>
                <p>Register Perkara
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="register_perkara" class="nav-link <?php echo $isMenu(['register_perkara','perkara_detil_banding']) ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Perkara Banding</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="register_perkara_satker" class="nav-link <?php echo $isMenu(['register_perkara_satker','perkara_detil_satker']) ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Perkara Satker</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="register_perkara_belum_dikirim" class="nav-link <?php echo $isMenu('register_perkara_belum_dikirim') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Belum Dikirim</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="register_perkara_dicabut" class="nav-link <?php echo $isMenu('register_perkara_dicabut') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Dicabut</p>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Monitoring Sinkronisasi -->
            <li class="nav-item">
              <a href="monitoring_sinkronisasi" class="nav-link <?php echo $isMenu('monitoring_sinkronisasi') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-arrow-repeat"></i>
                <p>Monitoring Sinkronisasi</p>
              </a>
            </li>

            <!-- Hatibinwasda -->
            <li class="nav-item">
              <a href="hatibinwasda" class="nav-link <?php echo $isMenu('hatibinwasda') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-clipboard2-data"></i>
                <p>Hatibinwasda</p>
              </a>
            </li>

            <!-- Hatibinwasbid -->
            <li class="nav-item">
              <a href="hatibinwasbid" class="nav-link <?php echo $isMenu('hatibinwasbid') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-clipboard2-pulse"></i>
                <p>Hatibinwasbid</p>
              </a>
            </li>

            <!-- CCTV -->
            <li class="nav-item">
              <a href="cctv" class="nav-link <?php echo $isMenu('cctv') ? 'active' : ''; ?>" target="_blank">
                <i class="nav-icon bi bi-camera-video"></i>
                <p>CCTV Online</p>
              </a>
            </li>

            <?php if ($isAdministrator) { ?>
            <!-- Divider -->
            <li class="nav-header"
                style="font-size:0.62rem;letter-spacing:.1em;color:rgba(255,255,255,.25);
                       padding:14px 12px 4px;text-transform:uppercase;font-weight:700;">
              Pengaturan
            </li>

            <!-- Pengaturan group -->
            <li class="nav-item <?php echo $isMenu(['pengguna','blangko','jenis_blangko','variabel','identitas_satker']) ? 'menu-open' : ''; ?>">
              <a href="#" class="nav-link <?php echo $isMenu(['pengguna','blangko','jenis_blangko','variabel','identitas_satker']) ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-sliders"></i>
                <p>Kelola
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="pengguna" class="nav-link <?php echo $isMenu('pengguna') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Pengguna</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="blangko" class="nav-link <?php echo $isMenu('blangko') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Blangko</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="jenis_blangko" class="nav-link <?php echo $isMenu('jenis_blangko') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Jenis Blangko</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="variabel" class="nav-link <?php echo $isMenu('variabel') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Variabel</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="identitas_satker" class="nav-link <?php echo $isMenu('identitas_satker') ? 'active' : ''; ?>">
                    <i class="nav-icon bi bi-dot"></i>
                    <p>Identitas Satker</p>
                  </a>
                </li>
              </ul>
            </li>
            <?php } ?>

          </ul>

          <!-- Bottom CTA -->
          <div class="sidebar-cta" style="padding:12px 12px 4px;margin-top:8px;
               border-top:1px solid rgba(255,255,255,.07);">
            <a href="https://sipp-banding.mahkamahagung.go.id/" target="_blank"
               style="display:flex;align-items:center;justify-content:center;gap:7px;
                      padding:9px 14px;border-radius:10px;
                      background:rgba(59,130,246,0.12);
                      border:1px solid rgba(59,130,246,0.25);
                      color:rgba(255,255,255,0.72);font-size:0.8rem;font-weight:500;
                      text-decoration:none;transition:all .2s ease;
                      font-family:'Inter',sans-serif;">
              <i class="bi bi-box-arrow-up-right"></i> Buka SIPP Banding
            </a>
          </div>

          <div class="sb-user-card">
            <div class="sb-avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="min-width:0;">
              <div class="sb-user-name text-truncate"><?php echo htmlspecialchars($currentUserFullname, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="sb-user-role text-truncate"><?php echo htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <span class="sb-active-dot" title="Aktif"></span>
          </div>

        </nav>
      </div>
    </aside>
    <!-- end::Sidebar -->

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
         APP MAIN (content injected by each module)
         Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <main class="app-main">
