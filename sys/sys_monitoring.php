<?php
if (!function_exists('kasuari_monitoring_ensure_schema')) {
  function kasuari_monitoring_ensure_schema($connection)
  {
    $createSql = "CREATE TABLE IF NOT EXISTS sync_monitoring (
      pn_id INT(10) UNSIGNED NOT NULL,
      last_seen_at DATETIME DEFAULT NULL,
      local_perkara_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
      local_data_changed_at DATETIME DEFAULT NULL,
      local_signature CHAR(64) DEFAULT NULL,
      last_sync_at DATETIME DEFAULT NULL,
      last_sync_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
      last_sync_signature CHAR(64) DEFAULT NULL,
      app_version VARCHAR(30) DEFAULT NULL,
      PRIMARY KEY (pn_id),
      KEY last_seen_at (last_seen_at),
      KEY last_sync_at (last_sync_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($connection, $createSql)) {
      error_log('KASUARI gagal membuat tabel sync_monitoring: ' . mysqli_error($connection));
      return false;
    }

    $backfillSql = "INSERT INTO sync_monitoring (pn_id, last_sync_at, last_sync_count)
      SELECT latest.id_satker, latest.tanggal, latest.perkara
      FROM log_singkron latest
      INNER JOIN (
        SELECT id_satker, MAX(id) AS id
        FROM log_singkron
        WHERE id_satker IS NOT NULL
        GROUP BY id_satker
      ) last_log ON last_log.id=latest.id
      ON DUPLICATE KEY UPDATE
        last_sync_count=IF(sync_monitoring.last_sync_at IS NULL, VALUES(last_sync_count), sync_monitoring.last_sync_count),
        last_sync_at=COALESCE(sync_monitoring.last_sync_at, VALUES(last_sync_at))";

    if (!mysqli_query($connection, $backfillSql)) {
      error_log('KASUARI gagal mengisi riwayat awal sync_monitoring: ' . mysqli_error($connection));
      return false;
    }

    return true;
  }
}
?>
