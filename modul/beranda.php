<?php
include_once("sys/sys_session.php");
$nama_halaman = "Beranda";
include_once("sys/header.php");

/* â”€â”€â”€ Nama pengguna â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$fullname = isset($_SESSION['fullname']) && $_SESSION['fullname'] !== ''
  ? $_SESSION['fullname'] : 'Pengguna';
$username = isset($_SESSION['username']) && $_SESSION['username'] !== ''
  ? $_SESSION['username'] : 'Operator';

/* â”€â”€â”€ Sinkronisasi â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$singkron = [1 => '-', 2 => '-', 3 => '-'];
$q = mysqli_query($koneksi, "SELECT * FROM singkron");
while ($r = mysqli_fetch_assoc($q)) {
  if (isset($singkron[$r['id']])) $singkron[$r['id']] = $r['waktu'];
}

/* â”€â”€â”€ Helper count â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$cnt = function ($sql) use ($koneksi) {
  $res = mysqli_query($koneksi, $sql);
  return $res ? mysqli_num_rows($res) : 0;
};

/* â”€â”€â”€ Stat cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$totalBanding = $cnt("SELECT id FROM perkara_banding");

$belumDikirim = $cnt("SELECT id FROM perkara_banding
  WHERE pengiriman_berkas_banding IS NULL
  AND tanggal_pendaftaran_banding IS NULL
  AND tanggal_cabut IS NULL
  AND year(permohonan_banding) >= year(CURDATE())-1
  AND nomor_perkara_banding = ''");

$dalamProses = $cnt("SELECT id FROM perkara_banding
  WHERE tanggal_pendaftaran_banding IS NOT NULL
  AND putusan_banding IS NULL");

$sudahPutusan = $cnt("SELECT id FROM perkara_banding
  WHERE putusan_banding IS NOT NULL");

$dicabut = $cnt("SELECT id FROM perkara_banding
  WHERE tanggal_cabut IS NOT NULL");

/* â”€â”€â”€ Perkara banding terbaru (sudah terdaftar) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$sql_new = "SELECT pb.id, pb.nomor_perkara_banding, pb.nomor_perkara_pn,
  pb.status_banding_text,
  convert_tanggal_indonesia(pb.tanggal_pendaftaran_banding) AS tgl_daftar,
  pa.nama AS pengaju
  FROM perkara_banding pb
  LEFT JOIN pengadilan_agama pa ON pa.id = pb.pn_id
  WHERE pb.tanggal_pendaftaran_banding IS NOT NULL
  ORDER BY pb.tanggal_pendaftaran_banding DESC LIMIT 5";
$recent = mysqli_query($koneksi, $sql_new);
$recentRows = [];
if ($recent) while ($r = mysqli_fetch_assoc($recent)) $recentRows[] = $r;

/* â”€â”€â”€ Perkara perlu tindak lanjut (belum dikirim) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$sql_tlj = "SELECT pb.id, pb.nomor_perkara_pn,
  convert_tanggal_indonesia(pb.permohonan_banding) AS tgl_permohonan,
  DATEDIFF(CURDATE(), pb.permohonan_banding) AS hari,
  pa.nama AS pengaju
  FROM perkara_banding pb
  LEFT JOIN pengadilan_agama pa ON pa.id = pb.pn_id
  WHERE pb.pengiriman_berkas_banding IS NULL
  AND pb.tanggal_pendaftaran_banding IS NULL
  AND pb.tanggal_cabut IS NULL
  AND year(pb.permohonan_banding) >= year(CURDATE())-1
  AND pb.nomor_perkara_banding = ''
  ORDER BY pb.permohonan_banding ASC LIMIT 5";
$tlj = mysqli_query($koneksi, $sql_tlj);
$tljRows = [];
if ($tlj) while ($r = mysqli_fetch_assoc($tlj)) $tljRows[] = $r;

$sql_satker = "SELECT
  perkara.id,
  perkara.nomor_perkara,
  perkara.jenis_perkara_nama,
  convert_tanggal_indonesia(perkara.tanggal_pendaftaran) AS tanggal_daftar,
  perkara.tahapan_terakhir_text,
  perkara.proses_terakhir_text,
  pengadilan_agama.nama AS pengaju
  FROM perkara
  LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara.pn_id
  ORDER BY perkara.tanggal_pendaftaran DESC, perkara.nomor_urut_register DESC
  LIMIT 8";
$satker = mysqli_query($koneksi, $sql_satker);
$satkerRows = [];
if ($satker) while ($r = mysqli_fetch_assoc($satker)) $satkerRows[] = $r;

/* â”€â”€â”€ Greeting emoji berdasarkan waktu â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$waktuWit = new DateTimeImmutable('now', new DateTimeZone('Asia/Jayapura'));
$jam = (int) $waktuWit->format('H');
$sapa = $jam < 12 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$greetingIcon = $jam < 12 ? 'bi-brightness-alt-high' : ($jam < 18 ? 'bi-hand-index-thumb' : 'bi-moon-stars');
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="ks-dashboard-header d-flex align-items-center justify-content-between gap-3">
      <div>
        <p class="mb-0" style="font-size:.78rem;color:#6b7280;font-weight:500;">
          <i class="bi bi-calendar3 me-1"></i>
          <?php echo ucfirst(strftime('%A, %d %B %Y')); ?>
        </p>
      </div>
      <div class="ks-dashboard-meta d-flex align-items-center gap-2">
        <span class="kasuari-chip kasuari-clock" id="witClock" aria-label="Waktu Indonesia Timur">
          <i class="bi bi-clock" aria-hidden="true"></i>
          <span id="witClockValue"><?php echo $waktuWit->format('H:i:s'); ?></span>
          <span>WIT</span>
        </span>
        <span class="kasuari-chip" style="font-size:.75rem;background:#eef2ff;border-color:rgba(79,70,229,.15);color:#4f46e5;">
          <i class="bi bi-circle-fill" style="font-size:.4rem;color:#22c55e;"></i>
          Sistem Aktif
        </span>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">
    <div class="row g-0">

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           MAIN CONTENT
           â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <div class="col-12">

        <!-- â”€â”€ Greeting â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div class="ks-greeting mb-4">
          <h2><?php echo htmlspecialchars($sapa, ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>! <i class="bi <?php echo $greetingIcon; ?> ms-1 text-primary" aria-hidden="true"></i></h2>
          <p><?php echo htmlspecialchars(@$deskripsi_app ?: 'Sistem manajemen dan layanan digital terpadu untuk mendukung tupoksi Pengadilan Tinggi Agama Papua Barat', ENT_QUOTES, 'UTF-8'); ?>.</p>
        </div>

        <!-- â”€â”€ Stat Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div class="row g-3 mb-4 ks-stat-grid">

          <div class="col-6 col-xl-3">
            <div class="ks-stat">
              <div class="ks-stat-top">
                <div class="ks-stat-icon purple"><i class="bi bi-folder2-open"></i></div>
              </div>
              <div class="ks-stat-num"><?php echo number_format($totalBanding); ?></div>
              <p class="ks-stat-label">Total Perkara Banding</p>
              <span class="ks-stat-sub info"><i class="bi bi-circle-fill" style="font-size:.4rem;"></i>Keseluruhan data</span>
              <a class="ks-stat-link" href="register_perkara">Lihat Register <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-6 col-xl-3">
            <div class="ks-stat">
              <div class="ks-stat-top">
                <div class="ks-stat-icon blue"><i class="bi bi-send-exclamation"></i></div>
              </div>
              <div class="ks-stat-num" style="color:#0369a1;"><?php echo number_format($belumDikirim); ?></div>
              <p class="ks-stat-label">Belum Dikirim</p>
              <span class="ks-stat-sub warn"><i class="bi bi-exclamation-circle-fill" style="font-size:.65rem;"></i>Perlu tindak lanjut</span>
              <a class="ks-stat-link blue" href="register_perkara_belum_dikirim">Lihat Detail <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-6 col-xl-3">
            <div class="ks-stat">
              <div class="ks-stat-top">
                <div class="ks-stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
              </div>
              <div class="ks-stat-num" style="color:#d97706;"><?php echo number_format($dalamProses); ?></div>
              <p class="ks-stat-label">Dalam Proses</p>
              <span class="ks-stat-sub warn"><i class="bi bi-clock-fill" style="font-size:.65rem;"></i>Menunggu putusan</span>
              <a class="ks-stat-link amber" href="register_perkara">Pantau Perkara <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-6 col-xl-3">
            <div class="ks-stat">
              <div class="ks-stat-top">
                <div class="ks-stat-icon emerald"><i class="bi bi-patch-check"></i></div>
              </div>
              <div class="ks-stat-num" style="color:#059669;"><?php echo number_format($sudahPutusan); ?></div>
              <p class="ks-stat-label">Sudah Putusan</p>
              <span class="ks-stat-sub good"><i class="bi bi-check-circle-fill" style="font-size:.65rem;"></i>Selesai diputus</span>
              <a class="ks-stat-link" style="color:#059669;" href="register_perkara">Lihat Data <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

        </div>
        <!-- end::stat cards -->

        <!-- â”€â”€ Section Cards (3 kolom, mirip gambar) â”€â”€â”€â”€â”€â”€â”€ -->
        <div class="row g-3 mb-3">

          <!-- â”€â”€ 1: Perkara Terbaru â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <div class="col-md-4">
            <div class="ks-card h-100">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#ede9fe;color:#7c3aed;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-folder2-open"></i></span>
                  Perkara Terbaru
                </h3>
                <a class="see-all" href="register_perkara">Lihat Semua <i class="bi bi-chevron-right"></i></a>
              </div>
              <div class="ks-card-body">
                <?php if (count($recentRows) === 0): ?>
                  <div class="kasuari-empty-state py-4">Tidak ada data</div>
                <?php else: ?>
                  <?php
                  $iconColors = ['purple','blue','emerald','amber','teal'];
                  $ic = 0;
                  foreach ($recentRows as $row):
                    $pengaju = str_replace('PENGADILAN AGAMA ', 'PA ', $row['pengaju'] ?? '');
                    $color = $iconColors[$ic % count($iconColors)]; $ic++;
                    $statusText = $row['status_banding_text'] ?? '';
                    // determine badge class
                    if (stripos($statusText,'proses') !== false || stripos($statusText,'register') !== false) $bc = 'proses';
                    elseif (stripos($statusText,'putusan') !== false || stripos($statusText,'selesai') !== false) $bc = 'selesai';
                    else $bc = 'proses';
                  ?>
                  <a class="ks-perkara-item" href="perkara_detil_banding&id=<?php echo $row['id']; ?>">
                    <div class="ks-perkara-icon <?php echo $color; ?>">
                      <i class="bi bi-folder2"></i>
                    </div>
                    <div class="ks-perkara-body">
                      <p class="ks-perkara-title"><?php echo htmlspecialchars($row['nomor_perkara_banding'], ENT_QUOTES, 'UTF-8'); ?></p>
                      <p class="ks-perkara-meta">
                        <span><?php echo htmlspecialchars($pengaju, ENT_QUOTES, 'UTF-8'); ?></span>
                      </p>
                    </div>
                    <div class="ks-perkara-right">
                      <p class="ks-perkara-date"><?php echo htmlspecialchars($row['tgl_daftar'], ENT_QUOTES, 'UTF-8'); ?></p>
                      <span class="ks-badge <?php echo $bc; ?> mt-1 d-block"><?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                  </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="ks-card-footer">
                <a href="register_perkara"><i class="bi bi-arrow-right-circle me-1"></i>Lihat Semua Perkara Banding</a>
              </div>
            </div>
          </div>

          <!-- â”€â”€ 2: Perlu Tindak Lanjut â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <div class="col-md-4">
            <div class="ks-card h-100">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#fef3c7;color:#d97706;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-exclamation-triangle"></i></span>
                  Perlu Tindak Lanjut
                </h3>
                <a class="see-all" href="register_perkara_belum_dikirim">Lihat Semua <i class="bi bi-chevron-right"></i></a>
              </div>
              <div class="ks-card-body">
                <?php if (count($tljRows) === 0): ?>
                  <div class="kasuari-empty-state py-4">
                    <i class="bi bi-check-circle text-success d-block mb-1" style="font-size:1.4rem;"></i>
                    Semua perkara sudah dikirim!
                  </div>
                <?php else: ?>
                  <?php foreach ($tljRows as $row):
                    $pengaju = str_replace('PENGADILAN AGAMA ', 'PA ', $row['pengaju'] ?? '');
                    $hari = (int)($row['hari'] ?? 0);
                    $badgeClass = $hari > 90 ? 'danger' : ($hari > 30 ? 'warn' : 'info');
                  ?>
                  <a class="ks-perkara-item" href="perkara_detil_banding&id=<?php echo $row['id']; ?>">
                    <div class="ks-perkara-icon amber">
                      <i class="bi bi-send-exclamation"></i>
                    </div>
                    <div class="ks-perkara-body">
                      <p class="ks-perkara-title"><?php echo htmlspecialchars($row['nomor_perkara_pn'], ENT_QUOTES, 'UTF-8'); ?></p>
                      <p class="ks-perkara-meta">
                        <span><?php echo htmlspecialchars($pengaju, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>&middot; <?php echo htmlspecialchars($row['tgl_permohonan'], ENT_QUOTES, 'UTF-8'); ?></span>
                      </p>
                    </div>
                    <div class="ks-perkara-right">
                      <span class="ks-badge <?php echo $badgeClass === 'danger' ? 'dicabut' : ($badgeClass === 'warn' ? 'warning' : 'belum'); ?>">
                        <?php echo $hari; ?> hari
                      </span>
                    </div>
                  </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="ks-card-footer">
                <a href="register_perkara_belum_dikirim"><i class="bi bi-arrow-right-circle me-1"></i>Lihat Semua Daftar Belum Dikirim</a>
              </div>
            </div>
          </div>

          <!-- â”€â”€ 3: Ringkasan Status Perkara â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <div class="col-md-4">
            <div class="ks-card h-100">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#dbeafe;color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-bar-chart"></i></span>
                  Ringkasan Status
                </h3>
                <a class="see-all" href="register_perkara">Detail <i class="bi bi-chevron-right"></i></a>
              </div>
              <div class="ks-card-body">

                <div class="ks-ringkasan-row">
                  <div class="ks-ringkasan-left">
                    <div class="ks-ringkasan-dot" style="background:#6366f1;"></div>
                    <span class="ks-ringkasan-label">Total Banding</span>
                  </div>
                  <div class="ks-ringkasan-right">
                    <span class="ks-ringkasan-count"><?php echo number_format($totalBanding); ?></span>
                    <span class="ks-badge proses">Keseluruhan</span>
                  </div>
                </div>

                <div class="ks-ringkasan-row">
                  <div class="ks-ringkasan-left">
                    <div class="ks-ringkasan-dot" style="background:#f59e0b;"></div>
                    <span class="ks-ringkasan-label">Belum Dikirim</span>
                  </div>
                  <div class="ks-ringkasan-right">
                    <span class="ks-ringkasan-count"><?php echo number_format($belumDikirim); ?></span>
                    <span class="ks-badge belum">Perhatian</span>
                  </div>
                </div>

                <div class="ks-ringkasan-row">
                  <div class="ks-ringkasan-left">
                    <div class="ks-ringkasan-dot" style="background:#0ea5e9;"></div>
                    <span class="ks-ringkasan-label">Dalam Proses</span>
                  </div>
                  <div class="ks-ringkasan-right">
                    <span class="ks-ringkasan-count"><?php echo number_format($dalamProses); ?></span>
                    <span class="ks-badge proses">Berjalan</span>
                  </div>
                </div>

                <div class="ks-ringkasan-row">
                  <div class="ks-ringkasan-left">
                    <div class="ks-ringkasan-dot" style="background:#10b981;"></div>
                    <span class="ks-ringkasan-label">Sudah Putusan</span>
                  </div>
                  <div class="ks-ringkasan-right">
                    <span class="ks-ringkasan-count"><?php echo number_format($sudahPutusan); ?></span>
                    <span class="ks-badge selesai">Selesai</span>
                  </div>
                </div>

                <div class="ks-ringkasan-row">
                  <div class="ks-ringkasan-left">
                    <div class="ks-ringkasan-dot" style="background:#f43f5e;"></div>
                    <span class="ks-ringkasan-label">Dicabut</span>
                  </div>
                  <div class="ks-ringkasan-right">
                    <span class="ks-ringkasan-count"><?php echo number_format($dicabut); ?></span>
                    <span class="ks-badge dicabut">Selesai</span>
                  </div>
                </div>

              </div>
              <div class="ks-card-footer">
                <a href="register_perkara"><i class="bi bi-arrow-right-circle me-1"></i>Lihat Detail Perkara</a>
              </div>
            </div>
          </div>

        </div>
        <!-- end::section cards -->

        <div class="row g-3 mb-3">
          <div class="col-12">
            <div class="ks-card">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#e0f2fe;color:#0369a1;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-building"></i></span>
                  Perkara Satker Terbaru
                </h3>
                <a class="see-all" href="register_perkara_satker">Lihat Semua <i class="bi bi-chevron-right"></i></a>
              </div>
              <div class="ks-card-body">
                <?php if (count($satkerRows) === 0): ?>
                  <div class="kasuari-empty-state py-4">Tidak ada data perkara satker.</div>
                <?php else: ?>
                  <div class="ks-satker-list">
                    <?php
                    $satkerColors = ['blue','teal','purple','emerald','amber'];
                    $sc = 0;
                    foreach ($satkerRows as $row):
                      $pengaju = str_replace('PENGADILAN AGAMA ', 'PA ', $row['pengaju'] ?? '');
                      $color = $satkerColors[$sc % count($satkerColors)]; $sc++;
                      $statusText = trim($row['proses_terakhir_text'] ?? '');
                      if ($statusText === '') $statusText = trim($row['tahapan_terakhir_text'] ?? '');
                      if ($statusText === '') $statusText = 'Belum ada status';
                      $statusLower = strtolower($statusText);
                      if (strpos($statusLower, 'putus') !== false || strpos($statusLower, 'minutasi') !== false || strpos($statusLower, 'selesai') !== false) {
                        $badgeClass = 'selesai';
                      } elseif (strpos($statusLower, 'sidang') !== false || strpos($statusLower, 'proses') !== false || strpos($statusLower, 'daftar') !== false || strpos($statusLower, 'mediasi') !== false) {
                        $badgeClass = 'proses';
                      } else {
                        $badgeClass = 'belum';
                      }
                    ?>
                    <a class="ks-perkara-item" href="perkara_detil_satker&id=<?php echo (int) $row['id']; ?>">
                      <div class="ks-perkara-icon <?php echo $color; ?>">
                        <i class="bi bi-file-earmark-text"></i>
                      </div>
                      <div class="ks-perkara-body">
                        <p class="ks-perkara-title"><?php echo htmlspecialchars($row['nomor_perkara'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="ks-perkara-meta">
                          <span><?php echo htmlspecialchars($pengaju, ENT_QUOTES, 'UTF-8'); ?></span>
                          <span>&middot; <?php echo htmlspecialchars($row['jenis_perkara_nama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                      </div>
                      <div class="ks-perkara-right">
                        <p class="ks-perkara-date"><?php echo htmlspecialchars($row['tanggal_daftar'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                        <span class="ks-badge <?php echo $badgeClass; ?> mt-1 d-block"><?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?></span>
                      </div>
                    </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="ks-card-footer">
                <a href="register_perkara_satker"><i class="bi bi-arrow-right-circle me-1"></i>Lihat Semua Perkara Satker</a>
              </div>
            </div>
          </div>
        </div>

        <!-- â”€â”€ Sinkronisasi + Akses Cepat â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div class="row g-3">

          <!-- Sinkronisasi -->
          <div class="col-md-6">
            <div class="ks-card h-100">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#d1fae5;color:#059669;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-arrow-repeat"></i></span>
                  Sinkronisasi Terakhir
                </h3>
              </div>
              <div class="ks-card-body">

                <div class="ks-sync-item">
                  <div class="ks-sync-icon" style="background:#ede9fe;">
                    <i class="bi bi-folder2" style="color:#7c3aed;"></i>
                  </div>
                  <div>
                    <p class="ks-sync-title">Data Perkara Banding</p>
                    <p class="ks-sync-time"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($singkron[1], ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <span class="ks-badge selesai ms-auto">Sync</span>
                </div>

                <div class="ks-sync-item">
                  <div class="ks-sync-icon" style="background:#dbeafe;">
                    <i class="bi bi-file-earmark-text" style="color:#1d4ed8;"></i>
                  </div>
                  <div>
                    <p class="ks-sync-title">Detail Perkara</p>
                    <p class="ks-sync-time"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($singkron[2], ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <span class="ks-badge selesai ms-auto">Sync</span>
                </div>

                <div class="ks-sync-item">
                  <div class="ks-sync-icon" style="background:#d1fae5;">
                    <i class="bi bi-people" style="color:#059669;"></i>
                  </div>
                  <div>
                    <p class="ks-sync-title">Data Pihak</p>
                    <p class="ks-sync-time"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($singkron[3], ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <span class="ks-badge selesai ms-auto">Sync</span>
                </div>

              </div>
              <div class="ks-card-footer">
                <a href="https://sipp-banding.mahkamahagung.go.id/" target="_blank">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Buka SIPP Banding
                </a>
              </div>
            </div>
          </div>

          <!-- Akses Cepat -->
          <div class="col-md-6">
            <div class="ks-card h-100">
              <div class="ks-card-header">
                <h3>
                  <span style="width:22px;height:22px;border-radius:6px;background:#e0f2fe;color:#0369a1;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="bi bi-grid-3x3-gap"></i></span>
                  Akses Cepat
                </h3>
              </div>
              <div class="ks-quick-grid">

                <a class="ks-quick-btn" href="register_perkara">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366f1,#8b5cf6);font-size:.95rem;"><i class="bi bi-folder2-open" style="color:#fff;"></i></div>
                  <span>Register<br>Banding</span>
                </a>

                <a class="ks-quick-btn" href="register_perkara_satker">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0ea5e9,#3b82f6);font-size:.95rem;"><i class="bi bi-building" style="color:#fff;"></i></div>
                  <span>Perkara<br>Satker</span>
                </a>

                <a class="ks-quick-btn" href="register_perkara_belum_dikirim">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f59e0b,#f97316);font-size:.95rem;"><i class="bi bi-send-exclamation" style="color:#fff;"></i></div>
                  <span>Belum<br>Dikirim</span>
                </a>

                <a class="ks-quick-btn" href="hatibinwasda">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#10b981,#059669);font-size:.95rem;"><i class="bi bi-clipboard2-data" style="color:#fff;"></i></div>
                  <span>Hatibin-<br>wasda</span>
                </a>

                <a class="ks-quick-btn" href="hatibinwasbid">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#14b8a6,#0d9488);font-size:.95rem;"><i class="bi bi-clipboard2-pulse" style="color:#fff;"></i></div>
                  <span>Hatibin-<br>wasbid</span>
                </a>

                <a class="ks-quick-btn" href="cctv" target="_blank">
                  <div class="ks-qb-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f43f5e,#e11d48);font-size:.95rem;"><i class="bi bi-camera-video" style="color:#fff;"></i></div>
                  <span>CCTV<br>Online</span>
                </a>

              </div>
            </div>
          </div>

        </div>

      </div><!-- end::main content -->




    </div><!-- end::row -->
  </div>
</div>
<!--end::App Content-->

<script>
  (function () {
    var clockValue = document.getElementById('witClockValue');
    if (!clockValue || typeof Intl === 'undefined') return;

    var formatter = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jayapura',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hourCycle: 'h23'
    });

    function updateWitClock() {
      var parts = formatter.formatToParts(new Date());
      var time = {};
      parts.forEach(function (part) {
        if (part.type !== 'literal') time[part.type] = part.value;
      });
      clockValue.textContent = time.hour + ':' + time.minute + ':' + time.second;
    }

    updateWitClock();
    window.setInterval(updateWitClock, 1000);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) updateWitClock();
    });
  }());
</script>
<?php include_once("sys/footer.php"); ?>
