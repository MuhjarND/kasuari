-- Jalankan pada database Kasuari Pusat setelah membuat backup.
-- Mengisi hakim dari kolom terstruktur SIPP dan memakai teks majelis sebagai
-- fallback untuk perkara yang pernah disinkronkan oleh versi lama.

SET NAMES utf8mb4;

UPDATE master_variabel
SET var_model='sql',
    var_tabel=NULL,
    var_field=NULL,
    var_sql_data="SELECT COALESCE(
      NULLIF(TRIM(hakim1_banding), ''),
      NULLIF(TRIM(REPLACE(
        SUBSTRING_INDEX(
          REPLACE(REPLACE(majelis_hakim_banding, '<br/>', '<br>'), '<br />', '<br>'),
          '<br>', 1
        ),
        'Hakim Ketua:', ''
      )), '')
    ) AS DATA
    FROM perkara_banding
    WHERE perkara_id=#perkara_id# AND pn_id=#pn_id#"
WHERE var_nomor='0032';

UPDATE master_variabel
SET var_model='sql',
    var_tabel=NULL,
    var_field=NULL,
    var_sql_data="SELECT COALESCE(
      NULLIF(TRIM(hakim2_banding), ''),
      NULLIF(TRIM(REPLACE(
        SUBSTRING_INDEX(
          SUBSTRING_INDEX(
            REPLACE(REPLACE(majelis_hakim_banding, '<br/>', '<br>'), '<br />', '<br>'),
            '<br>', 2
          ),
          '<br>', -1
        ),
        'Hakim Anggota:', ''
      )), '')
    ) AS DATA
    FROM perkara_banding
    WHERE perkara_id=#perkara_id# AND pn_id=#pn_id#"
WHERE var_nomor='0033';

UPDATE master_variabel
SET var_model='sql',
    var_tabel=NULL,
    var_field=NULL,
    var_sql_data="SELECT COALESCE(
      NULLIF(TRIM(hakim3_banding), ''),
      NULLIF(TRIM(REPLACE(
        SUBSTRING_INDEX(
          SUBSTRING_INDEX(
            REPLACE(REPLACE(majelis_hakim_banding, '<br/>', '<br>'), '<br />', '<br>'),
            '<br>', 3
          ),
          '<br>', -1
        ),
        'Hakim Anggota:', ''
      )), '')
    ) AS DATA
    FROM perkara_banding
    WHERE perkara_id=#perkara_id# AND pn_id=#pn_id#"
WHERE var_nomor='0034';

-- Semua relasi pihak harus menggunakan id lokal dan kode satker agar pihak
-- dari dua satker yang memiliki id sama tidak tercampur.
UPDATE master_variabel
SET var_sql_data=REPLACE(
  REPLACE(var_sql_data,
    'ON p.id=pbd.pemohon_id',
    'ON p.id=pbd.pemohon_id AND p.pn_id=pbd.pn_id'),
  'ON b.id=a.pihak_id',
  'ON b.id=a.pihak_id AND b.pn_id=a.pn_id'
)
WHERE var_nomor='0067'
  AND var_sql_data NOT LIKE '%p.pn_id=pbd.pn_id%';

UPDATE master_variabel
SET var_sql_data=REPLACE(
  REPLACE(var_sql_data,
    'ON p.id = pbd.pemohon_id',
    'ON p.id = pbd.pemohon_id AND p.pn_id = pbd.pn_id'),
  'ON b.id = a.pihak_id',
  'ON b.id = a.pihak_id AND b.pn_id = a.pn_id'
)
WHERE var_nomor='0068'
  AND var_sql_data NOT LIKE '%p.pn_id = pbd.pn_id%';

UPDATE master_variabel
SET var_sql_data=REPLACE(
  var_sql_data,
  'ON a.pihak_id = b.id',
  'ON a.pihak_id = b.id AND a.pn_id = b.pn_id'
)
WHERE var_nomor IN ('9804','9805','9806')
  AND var_sql_data NOT LIKE '%a.pn_id = b.pn_id%';

UPDATE master_variabel
SET var_sql_data=REPLACE(
  var_sql_data,
  'ON b.id = a.pihak_id',
  'ON b.id = a.pihak_id AND b.pn_id = a.pn_id'
)
WHERE var_nomor='9807'
  AND var_sql_data NOT LIKE '%b.pn_id = a.pn_id%';

-- Variabel berikut sebelumnya ditandai manual meskipun sumbernya tersedia
-- langsung pada tabel perkara_banding hasil sinkronisasi SIPP.
UPDATE master_variabel
SET var_model='text',
    var_tabel='perkara_banding',
    var_field='pemberitahuan_putusan_pn',
    var_fungsi_nama='tanggal_indonesia'
WHERE var_nomor='0026';

UPDATE master_variabel
SET var_model='text',
    var_tabel='perkara_banding',
    var_field='amar_putusan_banding',
    var_fungsi_nama=NULL
WHERE var_nomor='0083';

-- Tanggal penyerahan harus berupa tanggal lengkap, bukan hanya tahun.
UPDATE master_variabel
SET var_default_data='#0076#'
WHERE var_nomor='0086';

-- Bersihkan karakter hasil salah encoding pada contoh amar bawaan.
UPDATE master_variabel
SET var_default_data='1.\t.....; 2.\t.....; 3.\tMembebankan kepada Penggugat untuk membayar biaya perkara dalam tingkat pertama sejumlah Rp........ (........ rupiah);'
WHERE var_nomor='0037';

UPDATE master_variabel
SET var_default_data='1.\tMengabulkan gugatan Penggugat; 2.\tMenjatuhkan talak satu ba''in shughra Tergugat (...................) terhadap Penggugat (..................); 3.\tMenghukum Tergugat untuk membayar nafkah anak bernama ................ kepada Penggugat sejumlah Rp........ (........ rupiah) setiap bulan sampai anak berusia 21 (dua puluh satu) tahun atau setelah anak tersebut hidup mandiri; 4.\tMembebankan kepada Penggugat untuk membayar biaya perkara dalam tingkat pertama sejumlah Rp........ (........ rupiah);'
WHERE var_nomor='0039';
