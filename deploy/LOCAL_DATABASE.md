# Database Lokal KASUARI

Pemulihan lokal 16 November 2026 memakai penyimpanan MySQL terpisah karena
penyimpanan InnoDB XAMPP lama mengalami error baca yang menghentikan MySQL.
Database dan berkas lama tidak dihapus atau ditimpa.

- Alamat aplikasi: http://localhost/kasuari/
- MySQL khusus KASUARI: 127.0.0.1:3307, hanya menerima koneksi lokal.
- Penyimpanan: C:\xampp\mysql\kasuari-local-data
- Konfigurasi privat: sys/sys_koneksi.local.php, tidak masuk Git.
- Akun database aplikasi dibatasi pada database pemulihan, bukan akun root.

## Menyalakan Setelah Restart

1. Nyalakan Apache dari XAMPP.
2. Jalankan start_local_database.bat pada folder utama KASUARI.
3. Buka http://localhost/kasuari/.

Launcher tidak mengubah execution policy Windows, tidak membuat database baru,
dan tidak memakai folder penyimpanan XAMPP lama. Jika port 3307 telah terpakai,
pastikan prosesnya benar sebelum menjalankan aplikasi.

## Sumber Data

Backup lengkap yang berhasil dipulihkan: Downloads/dok_banding.sql, tanggal
15 Juli 2026. Hasil: 4.132 perkara dan 14 perkara banding.
File u1483703_db_kasuari.sql tanggal 16 November 2026 tidak digunakan sebagai
sumber pemulihan karena terputus saat ekspor dan berisi pesan error HTML.

Data yang lebih baru memerlukan backup lengkap atau pengiriman ulang satker.
Tabel portal SIPP dan status akta disiapkan, tetapi tidak diberi data contoh.
Status Belum disinkronkan tetap tampil sampai satker mengirim modul tambahan.

Konfigurasi dan salinan tabel hak akses sebelum perbaikan disimpan di
C:\Users\rubik\.codex\private-backups\kasuari, di luar direktori web.
Perubahan ini khusus komputer lokal, bukan perubahan server hosting.
