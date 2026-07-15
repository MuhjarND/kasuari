-- Jalankan pada database Kasuari Pusat setelah membuat backup.
-- Perubahan ini menyelaraskan kolom sinkronisasi dengan data SIPP yang lebih panjang
-- dan memastikan karakter Unicode dapat disimpan tanpa memotong isi perkara.

SET NAMES utf8mb4;

ALTER TABLE perkara
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY nomor_perkara VARCHAR(250) NOT NULL,
  MODIFY nomor_indeks VARCHAR(250) NULL,
  MODIFY nomor_surat VARCHAR(150) NULL,
  MODIFY pihak1_text LONGTEXT NULL,
  MODIFY pengacara_pihak1 LONGTEXT NULL,
  MODIFY pihak2_text LONGTEXT NULL,
  MODIFY pengacara_pihak2 LONGTEXT NULL,
  MODIFY pihak3_text LONGTEXT NULL,
  MODIFY pengacara_pihak3 LONGTEXT NULL,
  MODIFY pihak4_text LONGTEXT NULL,
  MODIFY pengacara_pihak4 LONGTEXT NULL,
  MODIFY para_pihak LONGTEXT NULL,
  MODIFY posita LONGTEXT NULL,
  MODIFY petitum LONGTEXT NULL;

ALTER TABLE perkara_banding
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY nomor_perkara_pn VARCHAR(250) NOT NULL,
  MODIFY pihak_pembanding BIGINT NULL,
  MODIFY pemohon_banding LONGTEXT NULL,
  MODIFY para_pihak LONGTEXT NULL,
  MODIFY hakim1_banding VARCHAR(255) NULL,
  MODIFY hakim2_banding VARCHAR(255) NULL,
  MODIFY hakim3_banding VARCHAR(255) NULL,
  MODIFY hakim4_banding VARCHAR(255) NULL,
  MODIFY hakim5_banding VARCHAR(255) NULL,
  MODIFY panitera_pengganti_banding VARCHAR(255) NULL,
  MODIFY amar_putusan_banding LONGTEXT NULL;

ALTER TABLE perkara_banding_detil
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY status_pihak_text LONGTEXT NULL,
  MODIFY urutan_banding BIGINT NULL,
  MODIFY pihak_asal BIGINT NULL,
  MODIFY pihak_asal_text LONGTEXT NULL,
  MODIFY pihak_nama LONGTEXT NULL,
  MODIFY pemohon_nama LONGTEXT NULL,
  MODIFY pemohon_nomor_surat VARCHAR(255) NULL,
  MODIFY pemohon_banding LONGTEXT NULL;

ALTER TABLE perkara_putusan
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY perkara_id BIGINT UNSIGNED NOT NULL,
  MODIFY amar_putusan LONGTEXT NULL;

ALTER TABLE pihak
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE log_singkron
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_requests (
  request_id CHAR(40) NOT NULL,
  pn_id INT UNSIGNED NOT NULL,
  completed_at DATETIME NOT NULL,
  jumlah_perkara INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (request_id),
  KEY pn_id_completed_at (pn_id, completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
