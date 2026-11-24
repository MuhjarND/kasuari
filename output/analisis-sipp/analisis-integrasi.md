# Analisis Integrasi SIPP Manokwari ke KASUARI

Tanggal pemeriksaan: 16 November 2026 (WIT).
Sumber: salinan lokal `C:/xampp/htdocs/sipp_manokwari`, aplikasi pusat
`C:/xampp/htdocs/kasuari`, dan client `C:/xampp/htdocs/kasuari_satker`.
Ini adalah analisis file dan dump SQL, bukan audit SIPP produksi.
Tidak ada data SIPP atau kode aplikasi yang diubah dalam pemeriksaan ini.

## Kesimpulan

Definisi menu dan struktur data dapat dipetakan. Seluruh fitur belum dapat
dipastikan berfungsi atau dipindahkan langsung: salinan kode SIPP tidak lengkap.
Implementasi yang paling sesuai untuk KASUARI adalah monitoring lintas satker
berdasarkan data SIPP, dengan SIPP tetap menjadi sumber pencatatan operasional.

## Hasil Inventaris

| Objek | Temuan |
|---|---|
| Tabel pada dump | 451 |
| Entri sys_menus | 392 |
| Menu published beserta rantai induk published | 106 |
| File PHP controller | 34 |
| File PHP model | 2 |
| File PHP view | 43 |
| Definisi template_dokumen | 836 baris |
| File RTF dalam resources/template | 123 |
| File PDF dalam resources/file | 501 |

392 entri tidak berarti 392 fitur terpisah. Ada menu induk, menu berulang untuk
jenis perkara berbeda, serta menu generik lingkungan peradilan lain. Angka 106
belum memperhitungkan sys_menu_rule, sys_group_rule, konfigurasi peradilan,
hak pengguna, serta pengaktifan dari kode. Tidak semua menu itu relevan bagi PA.

Pada 81 entri menu aktif yang mempunyai route, tidak ditemukan controller
dengan nama route yang sama di salinan ini. Ini indikator kelengkapan, bukan
jaminan route tersebut tidak tersedia di instalasi asli: alias routing dan
konfigurasi utama juga tidak tersedia di salinan lokal.

Folder tidak memuat index.php SIPP, application/config/routes.php,
application/config/database.php, dashboard controller, dan banyak dependensi
yang dirujuk controller. Contohnya perkara_list.php memanggil model
perkara/perkara, sedangkan c_efiling_banding.php memerlukan controller
api/Berpadu.php dan model efiling_banding yang tidak ditemukan pada salinan.

## Kelompok Menu Yang Teridentifikasi

- Home/dashboard dan daftar perkara berdasarkan tahapan.
- Perdata: register gugatan, permohonan, gugatan sederhana, banding, kasasi,
  PK, eksekusi, dan eksekusi hak tanggungan.
- Jurnal perkara: gugatan, permohonan, banding, kasasi, PK, eksekusi.
- Register induk keuangan dan penerimaan hak kepaniteraan.
- Jadwal sidang berdasarkan alur perkara.
- Laporan perdata agama dan mediasi.
- Delegasi.
- Arsip perkara.
- Antrian pihak, hakim/PP, serta display.
- Blanko/template dan template amar.
- e-Court: tingkat pertama, banding, perlawanan, kasasi, PK, verifikasi prodeo.
- e-Payment.
- Smart Majelis.
- Referensi hakim, panitera, jurusita, mediator, ruangan sidang, hari libur,
  dan biaya.
- Sistem: pengguna, konfigurasi, audit trail, backup, menu, dan monitoring sync.

Database juga memuat pidana, militer, TUN, kepailitan, jinayat, dan modul generik
lain. Keberadaan tabel/menu tidak membuktikan bahwa PA Manokwari menggunakannya.
Data anak pada perkara PA tidak disamakan dengan modul pidana anak.

## Data Dan Potensi Pengembangan

Jumlah berikut adalah baris data yang ditemukan pada dump, bukan angka layanan
saat ini. Satu perkara bisa memiliki banyak baris jadwal, biaya, atau dokumen.

