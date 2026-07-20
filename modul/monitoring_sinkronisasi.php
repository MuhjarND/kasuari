<?php
include_once("sys/sys_session.php");
$nama_halaman = "Monitoring Sinkronisasi";
include_once("sys/header.php");
include_once("sys/sys_monitoring.php");
date_default_timezone_set('Asia/Jayapura');

function kasuari_monitoring_datetime($value, $fallback = '-')
{
  $value = trim((string) $value);
  if ($value === '' || strpos($value, '0000-00-00') === 0) {
    return $fallback;
  }
  $timestamp = strtotime($value);
  if ($timestamp === false) {
    return $fallback;
  }
  $months = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
  return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y H:i:s', $timestamp) . ' WIT';
}

function kasuari_monitoring_short_name($name)
{
  return str_ireplace('PENGADILAN AGAMA ', 'PA ', trim((string) $name));
}

$schemaReady = kasuari_monitoring_ensure_schema($koneksi);
$monitoringRows = array();
$summary = array('total' => 0, 'sinkron' => 0, 'pending' => 0, 'belum' => 0);
$monitoredSatkerIds = array(429, 431, 439, 844);
$monitoredSatkerSql = implode(',', array_map('intval', $monitoredSatkerIds));

if ($schemaReady) {
  $monitoringSql = "SELECT
      pa.id, pa.kode, pa.nama,
      monitor.last_seen_at, monitor.local_perkara_count, monitor.local_data_changed_at,
      monitor.local_signature, monitor.last_sync_at, monitor.last_sync_count,
      monitor.last_sync_signature, monitor.app_version,
      COALESCE(central_data.jumlah, 0) AS central_perkara_count
    FROM pengadilan_agama pa
    LEFT JOIN sync_monitoring monitor ON monitor.pn_id=pa.id
    LEFT JOIN (
      SELECT pn_id, COUNT(*) AS jumlah
      FROM perkara
      GROUP BY pn_id
    ) central_data ON central_data.pn_id=pa.id
    WHERE pa.aktif='Y' AND pa.id IN (" . $monitoredSatkerSql . ")
    ORDER BY pa.nama ASC";
  $monitoringResult = mysqli_query($koneksi, $monitoringSql);
  if ($monitoringResult) {
    while ($row = mysqli_fetch_assoc($monitoringResult)) {
      $hasSync = !empty($row['last_sync_at']);
      $hasHeartbeat = !empty($row['last_seen_at']);
      $hasSignaturePair = !empty($row['local_signature']) && !empty($row['last_sync_signature']);
      $signatureChanged = $hasSignaturePair
        ? !hash_equals($row['last_sync_signature'], $row['local_signature'])
        : (!empty($row['local_data_changed_at']) && $row['local_data_changed_at'] > $row['last_sync_at']);
      $hasPendingChanges = $hasSync && $hasHeartbeat && (
        (int) $row['local_perkara_count'] !== (int) $row['last_sync_count'] ||
        $signatureChanged
      );

      if (!$hasSync) {
        $row['status_key'] = 'belum';
        $row['status_label'] = 'Belum sinkron';
        $row['status_note'] = 'Belum ada riwayat pengiriman';
        $summary['belum']++;
      } elseif ($hasPendingChanges) {
        $row['status_key'] = 'pending';
        $row['status_label'] = 'Perlu sinkronisasi';
        $row['status_note'] = 'Perubahan SIPP belum dikirim';
        $summary['pending']++;
      } else {
        $row['status_key'] = 'sinkron';
        $row['status_label'] = 'Sudah sinkron';
        $row['status_note'] = $hasHeartbeat ? 'Tidak ada perubahan tertunda' : 'Pemeriksaan perubahan belum aktif';
        $summary['sinkron']++;
      }

      if (!$hasHeartbeat) {
        $row['connection_key'] = 'unknown';
        $row['connection_label'] = 'Belum terpantau';
      } else {
        $secondsSinceSeen = time() - strtotime($row['last_seen_at']);
        if ($secondsSinceSeen <= 600) {
          $row['connection_key'] = 'online';
          $row['connection_label'] = 'Aktif';
        } elseif ($secondsSinceSeen <= 86400) {
          $row['connection_key'] = 'recent';
          $row['connection_label'] = 'Terpantau hari ini';
        } else {
          $row['connection_key'] = 'offline';
          $row['connection_label'] = 'Tidak aktif';
        }
      }

      $monitoringRows[] = $row;
      $summary['total']++;
    }
    mysqli_free_result($monitoringResult);
  } else {
    $schemaReady = false;
    error_log('KASUARI monitoring query failed: ' . mysqli_error($koneksi));
  }
}

