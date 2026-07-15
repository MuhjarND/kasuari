<nav class="kasuari-legacy-mobile-nav" aria-label="Navigasi administrasi mobile">
  <a href="beranda"><i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Beranda</span></a>
  <a href="pengguna"><i class="bi bi-people" aria-hidden="true"></i><span>Pengguna</span></a>
  <a href="blangko"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Blangko</span></a>
  <a href="jenis_blangko"><i class="bi bi-tags" aria-hidden="true"></i><span>Jenis</span></a>
  <a href="variabel"><i class="bi bi-braces" aria-hidden="true"></i><span>Variabel</span></a>
</nav>
<script src="assets/js/kasuari-mobile.js?v=<?php echo @filemtime('assets/js/kasuari-mobile.js'); ?>"></script>
<script>document.getElementById("loader").style.display="none";</script>
</body>
<?php mysqli_close($koneksi);?>
</html>