| Fitur KASUARI yang dapat dikembangkan | Sumber utama | Bukti baris pada dump | Catatan implementasi |
|---|---|---:|---|
| Timeline perkara lengkap | perkara_penetapan, perkara_jadwal_sidang, perkara_putusan | 4.119 penetapan; 8.730 jadwal | Gabungkan peristiwa tanpa mengganti status aslinya. |
| Kalender sidang empat satker | perkara_jadwal_sidang | 8.730 | Tanggal, jam, agenda, ruangan, tunda, alasan penundaan. Bukan informasi live bila sync manual. |
| Monitoring mediasi | perkara_mediasi, perkara_jadwal_mediasi | 326; 699 | Mediator, mulai, hasil, jadwal, tanggal laporan. Definisi rekap mengikuti kode hasil sumber. |
| Monitoring minutasi dan BHT | perkara_putusan | 4.090 | Sinkronkan tanggal_minutasi, tanggal_bht, pemberitahuan per pihak. Jangan simpulkan BHT dari umur putusan. |
| Monitoring ikrar talak | perkara_ikrar_talak | 483 | Jadwal dan realisasi ikrar dipisahkan. |
| Status akta cerai | perkara_akta_cerai | 1.876 | Sudah ada perubahan kode KASUARI. Penerapan produksi dan kelengkapan setiap satker tetap perlu dibuktikan. Tidak membaca unduhan EAC. |
| Monitoring kasasi/PK | perkara_kasasi, perkara_pk, tabel detail | 2 kasasi; 0 PK | Struktur tersedia; perlu fixture dan pilot karena contoh PK kosong. PK dapat berulang pada satu perkara. |
| Monitoring eksekusi | perkara_eksekusi, perkara_eksekusi_detil | 1 eksekusi induk | Permohonan, teguran, sita, lelang/riil, hambatan, cabut. Perlu contoh tambahan sebelum rekap kompleks. |
| Beban kerja hakim, PP, jurusita | perkara_hakim_pn, perkara_panitera_pn, perkara_jurusita | 9.272; 4.549; 3.957 | Pisahkan penugasan aktif dan riwayat penggantian, hindari menghitung satu perkara berkali-kali. |
| Monitoring keuangan perkara | perkara_biaya, kategori_biaya, jenis_biaya, saldo_awal | 39.997 transaksi | Baca penerimaan/pengeluaran per tahap. Rekonsiliasi saldo sebelum menyebut saldo final atau uang belum dikembalikan. |
| Monitoring delegasi dan relaas | delegasi_masuk/keluar, delegasi_proses_*, perkara_pelaksanaan_relaas | 234 masuk; 85 keluar; 4.908 relaas | Awalnya metadata proses saja. Sesuaikan ID satker dari referensi sumber. |
| Arsip dan peminjaman | arsip, arsip_pinjam | 2.781 arsip; 1 peminjaman | Ruang/lemari/rak, kelengkapan, masuk arsip, pinjam/kembali. |
| Kelengkapan berkas upaya hukum | perkara_bundel, ref_document_bundel | 90 bundel | Cocok untuk checklist berkas; ketersediaan file berbeda dari metadata yang tercatat. |
| Monitoring publikasi putusan | dirput_dokumen, dirput_perkara, dirput_pengajuan/antrian | 7.675 dokumen; 2.024 perkara dirput | published, flag_sync, link_dirput perlu dipetakan. Status antrian berbeda dari sudah terpublikasi. |
| Monitoring pendaftaran elektronik/prodeo | perkara_efiling, perkara_efiling_id, ecourt_prodeo | 1.839; 1.815; 31 | Tampilkan status lokal SIPP. Tidak menganggapnya API e-Court/EAC real time. |
| Statistik perceraian dan jenis perkara | perkara, perkara_akta_cerai, faktor_perceraian | Data tersedia | Analisis agregat per periode/satker. Bedakan tahun daftar, putus, dan terbit akta. |
| Pemeriksaan kualitas data | kolom proses dan metadata yang disinkronkan | Lintas tabel | Contoh: tanggal tidak valid, berkas belum lengkap, putus tanpa minutasi tercatat. Indikator temuan, bukan putusan hukum. |
| Laporan konsolidasi satker | Gabungan sumber fitur di atas | Lintas tabel | Sisa awal, masuk, putus, sisa akhir membutuhkan rekonstruksi posisi periode, bukan hanya status hari ini. |

## Perbandingan Dengan Sinkronisasi KASUARI Saat Ini

Client mengirim perkara, perkara_banding, perkara_banding_detil,
perkara_putusan, dan pihak. Metadata akta ditambahkan melalui alur tersendiri.
Data pihak lengkap yang dikirim masih dibatasi pihak pada perkara banding sejak
2023; jangan menganggap semua profil pihak seluruh perkara sudah di pusat.

Putusan tingkat pertama saat ini mengirim perkara_id, tanggal_putusan, dan
amar_putusan. Tanggal minutasi, BHT, hasil putusan rinci, serta tanggal pemberitahuan
belum ikut payload tersebut. Jadwal, mediasi, biaya, arsip, kasasi, PK, dan eksekusi
juga belum memiliki section sinkronisasi tersendiri.

