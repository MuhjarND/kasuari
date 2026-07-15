<?php
include_once("sys/sys_session.php");
$nama_halaman = "Detail Perkara Satker";
include_once("sys/header.php");

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$sql = "SELECT
          perkara.id,
          perkara.perkara_id,
          perkara.pn_id,
          perkara.nomor_perkara,
          perkara.nomor_urut_register,
          perkara.jenis_perkara_nama,
          perkara.jenis_perkara_text,
          perkara.para_pihak,
          perkara.pihak1_text,
          perkara.pihak2_text,
          perkara.pihak3_text,
          perkara.pihak4_text,
          perkara.posita,
          perkara.petitum,
          perkara.tahapan_terakhir_text,
          perkara.proses_terakhir_text,
          convert_tanggal_indonesia(perkara.tanggal_pendaftaran) AS tanggal_pendaftaran,
          convert_tanggal_indonesia(putusan.tanggal_putusan) AS tanggal_putusan,
          putusan.status_putusan_text,
          putusan.amar_putusan,
          pengadilan_agama.nama AS pengaju
        FROM perkara
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara.pn_id
        LEFT JOIN (
          SELECT pp.*
          FROM perkara_putusan pp
          INNER JOIN (
            SELECT pn_id, perkara_id, MAX(tanggal_putusan) AS tanggal_putusan
            FROM perkara_putusan
            GROUP BY pn_id, perkara_id
          ) latest ON latest.pn_id = pp.pn_id
            AND latest.perkara_id = pp.perkara_id
            AND latest.tanggal_putusan = pp.tanggal_putusan
        ) putusan ON putusan.pn_id = perkara.pn_id AND putusan.perkara_id = perkara.perkara_id
        WHERE perkara.id = " . $id . "
        LIMIT 1";
$query = mysqli_query($koneksi, $sql);
$perkara = $query ? mysqli_fetch_assoc($query) : null;

function ks_value($value, $fallback = '-') {
  $value = trim((string) $value);
  return $value === '' ? $fallback : $value;
}

function ks_status_class($text) {
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

function ks_type_class($text) {
  $text = strtolower((string) $text);
  if (strpos($text, 'cerai gugat') !== false) return 'rose';
  if (strpos($text, 'cerai talak') !== false) return 'amber';
  if (strpos($text, 'waris') !== false) return 'purple';
  if (strpos($text, 'dispensasi') !== false || strpos($text, 'perwalian') !== false) return 'emerald';
  return 'blue';
}
?>

<div class="app-content-header">
  <div class="container-fluid">
    <div class="kasuari-page-title">
      <div>
        <h1>Detail Perkara Satker</h1>
        <p>Informasi perkara yang tersinkron dari SIPP lokal satker.</p>
      </div>
      <a href="register_perkara_satker" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        Kembali
      </a>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <?php if (!$perkara): ?>
      <div class="kasuari-panel">
        <div class="kasuari-empty-state py-5">
          <i class="bi bi-exclamation-triangle d-block mb-2" style="font-size:2rem;"></i>
          Data perkara satker tidak ditemukan.
        </div>
      </div>
    <?php else:
      $nomorPerkara = ks_value($perkara['nomor_perkara'] ?? '');
      $satker = str_replace('PENGADILAN AGAMA ', 'PA ', ks_value($perkara['pengaju'] ?? ''));
      $jenis = ks_value($perkara['jenis_perkara_nama'] ?? '');
      $tahapan = ks_value($perkara['tahapan_terakhir_text'] ?? '');
      $proses = ks_value($perkara['proses_terakhir_text'] ?? '');
      $putusanStatus = ks_value($perkara['status_putusan_text'] ?? 'Belum putus');
      $typeClass = ks_type_class($jenis);
      $stageClass = ks_status_class($tahapan);
      $processClass = ks_status_class($proses);
    ?>

      <div class="ks-detail-hero mb-3">
        <div>
          <h2><?php echo htmlspecialchars($nomorPerkara, ENT_QUOTES, 'UTF-8'); ?></h2>
          <div class="ks-detail-meta">
            <span class="ks-satker-badge"><i class="bi bi-building"></i><?php echo htmlspecialchars($satker, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ks-type-badge <?php echo $typeClass; ?>"><?php echo htmlspecialchars($jenis, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ks-date-chip"><i class="bi bi-calendar3"></i><?php echo htmlspecialchars(ks_value($perkara['tanggal_pendaftaran'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <span class="ks-status-pill <?php echo $processClass; ?>"><?php echo htmlspecialchars($proses, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-lg-4">
          <div class="ks-detail-card">
            <div class="ks-detail-card-header">
              <i class="bi bi-activity text-primary"></i>
              <h3>Status Perkara</h3>
            </div>
            <div class="ks-detail-card-body">
              <div class="ks-info-grid">
                <div class="ks-info-box">
                  <label>Tahapan Terakhir</label>
                  <span class="ks-status-pill <?php echo $stageClass; ?>"><?php echo htmlspecialchars($tahapan, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="ks-info-box">
                  <label>Proses Terakhir</label>
                  <span class="ks-status-pill <?php echo $processClass; ?>"><?php echo htmlspecialchars($proses, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="ks-info-box">
                  <label>Tanggal Daftar</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['tanggal_pendaftaran'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="ks-info-box">
                  <label>Tanggal Putusan</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['tanggal_putusan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="ks-detail-card">
            <div class="ks-detail-card-header">
              <i class="bi bi-people text-success"></i>
              <h3>Para Pihak</h3>
            </div>
            <div class="ks-detail-card-body">
              <div class="ks-info-grid">
                <div class="ks-info-box">
                  <label>Pihak 1</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['pihak1_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="ks-info-box">
                  <label>Pihak 2</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['pihak2_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="ks-info-box">
                  <label>Pihak 3</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['pihak3_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="ks-info-box">
                  <label>Pihak 4</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['pihak4_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="ks-detail-card">
            <div class="ks-detail-card-header">
              <i class="bi bi-gavel text-danger"></i>
              <h3>Putusan</h3>
            </div>
            <div class="ks-detail-card-body">
              <div class="ks-info-grid">
                <div class="ks-info-box">
                  <label>Status Putusan</label>
                  <strong><?php echo htmlspecialchars($putusanStatus, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="ks-info-box">
                  <label>Nomor Urut</label>
                  <strong><?php echo htmlspecialchars(ks_value($perkara['nomor_urut_register'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
              </div>
              <div class="mt-3">
                <label class="form-label">Amar Putusan</label>
                <div class="ks-long-text ks-rich-text"><?php echo kasuari_safe_rich_text($perkara['amar_putusan'] ?? '', 'Belum ada amar putusan.'); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="ks-detail-card">
            <div class="ks-detail-card-header">
              <i class="bi bi-file-text text-primary"></i>
              <h3>Posita</h3>
            </div>
            <div class="ks-detail-card-body">
              <div class="ks-long-text ks-rich-text"><?php echo kasuari_safe_rich_text($perkara['posita'] ?? '', 'Belum ada data posita.'); ?></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="ks-detail-card">
            <div class="ks-detail-card-header">
              <i class="bi bi-journal-check text-success"></i>
              <h3>Petitum</h3>
            </div>
            <div class="ks-detail-card-body">
              <div class="ks-long-text ks-rich-text"><?php echo kasuari_safe_rich_text($perkara['petitum'] ?? '', 'Belum ada data petitum.'); ?></div>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </div>
</div>

<?php include_once("sys/footer.php"); ?>
