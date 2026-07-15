<?php
if (!isset($_SESSION)) {
  session_start();
}
include_once("sys/sys_config.php");

$penanda = base64_encode(md5("halaman_login" . date("d-m-Y H:i:s")));
$_SESSION['login_cek'] = $penanda;
$pesan_error = isset($_SESSION['pesan_error_login']) ? $_SESSION['pesan_error_login'] : "";
unset($_SESSION['pesan_error_login']);
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="light" />
  <meta name="theme-color" content="#214a9a" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="application-name" content="KASUARI" />
  <meta name="apple-mobile-web-app-title" content="KASUARI" />
  <title>Masuk - <?php echo htmlspecialchars(@$nama_app ?: 'KASUARI', ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>" />
  <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>" />
  <link rel="shortcut icon" href="assets/icons/logo-icon-32.png?v=<?php echo @filemtime('assets/icons/logo-icon-32.png'); ?>" />
  <link rel="apple-touch-icon" sizes="192x192" href="assets/icons/logo-icon-192.png?v=<?php echo @filemtime('assets/icons/logo-icon-192.png'); ?>" />
  <link rel="manifest" href="manifest.webmanifest?v=<?php echo @filemtime('manifest.webmanifest'); ?>" />
  <script>
    document.documentElement.setAttribute('data-bs-theme','light');
    document.documentElement.style.colorScheme='light';
    try { localStorage.removeItem('lte-theme'); } catch(e) {}
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="assets/adminlte4.3/css/adminlte.css" />
  <link rel="stylesheet" href="assets/css/kasuari-modern.css" />

  <style>
    html, body { min-height: 100%; }
  </style>
</head>
<body class="login-page kasuari-login kasuari-login-split">

  <main class="kasuari-login-shell">
    <section class="kasuari-login-showcase" aria-label="Informasi aplikasi KASUARI">
      <div class="kasuari-login-showcase-inner">
        <div class="kasuari-login-logo-badge kasuari-login-logo-main">
          <img src="assets/logo_icon.png" alt="KASUARI - Kanal Asisten Terintegrasi" />
        </div>
        <span class="kasuari-login-divider" aria-hidden="true"></span>
        <p><?php echo htmlspecialchars(@$deskripsi_app ?: 'Sistem manajemen dan layanan digital terpadu untuk mendukung tupoksi Pengadilan Tinggi Agama Papua Barat', ENT_QUOTES, 'UTF-8'); ?>.</p>

        <div class="kasuari-login-feature-grid" aria-label="Fitur utama">
          <div class="kasuari-login-feature">
            <i class="bi bi-journal-check"></i>
            <span>Register Perkara</span>
          </div>
          <div class="kasuari-login-feature">
            <i class="bi bi-arrow-repeat"></i>
            <span>Sinkronisasi Satker</span>
          </div>
          <div class="kasuari-login-feature">
            <i class="bi bi-bar-chart-line"></i>
            <span>Monitoring Data</span>
          </div>
        </div>
      </div>
    </section>

    <section class="kasuari-login-panel" aria-label="Form masuk">
      <div class="kasuari-login-form-card">
        <div class="kasuari-login-heading">
          <span class="kasuari-login-heading-icon"><i class="bi bi-stars"></i></span>
          <h2>Selamat Datang</h2>
        </div>
        <p class="login-sub">Masuk ke akun Anda untuk melanjutkan.</p>

        <?php if ($pesan_error !== ""): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <span><?php echo nl2br(htmlspecialchars(str_replace('<br>', "\n", $pesan_error), ENT_QUOTES, 'UTF-8')); ?></span>
          </div>
        <?php endif; ?>

        <form method="post" action="validateAuth" novalidate>
          <div class="kasuari-login-field mb-3">
            <label for="username" class="form-label">Nama Pengguna</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-person-badge"></i>
              </span>
              <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                placeholder="Masukkan nama pengguna"
                autocomplete="username"
                required
              />
            </div>
          </div>

          <div class="kasuari-login-field mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
              />
            </div>
          </div>

          <div class="kasuari-login-options">
            <label class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" value="1" />
              <span class="form-check-label">Ingat Saya</span>
            </label>
          </div>

          <input type="hidden" name="crsf" value="<?php echo htmlspecialchars($penanda, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="aksi" value="<?php echo base64_encode('login'); ?>" />

          <button type="submit" class="btn btn-primary kasuari-login-submit w-100">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Masuk</span>
          </button>
        </form>

        <p class="login-footer-note">
          &copy; <?php echo date('Y'); ?> &middot; TIM IT PTA Papua Barat
        </p>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
  <script src="assets/adminlte4.3/js/adminlte.js"></script>
  <script>
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
    window.addEventListener('load', function () {
      window.scrollTo(0, 0);
    });
  </script>
</body>
</html>
