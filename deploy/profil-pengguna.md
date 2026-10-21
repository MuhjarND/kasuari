# Profil pengguna dan mapping chatbot

Folder aplikasi utama adalah `C:/xampp/htdocs/kasuari`, yang dihosting pada
`https://kasuari.pta-papuabarat.go.id`. Checkout di subfolder `kasuari/` tidak
diubah; perubahan template dokumen di checkout tersebut dipertahankan.

## Database

1. Jalankan `database/user_profiles.sql` pada database **Kasuari**.
   Script membuat tabel foto profil dan indeks unik username tanpa mengubah
   password, role, atau status `block` pengguna. Script dapat diulang.
   Jika ada username ganda, selesaikan duplikasinya sebelum membuat indeks.
2. Jalankan `database/sql/map_kasuari_all_users.sql` dari project **chatbot**
   pada database **chatbot**. Script tidak membuat pegawai baru. NIP lima akun
   kepaniteraan diisi dari dump `employees.sql` dan `users.sql` yang cocok.
   NIP Baida Makasar (`197207292001122001`) dan Erwin Romel
   (`196712151993031006`) diambil dari `Rekap Data Pegawai (1).xlsx`, sumber
   project Kerjaan, masing-masing baris 27 dan 18. Semua 22 NIP sudah terisi.
   Mapping hanya diproses jika NIP cocok ke tepat satu pegawai. NIP yang
   belum ditemukan atau terduplikasi ditampilkan pada hasil pemeriksaan.
   Mapping yang sudah nonaktif tetap nonaktif ketika script diulang.

## Kode aplikasi Kasuari

Unggah perubahan berikut dari folder utama:

- `modul/profil.php`
- `index.php`
- `modul/validateAuth.php`
- `sys/sys_profile.php`
- `sys/sys_password.php`
- `sys/header.php`
- `assets/css/kasuari-profile.css`
- `uploads/profiles/.htaccess`

Pastikan direktori `uploads/profiles` dapat ditulis oleh PHP. Unggahan runtime
tidak disimpan di Git. Backup direktori ini bersama database `sys_user_profiles`.
PHP memerlukan ekstensi mysqli/mysqlnd, fileinfo, dan GD untuk foto.

## Perilaku

Setelah login, pilih **Profil Saya** melalui menu pengguna atau avatar sidebar.
Pengguna dapat mengubah nama lengkap, username, email, password, dan foto sendiri.
Perubahan identitas/password memerlukan password saat ini dan CSRF yang valid.
Foto hanya menerima gambar JPG/PNG/WEBP maksimal 2 MB, disimpan ulang sebagai
JPEG maksimal 512 piksel agar metadata dan konten tambahan tidak tersimpan.
ID pengguna berasal dari sesi; input `userid`, `group`, dan `block` tidak dapat
mengganti pemilik atau hak akses. Halaman memeriksa status aktif dari database.
Router menolak penimpaan superglobal dari URL dan form login tidak menyalin
input menjadi variabel sesi. Nama modul dibatasi agar tetap berada di folder modul.

Login biasa dan autologin sama-sama menolak `block=1`. Perubahan username atau
password tidak mengubah ID akun, sehingga mapping chatbot berbasis ID tetap
berlaku. Password lama tidak direset massal. Pengguna yang tidak mengetahui
password saat ini perlu meminta administrator melakukan reset melalui fitur
Kelola Pengguna yang sudah ada.

## Verifikasi lokal

`php tools/tests/profile_test.php` menjalankan MariaDB sementara pada port acak
dan dua server PHP lokal, dengan akun fixture. Tidak memakai konfigurasi privat,
database lokal utama, atau hosting. Proses dihentikan setelah tes selesai.
Tambahkan `--preview` untuk mempertahankan preview fixture; informasi preview
tersimpan di `output/profile-tests/preview.json`. Membuat file `stop` di direktori
fixture akan menghentikan preview. Semua data di direktori tersebut adalah data
pengujian, bukan data pengguna produksi.
