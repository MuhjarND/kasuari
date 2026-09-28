<?php
include_once(__DIR__ . '/../sys/sys_session.php');
include_once(__DIR__ . '/../sys/sys_authorization.php');
kasuari_require_admin();
$nama_halaman = 'Preview Laporan Perkara';
include_once(__DIR__ . '/../sys/header.php');

$type = isset($_GET['jenis']) ? (int) $_GET['jenis'] : 1;
$type = ($type >= 1 && $type <= 4) ? $type : 1;
$mode = isset($_GET['mode']) && $_GET['mode'] === 'rentang' ? 'rentang' : 'bulanan';
$month = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$year = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
$month = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
$year = ($year >= 2000 && $year <= 2100) ? $year : (int) date('Y');
$dari = isset($_GET['dari']) ? preg_replace('/[^0-9-]/', '', (string) $_GET['dari']) : date('Y-m-01');
$sampai = isset($_GET['sampai']) ? preg_replace('/[^0-9-]/', '', (string) $_GET['sampai']) : date('Y-m-d');
$months = array(1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember');
$reportNames = array(
  1 => 'Laporan Perkara Banding',
  2 => 'Laporan Pengiriman Salinan Putusan',
  3 => 'Laporan Publikasi Putusan',
  4 => 'Register Perkara PTA Papua Barat',
);
$query = array('jenis' => $type, 'mode' => $mode, 'bulan' => $month, 'tahun' => $year);
if ($mode === 'rentang') { $query['dari'] = $dari; $query['sampai'] = $sampai; }
$reportQuery = array_merge(array('modul' => 'laporan_perkara_banding'), $query);
$pdfUrl = 'index.php?' . http_build_query($reportQuery);
$downloadUrl = $pdfUrl . '&download=1';
$periodLabel = $mode === 'rentang' ? $dari . ' sampai ' . $sampai : $months[$month] . ' ' . $year;
?>
<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-panel kasuari-report-preview-panel">
      <div class="kasuari-toolbar">
        <div>
          <div class="kasuari-section-kicker">PRATINJAU DOKUMEN</div>
          <h3 class="mb-1 fw-bold fs-5"><?php echo htmlspecialchars($reportNames[$type], ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="text-secondary mb-0">Periode: <?php echo htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
          <a href="register_perkara" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
          <a href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><i class="bi bi-download me-1"></i>Unduh PDF</a>
        </div>
      </div>
      <div class="kasuari-report-preview-frame-wrap">
        <div class="kasuari-report-preview-loading" id="reportPreviewLoading">
          <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
          <span>Menyiapkan preview PDF...</span>
        </div>
        <div class="alert alert-danger d-none" id="reportPreviewError" role="alert">
          <strong>Preview PDF belum dapat ditampilkan.</strong>
          <span id="reportPreviewErrorText">Periksa data laporan lalu coba kembali.</span>
        </div>
        <iframe class="kasuari-report-preview-frame d-none" id="reportPreviewFrame" data-pdf-url="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Preview PDF laporan"></iframe>
      </div>
    </div>
  </div>
</div>
<script>
  (function () {
    var frame = document.getElementById('reportPreviewFrame');
    var loading = document.getElementById('reportPreviewLoading');
    var errorBox = document.getElementById('reportPreviewError');
    var errorText = document.getElementById('reportPreviewErrorText');
    fetch(frame.getAttribute('data-pdf-url'), { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) {
        if (!response.ok) return response.text().then(function (text) {
          throw new Error('HTTP ' + response.status + ' - ' + (text || 'Server menolak permintaan laporan.'));
        });
        var contentType = (response.headers.get('content-type') || '').toLowerCase();
        return response.blob().then(function (blob) {
          return blob.slice(0, 5).text().then(function (signature) {
            if (signature !== '%PDF-') {
              return blob.text().then(function (text) {
                throw new Error('Server tidak mengembalikan PDF (Content-Type: ' + (contentType || 'tidak tersedia') + '). ' + text);
              });
            }
            return blob;
          });
        });
      })
      .then(function (blob) {
        if (!blob || blob.size < 20) throw new Error('File PDF kosong.');
        return blob;
      })
      .then(function (blob) {
        frame.src = URL.createObjectURL(blob);
        frame.classList.remove('d-none');
        loading.classList.add('d-none');
      })
      .catch(function (error) {
        loading.classList.add('d-none');
        errorText.textContent = error && error.message ? error.message.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 500) : 'Terjadi kesalahan saat membuat PDF.';
        errorBox.classList.remove('d-none');
      });
  }());
</script>
<?php include_once(__DIR__ . '/../sys/footer.php'); ?>
