<?php
include_once("sys/sys_session.php");
$nama_halaman = "Akses Ditolak";
include_once("sys/header.php");
?>

<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-access-denied">
      <div class="kasuari-access-icon" aria-hidden="true">
        <i class="bi bi-shield-lock"></i>
      </div>
      <div>
        <span class="kasuari-access-label">Akses terbatas</span>
        <h1>Halaman ini hanya untuk Administrator</h1>
        <p>Akun Anda tetap dapat menggunakan menu operasional, tetapi tidak memiliki izin untuk membuka pengaturan aplikasi.</p>
        <a href="beranda" class="btn btn-primary">
          <i class="bi bi-arrow-left" aria-hidden="true"></i>
          Kembali ke Dashboard
        </a>
      </div>
    </div>
  </div>
</div>

<?php include_once("sys/footer.php"); ?>
