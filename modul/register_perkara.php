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
        <a href="register_perkara_satker" class="btn btn-outline-primary">
          <i class="bi bi-folder2-open me-1" aria-hidden="true"></i>
          Perkara Satker
        </a>
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
          $statusBanding = htmlspecialchars($data["status_banding_text"] ?? "", ENT_QUOTES, 'UTF-8');
          $statusBandingClass = register_banding_status_class($data["status_banding_text"] ?? "");
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
            <td><span class="ks-banding-status ' . $statusBandingClass . '">' . ($statusBanding !== '' ? $statusBanding : 'Belum ada status') . '</span></td>
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
    ajax: "register_perkara_data"
  });
</script>

<?php include_once("sys/footer.php"); ?>
