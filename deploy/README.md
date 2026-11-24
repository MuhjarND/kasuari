# Deployment Kasuari

Alamat aplikasi pusat:

```text
https://kasuari.pta-papuabarat.go.id/
```

Konfigurasi hosting sudah melayani aplikasi di domain utama. Tidak diperlukan
pemasang atau perubahan aturan domain tambahan dari repository ini.

## Konfigurasi database privat

Sebelum memperbarui kode di server, pastikan `sys/sys_koneksi.local.php`
berisi kredensial database hosting yang benar. File harus mengembalikan array
seperti `sys/sys_koneksi.local.example.php`. Jangan menyalin seluruh isi file
koneksi lama karena file tersebut juga menjalankan koneksi MySQL.

File privat tidak dilacak Git. Buat melalui SSH/File Manager atau unggah secara
terpisah. Jangan memakai `git add -f`. Contoh konfigurasi hanya berisi placeholder.
Alternatifnya, gunakan environment `KASUARI_DB_HOST`, `KASUARI_DB_USER`,
`KASUARI_DB_PASSWORD`, `KASUARI_DB_NAME`, dan `KASUARI_DB_PORT`.
File privat memiliki prioritas lebih tinggi daripada environment.

Tidak ada username, password, atau nama database bawaan dalam kode koneksi.
Instalasi tanpa konfigurasi privat/environment menampilkan pesan konfigurasi
belum lengkap. Untuk XAMPP, isi kredensial database lokal pada file privat.
Password kosong harus ditentukan secara eksplisit.

Kredensial yang pernah di-commit masih dapat ditemukan pada riwayat Git.
Ganti password database hosting yang pernah terpublikasi dan perbarui file
privat. Penghapusan dari versi terbaru tidak menghapus riwayat commit.

## Pembaruan server dan satker

Setelah konfigurasi privat siap dan perubahan di-push ke repository, jalankan
dari folder repository Kasuari pada server:

```bash
git pull --ff-only origin main
```

Paket `kasuari_satker` harus menggunakan nilai `$url_api` berikut pada
`sys/sys_config.php`:

```text
https://kasuari.pta-papuabarat.go.id/
```

Endpoint pengiriman adalah `/sync`, sedangkan riwayat pengiriman memakai
`/sync_data`. Petugas tetap menggunakan tombol Kirimkan Data. Pelaporan status
berjalan setiap lima menit selama halaman sinkronisasi terbuka.

Jika satu satker mengalami timeout sebelum pengiriman, lihat
`deploy/satker-network/README.md`. Paket tersebut berisi pembaruan client dan
alat pemeriksaan jaringan tanpa membaca data SIPP. Buat ZIP distribusi dengan
`php tools/build_satker_network_package.php` dari repository ini.

Jika environment `KASUARI_SYNC_ENDPOINT` pernah diatur pada server satker,
perbarui nilainya menjadi `https://kasuari.pta-papuabarat.go.id/sync` karena
override tersebut diprioritaskan untuk pengiriman dan pelaporan status.

## Verifikasi

1. Buka `https://kasuari.pta-papuabarat.go.id/` dan login.
2. Pastikan CSS, logo, manifest, dan preview laporan dimuat.
3. Uji health endpoint tanpa mengirim data perkara:

```bash
curl -sS -X POST -d 'action=health&protocol=2' https://kasuari.pta-papuabarat.go.id/sync
```

4. Pastikan akses langsung ke `/sys/sys_koneksi.local.php` dan
   `/.git/config` ditolak.
5. Jalankan `git check-ignore sys/sys_koneksi.local.php` di repository.