Laporan publikasi banding KASUARI saat ini memakai tgl_minutasi/minutasi_banding.
Ini belum membuktikan putusan terpublikasi di Direktori Putusan. Tabel dirput SIPP
dapat dipakai untuk monitoring publikasi tingkat pertama setelah pemetaan;
publikasi produk PTA tetap memerlukan sumber produk PTA yang sesuai.

Template RTF SIPP dan template KASUARI mempunyai pola variabel dan helper berbeda.
836 definisi template tidak membuktikan 836 file tersedia; 123 RTF juga tersebar
di beberapa lingkungan peradilan. Template tidak bisa langsung diimpor massal
tanpa memeriksa kesesuaian variabel, format dokumen, sumber, dan relevansinya.

## Batas Integrasi

KASUARI dapat membangun tampilan konsolidasi berbasis salinan data. Pendaftaran
perkara, penetapan, penandatanganan, pencatatan penerimaan/pengeluaran, perubahan
putusan, penerbitan akta, dan transaksi pembayaran membutuhkan alur otoritatif
sendiri. Jangan membuat penulisan dua arah ke SIPP hanya untuk menyalin menu.

Operasi e-Court, e-Payment, EAC, dan pengiriman ke MA memerlukan jalur integrasi
yang memang tersedia dan diizinkan bagi aplikasi. Kode parsial dan keberadaan
tabel lokal tidak membuktikan ada API yang dapat dipakai KASUARI.

Metadata dokumen dapat ikut sync database, tetapi isi PDF memerlukan akses
filesystem/API dokumen, penyimpanan privat, dan izin pengguna yang sesuai.
Informasi status tidak disamakan dengan dokumen asli yang sudah ditandatangani.

## Arsitektur Pengembangan

1. Tetap gunakan SIPP sebagai sumber, client satker membaca dengan SELECT.
2. Satker mengirim ke domain pusat melalui HTTPS saat tombol Kirimkan Data ditekan.
3. Tambahkan bagian data sesuai modul, jangan mengirim seluruh 451 tabel.
4. Setiap relasi menggunakan kode satker dan primary key sumber yang benar.
   Beberapa modul mempunyai banyak record per perkara; kuncinya tidak selalu perkara_id.
5. Periksa kemampuan kolom dan versi skema sebelum membuat paket. Tabel/kolom
   hilang harus menghasilkan keterangan belum tersedia, bukan nol yang menyesatkan.
6. Publikasikan snapshot lengkap saja, dukung retry dan idempotensi. Jangan
   menimpa snapshot lengkap dengan kiriman parsial atau hasil baca gagal.
7. Simpan waktu sync setiap modul; sync perkara sukses belum membuktikan semua modul terbarui.
8. Tambahkan monitoring perubahan pada tabel baru. Untuk pertumbuhan jadwal dan
   transaksi, siapkan delta/cursor, pembatalan/penghapusan, dan rekonsiliasi penuh.
9. Pisahkan wewenang: pimpinan melihat agregat, kepaniteraan melihat detail yang
   diperlukan, dan administrator mengatur akses. Akta tetap administrator saja
   sesuai keputusan pengguna saat ini.
10. Uji contoh empat satker, perbedaan skema, data kosong, duplikasi, retry,
    tanggal kosong, pemutusan jaringan, dan hasil laporan historis.

## Tahapan Yang Disarankan

1. Lengkapi detail Register Perkara: PMH/PP/jurusita, timeline, jadwal,
   hasil putusan, minutasi/BHT, pemberitahuan, ikrar, dan status akta.
2. Tambahkan pengawasan: mediasi, beban kerja, temuan kelengkapan, rekap
   masuk/putus/sisa per satker, dan laporan periodik.
3. Tambahkan monitoring kasasi/PK, eksekusi, delegasi, bundel, serta arsip.
4. Tambahkan keuangan setelah aturan rekonsiliasi dan hak akses disepakati.
5. Pertimbangkan integrasi layanan eksternal hanya setelah kontrak akses tersedia.

Awali satu modul dan PA Manokwari sebagai pilot, lalu validasi Sorong, Kaimana,
dan Fakfak sebelum memperluas. Hindari satu pembaruan besar yang menyalin seluruh SIPP.

## Lampiran

- `inventaris_menu_sipp.csv`: semua 392 definisi menu, hierarki, route,
  flag publikasi, dan indikator controller bernama sama pada salinan lokal.
- `inventaris_tabel_sipp.csv`: semua 451 tabel, daftar kolom, jumlah baris dump,
  serta nomor baris sumber SQL.

Lampiran tidak mengekspor isi record perkara, identitas pihak, password, atau
konten dokumen. Hanya metadata struktur dan hitungan untuk analisis ini.
