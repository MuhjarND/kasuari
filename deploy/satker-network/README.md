# Pembaruan koneksi Kasuari Satker - 28 Oktober 2026

Gejala: `Pusat tidak terhubung` dan `Connection timed out after 15001 milliseconds`
pada pemeriksaan awal. Ini terjadi sebelum pengiriman data SIPP, bukan bukti
bahwa isi perkara PA Sorong rusak atau jumlah perkara terlalu banyak.

Paket ini adalah salinan file client untuk didistribusikan ke server satker,
bukan modul yang dipasang pada Kasuari Pusat. Jangan mengunggah folder ini
sebagai pengganti aplikasi pusat.

## Isi paket

- `sys/sys_transport.php`: transport bersama, retry IPv4 ketika TCP belum
  terhubung, validasi TLS tetap aktif, dan pesan DNS/koneksi/HTTP/TLS.
- `sys/sys_fungsi.php`: transport yang sama untuk pelaporan status dan helper lama.
- `modul/sync_lokal.php`: transport bersama untuk health, batch, dan finalisasi.
- `modul/data.php`: transport dan endpoint override yang sama untuk riwayat.
- `tools/cek_koneksi.php`: pemeriksaan CLI tanpa koneksi database SIPP.

Tidak ada kredensial, penggantian database, migrasi SQL, atau perubahan menjadi
sinkronisasi otomatis. Petugas tetap menekan **Kirimkan Data**.
Pusat tidak diubah oleh paket ini. Request ID dan protokol batch tetap sama.

## Pemasangan PA Sorong

1. Tunggu pengiriman yang sedang berjalan selesai. Cadangkan folder
   `kasuari_satker` di luar webroot.
2. Salin kelima file di atas ke folder aplikasi `kasuari_satker` pada
   komputer/server SIPP PA Sorong, pertahankan susunan folder dan timpa file lama.
   Pasang bersama-sama; `sys_fungsi.php` memerlukan file baru `sys_transport.php`.
   Folder aplikasi bukan folder pusat di hosting.
3. **Jangan menimpa `sys/sys_koneksi.php` atau konfigurasi database satker.**
   Paket sengaja tidak menyertakan file tersebut maupun `sys/sys_config.php`.
4. Periksa `$url_api` pada `sys/sys_config.php`, harus menunjuk ke
   `https://kasuari.pta-papuabarat.go.id/`. Jika environment
   `KASUARI_SYNC_ENDPOINT` diatur, nilai efektifnya harus
   `https://kasuari.pta-papuabarat.go.id/sync`, bukan IP/domain lama.
5. Jalankan dari folder aplikasi di terminal server Sorong menggunakan PHP yang
   sama dengan Apache. Sesuaikan letak executable:

   ```powershell
   C:\xampp\php\php.exe tools\cek_koneksi.php
   ```

   Linux:

   ```bash
   php tools/cek_koneksi.php
   ```

6. Jika hasilnya `koneksi dan protokol pusat siap`, muat ulang dashboard lalu
   klik **Kirimkan Data**. Pastikan muncul sukses dan waktu terakhir sinkronisasi
   berubah di monitoring pusat.

## Jika masih gagal

Uji dari **server SIPP Sorong**, bukan hanya laptop petugas. Catat hasil
`cek_koneksi.php`; environment PHP CLI dan Apache bisa berbeda.

```powershell
nslookup kasuari.pta-papuabarat.go.id
Test-NetConnection kasuari.pta-papuabarat.go.id -Port 443
curl.exe -4 -sS --connect-timeout 15 --max-time 45 -X POST -d "action=health&protocol=2" https://kasuari.pta-papuabarat.go.id/sync
```

Linux dapat memakai `nslookup` dan perintah `curl -4` yang sama tanpa `.exe`.
Tes tersebut hanya memeriksa health, tidak mengirim data perkara.

- cURL 6: periksa DNS server Sorong, domain, dan akses DNS keluar.
- cURL 7/28 sebelum TCP terhubung: periksa internet server, proxy, firewall
  keluar TCP 443, serta pemblokiran/rate limit IP publik Sorong di hosting/WAF.
  Coba jalur internet lain yang diizinkan untuk membandingkan hasil. Jangan
  menonaktifkan seluruh firewall.
- HTTP 403/429: periksa log WAF/hosting dengan waktu tes dan IP publik Sorong.
- HTTP 301/302: periksa alamat tujuan, jangan memakai halaman login sebagai API.
- Kesalahan sertifikat/TLS: periksa tanggal server dan CA bundle PHP/cURL;
  jangan memakai `-k` atau mematikan verifikasi sertifikat.

Jika perlu uji IPv4 sejak percobaan pertama pada Windows PowerShell:

```powershell
$env:KASUARI_SYNC_IP_FAMILY = 'ipv4'
C:\xampp\php\php.exe tools\cek_koneksi.php
Remove-Item Env:\KASUARI_SYNC_IP_FAMILY
```

Ini hanya memengaruhi proses terminal tersebut, bukan Apache. Client web
otomatis mencoba IPv4 pada percobaan berikutnya setelah kegagalan koneksi awal.
Tidak ada IP hosting yang ditanam di kode.

Paket tidak dapat menjamin perbaikan bila jaringan/firewall/hosting tetap
memblokir koneksi. Keberhasilan dari komputer pengembang tidak membuktikan
koneksi dari PA Sorong berhasil.
