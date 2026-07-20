<?php
include_once("sys/sys_session.php");
$nama_halaman = "Register Perkara Satker";
include_once("sys/header.php");

$totalPerkaraSatker = 0;
$selectedSatkerId = filter_input(INPUT_GET, 'satker_id', FILTER_VALIDATE_INT);
$selectedSatkerId = $selectedSatkerId && $selectedSatkerId > 0 ? (int) $selectedSatkerId : 0;
$totalPerkaraSatkerResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM perkara");
if ($totalPerkaraSatkerResult) {
  $totalPerkaraSatkerRow = mysqli_fetch_assoc($totalPerkaraSatkerResult);
  $totalPerkaraSatker = (int) ($totalPerkaraSatkerRow['total'] ?? 0);
} else {
  error_log('KASUARI register_perkara_satker count failed: ' . mysqli_error($koneksi));
}
$satkerOptions = array();
$satkerQuery = mysqli_query($koneksi, "SELECT
  pengadilan_agama.id,
  pengadilan_agama.nama,
  COUNT(perkara.id) AS jumlah_perkara
  FROM pengadilan_agama
  INNER JOIN perkara ON perkara.pn_id = pengadilan_agama.id
  GROUP BY pengadilan_agama.id, pengadilan_agama.nama
  ORDER BY pengadilan_agama.nama ASC");
if ($satkerQuery) {
  while ($satkerRow = mysqli_fetch_assoc($satkerQuery)) {
    $satkerOptions[] = $satkerRow;
  }
} else {
  error_log('KASUARI register_perkara_satker filter failed: ' . mysqli_error($koneksi));
}

function satker_status_class($text) {
  $text = strtolower((string) $text);
  if ($text === '') return 'kosong';
  if (strpos($text, 'penetapan') !== false) return 'penetapan';
  if (strpos($text, 'sidang') !== false || strpos($text, 'persidangan') !== false) return 'sidang';
  if (strpos($text, 'minutasi') !== false) return 'minutasi';
  if (strpos($text, 'akta cerai') !== false) return 'akta';
  if (strpos($text, 'ikrar') !== false) return 'ikrar';
  if (strpos($text, 'pemberitahuan') !== false) return 'pemberitahuan';
  if (strpos($text, 'banding') !== false || strpos($text, 'permohonan banding') !== false) return 'banding';
  if (strpos($text, 'kasasi') !== false) return 'kasasi';
  if (strpos($text, 'verzet') !== false || strpos($text, 'perlawanan') !== false) return 'verzet';
  if (strpos($text, 'eksekusi') !== false) return 'eksekusi';
  if (strpos($text, 'putus') !== false || strpos($text, 'selesai') !== false) return 'putusan';
  if (strpos($text, 'cabut') !== false || strpos($text, 'gugur') !== false) return 'dicabut';
  return 'belum';
}

function satker_type_class($text) {
  $text = strtolower((string) $text);
  if (strpos($text, 'cerai gugat') !== false) return 'rose';
  if (strpos($text, 'cerai talak') !== false) return 'amber';
  if (strpos($text, 'waris') !== false) return 'purple';
  if (strpos($text, 'dispensasi') !== false || strpos($text, 'perwalian') !== false) return 'emerald';
  return 'blue';
}
?>
<link rel="stylesheet" type="text/css" href="assets/plugins/jstable/jstable.css" />
<script src="assets/plugins/jstable/jstable.min.js" type="text/javascript"></script>

<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-panel kasuari-panel-table">
      <div class="kasuari-toolbar">
        <div>
          <h3 class="mb-1 fw-bold fs-5">Perkara Satker</h3>
          <p class="text-secondary mb-0">Gunakan pencarian tabel untuk nomor perkara, jenis perkara, satker, atau tahapan terakhir.</p>
        </div>
        <div class="ks-register-actions">
          <label class="ks-satker-filter" for="satker_filter">
            <span>Filter satker</span>
            <select class="form-select" id="satker_filter">
              <option value="">Semua Satker (<?php echo number_format($totalPerkaraSatker, 0, ',', '.'); ?>)</option>
              <?php foreach ($satkerOptions as $satkerOption) { ?>
                <option value="<?php echo (int) $satkerOption['id']; ?>" <?php echo $selectedSatkerId === (int) $satkerOption['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($satkerOption['nama'], ENT_QUOTES, 'UTF-8'); ?>
                  (<?php echo number_format((int) $satkerOption['jumlah_perkara'], 0, ',', '.'); ?>)
                </option>
              <?php } ?>
            </select>
          </label>
          <a href="register_perkara" class="btn btn-outline-primary">
            <i class="bi bi-journal-check me-1" aria-hidden="true"></i>
            Perkara Banding
          </a>
        </div>
      </div>

      <div class="kasuari-table-wrap table-responsive">
        <table class="table table-hover align-middle ks-satker-table" id="datane_result">
          <thead>
            <tr>
              <th>No</th>
              <th>Nomor Perkara</th>
              <th>Satker</th>
              <th>Jenis Perkara</th>
              <th>Tanggal Daftar</th>
              <th>Tanggal Putusan</th>
              <th>Tahapan Terakhir</th>
              <th>Proses Terakhir</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT
                      perkara.id,
                      perkara.nomor_perkara,
                      perkara.jenis_perkara_nama,
                      perkara.tanggal_pendaftaran AS tanggalpendaftaran,
                      perkara.tahapan_terakhir_text,
                      perkara.proses_terakhir_text,
                      pengadilan_agama.nama AS pengaju,
                      putusan.tanggal_putusan AS tanggalputusan
                    FROM perkara
                    LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara.pn_id
                    LEFT JOIN (
                      SELECT pn_id, perkara_id, MAX(tanggal_putusan) AS tanggal_putusan
                      FROM perkara_putusan
                      GROUP BY pn_id, perkara_id
                    ) putusan ON putusan.pn_id = perkara.pn_id AND putusan.perkara_id = perkara.perkara_id
                    ORDER BY perkara.tanggal_pendaftaran DESC, perkara.nomor_urut_register DESC
                    LIMIT 25";
            $query = mysqli_query($koneksi, $sql);
            $no = 0;
            if (!$query) {
              error_log('KASUARI register_perkara_satker list failed: ' . mysqli_error($koneksi));
              echo "<tr><td class='kasuari-empty-state' colspan='9'>Data perkara satker belum dapat dimuat. Periksa struktur database server.</td></tr>";
            }
            while ($query && ($data = mysqli_fetch_assoc($query))) {
              $no++;
              $nomorPerkara = htmlspecialchars($data["nomor_perkara"] ?? "", ENT_QUOTES, 'UTF-8');
              $satker = htmlspecialchars(str_replace("PENGADILAN AGAMA", "PA", $data["pengaju"] ?? ""), ENT_QUOTES, 'UTF-8');
              $jenis = htmlspecialchars($data["jenis_perkara_nama"] ?? "", ENT_QUOTES, 'UTF-8');
              $tahapan = htmlspecialchars($data["tahapan_terakhir_text"] ?? "", ENT_QUOTES, 'UTF-8');
              $proses = htmlspecialchars($data["proses_terakhir_text"] ?? "", ENT_QUOTES, 'UTF-8');
              $typeClass = satker_type_class($data["jenis_perkara_nama"] ?? "");
              $stageClass = satker_status_class($data["tahapan_terakhir_text"] ?? "");
              $processClass = satker_status_class($data["proses_terakhir_text"] ?? "");
              echo "<tr>";
              echo "<td class='text-center'><span class='ks-row-number'>" . $no . "</span></td>";
              echo "<td><a class='ks-case-ref' href='perkara_detil_satker&id=" . (int) $data["id"] . "'><span>" . $nomorPerkara . "</span></a></td>";
              echo "<td><span class='ks-satker-badge'>" . $satker . "</span></td>";
              echo "<td><span class='ks-type-badge " . $typeClass . "'>" . $jenis . "</span></td>";
              echo "<td><span class='ks-date-chip'>" . htmlspecialchars(kasuari_tanggal_indonesia($data["tanggalpendaftaran"] ?? ""), ENT_QUOTES, 'UTF-8') . "</span></td>";
              echo "<td><span class='ks-date-chip muted'>" . htmlspecialchars(kasuari_tanggal_indonesia($data["tanggalputusan"] ?? ""), ENT_QUOTES, 'UTF-8') . "</span></td>";
              echo "<td><span class='ks-status-pill " . $stageClass . "'>" . $tahapan . "</span></td>";
              echo "<td><span class='ks-status-pill " . $processClass . "'>" . $proses . "</span></td>";
              echo "<td><a class='kasuari-action-link ks-view-action' href='perkara_detil_satker&id=" . (int) $data["id"] . "' title='Lihat detail perkara' aria-label='Lihat detail perkara'><i class='bi bi-eye' aria-hidden='true'></i></a></td>";
              echo "</tr>";
            }
            if ($query && $no == 0) {
              echo "<tr><td class='kasuari-empty-state' colspan='9'>Tidak ada data perkara satker.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  var table = new JSTable("#datane_result", {
    serverSide: true,
    deferLoading: <?php echo $totalPerkaraSatker ?>,
    ajax: "register_perkara_satker_data",
    ajaxParams: {
      satker_id: "<?php echo $selectedSatkerId > 0 ? $selectedSatkerId : ''; ?>"
    },
    columns: [
      { select: 4, sort: "desc" }
    ]
  });

  <?php if ($selectedSatkerId > 0): ?>
  table.paginate(1);
  <?php endif; ?>

  document.getElementById("satker_filter").addEventListener("change", function () {
    table.config.ajaxParams.satker_id = this.value;
    table.paginate(1);
  });
</script>

<?php include_once("sys/footer.php"); ?>
