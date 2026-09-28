<?php
include_once("sys/sys_session.php");
$nama_halaman = "Register Perkara Banding";
include_once("sys/header.php");

$totalBanding = 0;
$totalBandingResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM perkara_banding WHERE tanggal_pendaftaran_banding IS NOT NULL");
if ($totalBandingResult) {
  $totalBandingRow = mysqli_fetch_assoc($totalBandingResult);
  $totalBanding = (int) ($totalBandingRow['total'] ?? 0);
} else {
  error_log('KASUARI register_perkara count failed: ' . mysqli_error($koneksi));
}

function register_banding_status_class($text) {
  $text = strtolower(trim((string) $text));
  if ($text === '') return 'kosong';
  if (strpos($text, 'minutasi') !== false) return 'minutasi';
  if (strpos($text, 'pemberitahuan') !== false) return 'pemberitahuan';
  if (strpos($text, 'putus') !== false) return 'putusan';
  if (strpos($text, 'cabut') !== false) return 'dicabut';
  return 'proses';
}
?>
<link rel="stylesheet" type="text/css" href="assets/plugins/jstable/jstable.css" />
<script src="assets/plugins/jstable/jstable.min.js" type="text/javascript"></script>

<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-panel kasuari-banding-register">
      <div class="kasuari-toolbar">
        <div>
          <h3 class="mb-1 fw-bold fs-5">Perkara Banding</h3>
          <p class="text-secondary mb-0">Gunakan pencarian tabel untuk nomor perkara, satker pengaju, atau status banding.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
          <?php if (!empty($isAdministrator)): ?>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLaporanBulanan">
            <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>
            Laporan Bulanan
          </button>
          <?php endif; ?>
          <a href="register_perkara_satker" class="btn btn-outline-primary">
            <i class="bi bi-folder2-open me-1" aria-hidden="true"></i>
            Perkara Satker
          </a>
        </div>
      </div>

      <div id="results_content" class="kasuari-table-wrap table-responsive">
        <?php
        $table = '<table class="table table-striped table-hover align-middle ks-banding-table" id="datane_result"><thead><tr>
          <th>No</th>
          <th>Nomor Perkara Banding</th>
          <th>PA Pengaju</th>
          <th>Nomor Perkara Tk. 1</th>
          <th>Tanggal Pendaftaran</th>
          <th>Tanggal Putusan</th>
          <th>Status Banding</th>
          <th>Aksi</th>
          </tr></thead><tbody>';

        $sql = "SELECT
                  perkara_banding.id,
                  perkara_banding.nomor_perkara_banding,
                  perkara_banding.nomor_perkara_pn,
                  perkara_banding.tanggal_pendaftaran_banding AS tanggalpendaftaranbanding,
                  perkara_banding.putusan_banding AS putusanbanding,
                  perkara_banding.tgl_kirim_salinan_putusan,
                  perkara_banding.tgl_minutasi,
                  perkara_banding.minutasi_banding,
                  perkara_banding.pemberitahuan_putusan_banding,
                  perkara_banding.tgl_pemberitahuan_putusan,
                  perkara_banding.tanggal_cabut,
                  perkara_banding.status_banding_text,
                  pengadilan_agama.nama AS pengaju
                FROM perkara_banding
                LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara_banding.pn_id
                WHERE perkara_banding.tanggal_pendaftaran_banding IS NOT NULL
                ORDER BY perkara_banding.tanggal_pendaftaran_banding DESC, perkara_banding.nomor_urut_register DESC LIMIT 25";
        $query = mysqli_query($koneksi, $sql);
        $no = 0;
        if (!$query) {
          error_log('KASUARI register_perkara list failed: ' . mysqli_error($koneksi));
          $table .= '<tr><td class="kasuari-empty-state" colspan="8">Data perkara banding belum dapat dimuat. Periksa struktur database server.</td></tr>';
        }
        while ($query && ($data = mysqli_fetch_assoc($query))) {
          $no++;
          $statusBandingText = kasuari_status_banding_tampil($data);
          $statusBanding = htmlspecialchars($statusBandingText, ENT_QUOTES, 'UTF-8');
          $statusBandingClass = register_banding_status_class($statusBandingText);
          $nomorBanding = htmlspecialchars((string) ($data["nomor_perkara_banding"] ?? ""), ENT_QUOTES, 'UTF-8');
          $satker = htmlspecialchars(str_replace("PENGADILAN AGAMA", "PA", (string) ($data["pengaju"] ?? "")), ENT_QUOTES, 'UTF-8');
          $nomorPerkaraPn = htmlspecialchars((string) ($data["nomor_perkara_pn"] ?? ""), ENT_QUOTES, 'UTF-8');
          $tanggalPendaftaran = htmlspecialchars(kasuari_tanggal_indonesia($data["tanggalpendaftaranbanding"] ?? ""), ENT_QUOTES, 'UTF-8');
          $tanggalPutusan = htmlspecialchars(kasuari_tanggal_indonesia($data["putusanbanding"] ?? ""), ENT_QUOTES, 'UTF-8');
          $table .= '<tr>
            <td class="text-center">' . $no . '</td>
            <td class="fw-semibold">' . $nomorBanding . '</td>
            <td>' . $satker . '</td>
            <td>' . $nomorPerkaraPn . '</td>
            <td>' . ($tanggalPendaftaran !== '' ? $tanggalPendaftaran : '-') . '</td>
            <td>' . ($tanggalPutusan !== '' ? $tanggalPutusan : '-') . '</td>
            <td><span class="ks-banding-status ' . $statusBandingClass . '">' . $statusBanding . '</span></td>
            <td class="text-center"><a class="kasuari-action-link" href="perkara_detil_banding&id=' . $data["id"] . '" title="Detail Perkara"><i class="bi bi-eye" aria-hidden="true"></i> Detail</a></td>
          </tr>';
        }
        if ($query && $no == 0) {
          $table .= '<tr><td class="kasuari-empty-state" colspan="8">Tidak ada data perkara banding.</td></tr>';
        }
        $table .= "</tbody></table>";
        echo $table;
        ?>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  var table = new JSTable("#datane_result", {
    serverSide: true,
    deferLoading: <?php echo $totalBanding ?>,
    ajax: "register_perkara_data",
    columns: [
      { select: 4, sort: "desc" }
    ]
  });
