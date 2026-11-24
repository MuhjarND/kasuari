-- Tambahan pusat saja. Tidak mengubah tabel atau data SIPP satker.
CREATE TABLE IF NOT EXISTS kasuari_akta_cerai (
  pn_id INT UNSIGNED NOT NULL,
  perkara_id BIGINT UNSIGNED NOT NULL,
  nomor_akta_cerai VARCHAR(255) NULL,
  tgl_akta_cerai DATE NULL,
  tgl_penyerahan_akta_cerai DATE NULL,
  tgl_penyerahan_akta_cerai_pihak2 DATE NULL,
  pihak2_tersedia TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (pn_id, perkara_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_akta_cerai_stage (
  pn_id INT UNSIGNED NOT NULL,
  request_id CHAR(40) NOT NULL,
  perkara_id BIGINT UNSIGNED NOT NULL,
  nomor_akta_cerai VARCHAR(255) NULL,
  tgl_akta_cerai DATE NULL,
  tgl_penyerahan_akta_cerai DATE NULL,
  tgl_penyerahan_akta_cerai_pihak2 DATE NULL,
  pihak2_tersedia TINYINT UNSIGNED NOT NULL DEFAULT 1,
  received_at DATETIME NOT NULL,
  PRIMARY KEY (pn_id, request_id, perkara_id),
  KEY received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_akta_cerai_sync (
  pn_id INT UNSIGNED NOT NULL,
  request_id CHAR(40) NOT NULL,
  synced_at DATETIME NOT NULL,
  row_count INT UNSIGNED NOT NULL,
  PRIMARY KEY (pn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
