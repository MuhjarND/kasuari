# Status Akta Cerai - 1 November 2026

Pembaruan ini menambahkan metadata akta cerai SIPP ke sinkronisasi manual.
Tidak mengambil file PDF, tidak mengakses EAC, tidak mengubah data SIPP,
dan tidak mengaktifkan pengiriman otomatis.

## Pusat (pasang lebih dahulu)

1. Deploy perubahan aplikasi pusat, termasuk `sys/sys_akta_cerai.php` dan
   `database/akta_cerai.sql`.
2. Endpoint health membuat tiga tabel tambahan jika akun database mempunyai
   hak CREATE. Jika tidak, administrator database menjalankan
   `database/akta_cerai.sql` satu kali pada database Kasuari Pusat.
3. Akta hanya terlihat oleh administrator pada sel status Register Perkara
   Satker/Banding dan bagian Status Perkara di halaman detail. Tidak ada menu baru.

## Satker

1. Cadangkan file aplikasi, tunggu pengiriman aktif selesai.
2. Timpa file dari ZIP dengan susunan folder yang sama pada `kasuari_satker`.
   Paket menyertakan helper transport yang dibutuhkan versi sebelumnya.
3. Jangan menimpa `sys/sys_koneksi.php`, `sys/sys_config.php`, atau konfigurasi
   database lainnya. ZIP tidak memuat konfigurasi atau kredensial.
4. Buka dashboard lalu klik **Kirimkan Data**. Tunggu pesan sukses yang
   mencantumkan jumlah status akta cerai.
5. Periksa satu perkara perceraian di pusat sebagai administrator. Nomor,
   tanggal terbit, penyerahan pihak 1/2, dan waktu WIT harus sesuai SIPP.

Semua catatan tabel `perkara_akta_cerai` dikirim tanpa batas tahun/banding.
Sumber harus memiliki perkara_id, nomor_akta_cerai, tgl_akta_cerai,
dan tgl_penyerahan_akta_cerai. Kolom pihak2 opsional: jika tidak tersedia,
detail pusat menyatakannya secara eksplisit. Tidak diartikan belum diunduh.

Satker versi lama tetap dapat sinkronisasi perkara. Data aktanya tetap belum
tersinkron atau menampilkan snapshot akta terakhir, sampai paket ini dipasang.
Data akta yang belum lengkap diterima tanpa mengarang status penerbitan.
Snapshot baru hanya tampil setelah finalisasi sukses; snapshot lama tetap
terlihat jika pengiriman terputus. Pengiriman ulang tidak menggandakan data.

Data di KASUARI adalah catatan SIPP, bukan status unduhan EAC.