</script>

<?php if (!empty($isAdministrator)): ?>
<div class="modal fade" id="modalLaporanBulanan" tabindex="-1" aria-labelledby="modalLaporanBulananLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content kasuari-report-modal">
      <div class="modal-header">
        <div>
          <div class="kasuari-section-kicker">CETAK DOKUMEN</div>
          <h5 class="modal-title" id="modalLaporanBulananLabel">Laporan Perkara Bulanan</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary mb-3">Pilih bulan dan tahun, kemudian pilih laporan yang akan dicetak dalam format PDF.</p>
        <div class="kasuari-report-mode mb-3" role="group" aria-label="Mode periode laporan">
          <input type="radio" class="btn-check" name="laporanMode" id="laporanModeBulanan" value="bulanan" checked>
          <label class="btn btn-outline-primary" for="laporanModeBulanan"><i class="bi bi-calendar3 me-1"></i>Per Bulan</label>
          <input type="radio" class="btn-check" name="laporanMode" id="laporanModeRentang" value="rentang">
          <label class="btn btn-outline-primary" for="laporanModeRentang"><i class="bi bi-calendar-range me-1"></i>Rentang Tanggal</label>
        </div>
        <div class="row g-3 mb-4" id="laporanBulananFields">
          <div class="col-sm-7">
            <label for="laporanBulan" class="form-label">Bulan laporan</label>
            <select class="form-select" id="laporanBulan">
              <?php foreach (array(1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember') as $bulanNo => $bulanNama): ?>
                <option value="<?php echo $bulanNo; ?>" <?php echo ((int) date('n') === $bulanNo ? 'selected' : ''); ?>><?php echo $bulanNama; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-5">
            <label for="laporanTahun" class="form-label">Tahun laporan</label>
            <input type="number" class="form-control" id="laporanTahun" min="2000" max="2100" value="<?php echo (int) date('Y'); ?>">
          </div>
        </div>
        <div class="row g-3 mb-4 d-none" id="laporanRentangFields">
          <div class="col-sm-6">
            <label for="laporanDari" class="form-label">Tanggal dari</label>
            <input type="date" class="form-control" id="laporanDari" value="<?php echo date('Y-m-01'); ?>">
          </div>
          <div class="col-sm-6">
            <label for="laporanSampai" class="form-label">Tanggal sampai</label>
            <input type="date" class="form-control" id="laporanSampai" value="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
        <div class="kasuari-report-list">
          <a class="kasuari-report-option" data-report-type="1" target="_blank" rel="noopener">
            <span class="kasuari-report-icon blue"><i class="bi bi-journal-text"></i></span>
            <span><strong>Laporan perkara banding</strong><small>Rekap penerimaan, PMH, sidang, putusan, dan sisa perkara.</small></span>
            <i class="bi bi-arrow-up-right ms-auto"></i>
          </a>
          <a class="kasuari-report-option" data-report-type="2" target="_blank" rel="noopener">
            <span class="kasuari-report-icon violet"><i class="bi bi-send-check"></i></span>
            <span><strong>Laporan pengiriman salinan putusan</strong><small>Salinan putusan PTA Papua Barat ke PA pengaju.</small></span>
            <i class="bi bi-arrow-up-right ms-auto"></i>
          </a>
          <a class="kasuari-report-option" data-report-type="3" target="_blank" rel="noopener">
            <span class="kasuari-report-icon green"><i class="bi bi-globe2"></i></span>
            <span><strong>Laporan publikasi putusan</strong><small>Putusan yang telah masuk proses publikasi/minutasi.</small></span>
            <i class="bi bi-arrow-up-right ms-auto"></i>
          </a>
          <a class="kasuari-report-option" data-report-type="4" target="_blank" rel="noopener">
            <span class="kasuari-report-icon orange"><i class="bi bi-list-columns-reverse"></i></span>
            <span><strong>Register perkara PTA Papua Barat</strong><small>Register perkara banding berdasarkan tanggal pendaftaran.</small></span>
            <i class="bi bi-arrow-up-right ms-auto"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    function updateReportLinks() {
      var month = document.getElementById('laporanBulan').value;
      var year = document.getElementById('laporanTahun').value;
      var mode = document.querySelector('input[name="laporanMode"]:checked').value;
      var dari = document.getElementById('laporanDari').value;
      var sampai = document.getElementById('laporanSampai').value;
      document.querySelectorAll('.kasuari-report-option').forEach(function (link) {
        var url = 'preview_laporan_perkara_banding?jenis=' + encodeURIComponent(link.getAttribute('data-report-type')) + '&mode=' + encodeURIComponent(mode) + '&bulan=' + encodeURIComponent(month) + '&tahun=' + encodeURIComponent(year);
        if (mode === 'rentang') url += '&dari=' + encodeURIComponent(dari) + '&sampai=' + encodeURIComponent(sampai);
        link.href = url;
      });
    }
    function toggleReportPeriodFields() {
      var mode = document.querySelector('input[name="laporanMode"]:checked').value;
      document.getElementById('laporanBulananFields').classList.toggle('d-none', mode !== 'bulanan');
      document.getElementById('laporanRentangFields').classList.toggle('d-none', mode !== 'rentang');
      updateReportLinks();
    }
    document.getElementById('laporanBulan').addEventListener('change', updateReportLinks);
    document.getElementById('laporanTahun').addEventListener('input', updateReportLinks);
    document.getElementById('laporanDari').addEventListener('change', updateReportLinks);
    document.getElementById('laporanSampai').addEventListener('change', updateReportLinks);
    document.querySelectorAll('input[name="laporanMode"]').forEach(function (radio) { radio.addEventListener('change', toggleReportPeriodFields); });
    document.getElementById('modalLaporanBulanan').addEventListener('show.bs.modal', updateReportLinks);
    updateReportLinks();
  }());
</script>
<?php endif; ?>

<?php include_once("sys/footer.php"); ?>
