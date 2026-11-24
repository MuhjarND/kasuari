# Pembaruan Portal SIPP KASUARI

## Pusat terlebih dahulu

1. Deploy kode aplikasi pusat, katalog dan inventaris menu SIPP.
2. Jalankan `database/sipp_portal.sql` dan `database/akta_cerai.sql` pada
   database pusat. Health juga dapat membuat tabel bila akun mempunyai izin CREATE.
3. Login administrator, pilih **SIPP > nama satker > Koneksi satker**.
4. Unduh konfigurasi token untuk masing-masing satker. Token satu satker
   tidak boleh dipakai oleh satker lain. Mengunduh ulang mengganti token lama.
5. Alamat SIPP satker opsional untuk membuka menu operasional asal.

## Client pada empat satker

1. Cadangkan aplikasi client. Tunggu pengiriman yang sedang berjalan selesai.
2. Ekstrak ZIP pada folder `kasuari_satker` dengan struktur folder tetap.
3. Pasang konfigurasi token yang diunduh dari pusat sebagai
   `kasuari_satker/sys/sys_sipp.local.php` pada satker yang sesuai.
4. Jangan menimpa sys_koneksi.php atau sys_config.php. Paket tidak memuat keduanya.
5. Pastikan tujuan pusat `https://kasuari.pta-papuabarat.go.id/` dan PHP cURL
   dapat memverifikasi HTTPS. Jalankan `php tools/cek_koneksi.php` bila diperlukan.
6. Buka dashboard satker lalu tekan **Kirimkan Data**. Tetap sinkronisasi manual.
7. Periksa **SIPP > satker > Ringkasan**: jumlah record dan waktu per modul,
   serta status sumber yang belum tersedia pada versi SIPP tersebut.

Data tambahan dibaca hanya dari kolom katalog, tanpa mengubah SIPP. Tidak ada
password pengguna, NIK, alamat, telepon, file PDF, atau kredensial layanan yang
ikut paket data portal. Nama petugas dan nomor perkara adalah metadata internal.

Paket memakai PHP 5.6+ untuk client dan PHP 7.4+ pada pusat. Catatan akta yang
ditambahkan sebelumnya tetap dikirim. Semua snapshot portal dipublikasikan pada
finalisasi yang sama; data lama tetap terlihat jika pengiriman terputus.
Dataset kosong berbeda dengan tabel/kolom yang tidak tersedia. Koreksi dan
penghapusan record sumber tercermin setelah snapshot penuh berikutnya berhasil.
Snapshot konsisten memerlukan tabel sumber InnoDB (seluruh 48 tabel pada contoh
Manokwari menggunakan InnoDB). Client menggunakan transaksi baca REPEATABLE READ.

Token belum dipasang: pengiriman perkara/akta lama tetap dapat selesai, tetapi
pesan hasil menyebut bahwa modul tambahan SIPP dilewati. Ini bukan bukti portal
telah tersinkron. Pasang token, kemudian kirim ulang.

SIPP tetap menjadi aplikasi pencatatan operasional. Menu sumber disediakan
untuk mengakses SIPP asli sesuai akun sumber. KASUARI tidak mendaftarkan perkara,
memproses pembayaran, menerbitkan akta, menandatangani produk, atau mengubah putusan.
Status EAC/e-Court yang terlihat berasal dari catatan lokal SIPP, bukan panggilan
langsung ke sistem MA.

Pengiriman awal lebih besar karena memuat seluruh periode pada 48 modul.
Kolom opsional yang tidak tersedia akan disebutkan pada Pemeriksaan data.
Nomor perkara yang sama/ID yang sama di satker lain tetap terpisah.

## Pemeriksaan

- Cari satu perkara dan buka detail beserta riwayat terkait.
- Bandingkan jadwal, minutasi/BHT, mediasi dan biaya dengan SIPP.
- Cek filter tanggal sesuai tanggal utama masing-masing modul.
- Periksa ekspor CSV dan rekap perkara dari tanggal daftar/putusan, bukan status akhir.
- Data yang belum tersedia tidak boleh dinyatakan nol aktivitas.
- Gunakan file token privat dan cadangkan database pusat sesuai kebijakan satker.
