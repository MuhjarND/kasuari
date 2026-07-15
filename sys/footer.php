      </main>
      <!--end::App Main-->
      <!--begin::Footer-->
      <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline"></div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>KASUARI</strong> - Kanal Asisten Terintegrasi &middot; TIM IT PTA Papua Barat
        <!--end::Copyright-->
      </footer>
      <!--end::Footer-->

      <nav class="kasuari-mobile-nav" aria-label="Navigasi cepat mobile">
        <a href="beranda" class="<?php echo $isMenu('beranda') ? 'active' : ''; ?>">
          <i class="bi bi-grid-1x2" aria-hidden="true"></i>
          <span>Beranda</span>
        </a>
        <a href="register_perkara" class="<?php echo $isMenu(array('register_perkara', 'perkara_detil_banding')) ? 'active' : ''; ?>">
          <i class="bi bi-journal-check" aria-hidden="true"></i>
          <span>Banding</span>
        </a>
        <a href="register_perkara_satker" class="<?php echo $isMenu(array('register_perkara_satker', 'perkara_detil_satker')) ? 'active' : ''; ?>">
          <i class="bi bi-building" aria-hidden="true"></i>
          <span>Satker</span>
        </a>
        <?php if ($isAdministrator) { ?>
          <a href="blangko" class="<?php echo $isMenu(array('blangko', 'jenis_blangko', 'variabel')) ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            <span>Blangko</span>
          </a>
        <?php } else { ?>
          <a href="hatibinwasda" class="<?php echo $isMenu(array('hatibinwasda', 'hatibinwasbid')) ? 'active' : ''; ?>">
            <i class="bi bi-clipboard2-data" aria-hidden="true"></i>
            <span>Pengawasan</span>
          </a>
        <?php } ?>
        <button type="button" data-lte-toggle="sidebar" aria-label="Buka seluruh menu">
          <i class="bi bi-list" aria-hidden="true"></i>
          <span>Menu</span>
        </button>
      </nav>
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)-->
    <!--begin::Required Plugin(Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <!--end::Required Plugin(Bootstrap 5)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <script src="assets/adminlte4.3/js/adminlte.js"></script>
    <script src="assets/js/kasuari-mobile.js?v=<?php echo @filemtime('assets/js/kasuari-mobile.js'); ?>"></script>
    <!--end::Required Plugin(AdminLTE)-->
    <!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function() {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        // Disable OverlayScrollbars on mobile devices to prevent touch interference
        const isMobile = window.innerWidth <= 992;
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined && !isMobile) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <script>
    // Menunggu hingga seluruh halaman (gambar, stylesheet, dll) selesai dimuat
    window.addEventListener('load', function() {
        const preloader = document.getElementById('loader');
        if (preloader) {
            // Memberikan efek fade-out transparan
            preloader.style.opacity = '0';
            preloader.style.visibility = 'hidden';
            
            
        }
    });
</script>
  </body>
</html>