$statusPriority = array('pending' => 0, 'belum' => 1, 'sinkron' => 2);
usort($monitoringRows, function ($left, $right) use ($statusPriority) {
  $statusOrder = $statusPriority[$left['status_key']] - $statusPriority[$right['status_key']];
  return $statusOrder !== 0 ? $statusOrder : strcasecmp($left['nama'], $right['nama']);
});
?>

<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-page-title ks-monitoring-title">
      <div>
        <h1>Monitoring Sinkronisasi</h1>
        <p>Pantau pengiriman dan perubahan data SIPP pada setiap satuan kerja.</p>
      </div>
      <button class="btn btn-outline-primary" type="button" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
        Perbarui
      </button>
    </div>

    <?php if (!$schemaReady): ?>
      <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <div>
          <strong>Data monitoring belum dapat disiapkan.</strong><br>
          Jalankan berkas <code>database/monitoring_sinkronisasi.sql</code> pada database Kasuari Pusat.
        </div>
      </div>
    <?php else: ?>
      <div class="ks-monitoring-summary" aria-label="Ringkasan sinkronisasi">
        <article class="ks-monitoring-stat total">
          <span class="ks-monitoring-stat-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
          <div><strong><?php echo number_format($summary['total'], 0, ',', '.'); ?></strong><span>Satker Aktif</span></div>
        </article>
        <article class="ks-monitoring-stat synced">
          <span class="ks-monitoring-stat-icon"><i class="bi bi-cloud-check" aria-hidden="true"></i></span>
          <div><strong><?php echo number_format($summary['sinkron'], 0, ',', '.'); ?></strong><span>Sudah Sinkron</span></div>
        </article>
        <article class="ks-monitoring-stat pending">
          <span class="ks-monitoring-stat-icon"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></span>
          <div><strong><?php echo number_format($summary['pending'], 0, ',', '.'); ?></strong><span>Ada Perubahan</span></div>
        </article>
        <article class="ks-monitoring-stat never">
          <span class="ks-monitoring-stat-icon"><i class="bi bi-cloud-slash" aria-hidden="true"></i></span>
          <div><strong><?php echo number_format($summary['belum'], 0, ',', '.'); ?></strong><span>Belum Sinkron</span></div>
        </article>
      </div>

      <section class="kasuari-panel ks-monitoring-panel">
        <div class="ks-monitoring-toolbar">
          <div>
            <h2>Status Satker</h2>
            <p>Status perubahan tersedia setelah satker memperbarui aplikasi dan membuka dashboard sinkronisasi.</p>
          </div>
          <div class="ks-monitoring-filters">
            <label class="visually-hidden" for="monitoringStatusFilter">Filter status</label>
            <select class="form-select" id="monitoringStatusFilter">
              <option value="">Semua status</option>
              <option value="pending">Perlu sinkronisasi</option>
              <option value="belum">Belum sinkron</option>
              <option value="sinkron">Sudah sinkron</option>
            </select>
            <label class="ks-monitoring-search" for="monitoringSearch">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input class="form-control" id="monitoringSearch" type="search" placeholder="Cari satker...">
            </label>
          </div>
        </div>

        <div class="table-responsive ks-monitoring-table-wrap">
          <table class="table align-middle ks-monitoring-table">
            <thead>
              <tr>
                <th>Satker</th>
                <th>Status Data</th>
                <th class="ks-monitoring-optional">Perkara Lokal / Pusat</th>
                <th>Terakhir Sinkron</th>
                <th class="ks-monitoring-optional">Perubahan SIPP</th>
                <th class="ks-monitoring-optional">Koneksi</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="monitoringRows">
              <?php foreach ($monitoringRows as $row): ?>
                <?php $searchName = strtolower($row['nama'] . ' ' . $row['kode']); ?>
                <tr data-status="<?php echo htmlspecialchars($row['status_key'], ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8'); ?>">
                  <td>
                    <div class="ks-monitoring-satker">
                      <span class="ks-monitoring-satker-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
                      <div>
                        <strong><?php echo htmlspecialchars(kasuari_monitoring_short_name($row['nama']), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>Kode <?php echo (int) $row['id']; ?><?php echo !empty($row['app_version']) ? ' - v' . htmlspecialchars($row['app_version'], ENT_QUOTES, 'UTF-8') : ''; ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="ks-monitoring-status <?php echo htmlspecialchars($row['status_key'], ENT_QUOTES, 'UTF-8'); ?>">
                      <?php echo htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <small class="ks-monitoring-note"><?php echo htmlspecialchars($row['status_note'], ENT_QUOTES, 'UTF-8'); ?></small>
                  </td>
                  <td class="ks-monitoring-optional">
                    <span class="ks-monitoring-count">
                      <?php echo $row['last_seen_at'] ? number_format((int) $row['local_perkara_count'], 0, ',', '.') : '-'; ?>
                      <small>/ <?php echo number_format((int) $row['central_perkara_count'], 0, ',', '.'); ?></small>
                    </span>
                  </td>
                  <td><span class="ks-monitoring-time"><?php echo htmlspecialchars(kasuari_monitoring_datetime($row['last_sync_at']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td class="ks-monitoring-optional"><span class="ks-monitoring-time"><?php echo htmlspecialchars(kasuari_monitoring_datetime($row['local_data_changed_at']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td class="ks-monitoring-optional">
                    <span class="ks-monitoring-connection <?php echo htmlspecialchars($row['connection_key'], ENT_QUOTES, 'UTF-8'); ?>">
                      <i class="bi bi-circle-fill" aria-hidden="true"></i>
                      <?php echo htmlspecialchars($row['connection_label'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <small class="ks-monitoring-note"><?php echo htmlspecialchars(kasuari_monitoring_datetime($row['last_seen_at']), ENT_QUOTES, 'UTF-8'); ?></small>
                  </td>
                  <td class="text-center">
                    <a class="kasuari-action-link ks-view-action" href="register_perkara_satker?satker_id=<?php echo (int) $row['id']; ?>" title="Lihat perkara satker" aria-label="Lihat perkara <?php echo htmlspecialchars(kasuari_monitoring_short_name($row['nama']), ENT_QUOTES, 'UTF-8'); ?>">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="monitoringEmpty" class="d-none"><td colspan="7" class="kasuari-empty-state">Tidak ada satker yang sesuai dengan filter.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>
  </div>
</div>

<script>
  (function () {
    var statusFilter = document.getElementById('monitoringStatusFilter');
    var searchInput = document.getElementById('monitoringSearch');
    if (!statusFilter || !searchInput) return;

    function applyMonitoringFilter() {
      var selectedStatus = statusFilter.value;
      var search = searchInput.value.trim().toLowerCase();
      var rows = document.querySelectorAll('#monitoringRows tr[data-status]');
      var visible = 0;
      rows.forEach(function (row) {
        var statusMatches = selectedStatus === '' || row.dataset.status === selectedStatus;
        var searchMatches = search === '' || row.dataset.search.indexOf(search) !== -1;
        var show = statusMatches && searchMatches;
        row.classList.toggle('d-none', !show);
        if (show) visible++;
      });
      document.getElementById('monitoringEmpty').classList.toggle('d-none', visible !== 0);
    }

    statusFilter.addEventListener('change', applyMonitoringFilter);
    searchInput.addEventListener('input', applyMonitoringFilter);
  }());
</script>

<?php include_once("sys/footer.php"); ?>
