<?php
require_once __DIR__ . '/../../sys/sys_koneksi.php';
require_once __DIR__ . '/../../sys/sys_fungsi.php';

// Ambil data kegiatan diurutkan dari yang terbaru
$sql = "SELECT hatibinwasda.* FROM hatibinwasda ORDER BY id DESC";
$result = $conn->query($sql);
$no = 1;

if ($result === false) {
    error_log('KASUARI hatibinwasda load failed: '.$conn->error);
    echo "<tr><td colspan='8' class='text-center text-danger'>Data kegiatan belum dapat dimuat.</td></tr>";
} elseif ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id_kegiatan = (int) $row['id'];
        $mulai = htmlspecialchars(kasuari_tanggal_indonesia($row['tgl_mulai'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sampai = htmlspecialchars(kasuari_tanggal_indonesia($row['tgl_sampai'] ?? ''), ENT_QUOTES, 'UTF-8');
        $satuanKerja = htmlspecialchars((string) ($row['satuan_kerja'] ?? ''), ENT_QUOTES, 'UTF-8');
        $tim = nl2br(htmlspecialchars((string) ($row['tim'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $catatan = nl2br(htmlspecialchars((string) ($row['catatan'] ?? ''), ENT_QUOTES, 'UTF-8'));
        
        if(!empty($row['laporan'])){
                // Link untuk melihat/mendownload file
                $laporan = htmlspecialchars((string) $row['laporan'], ENT_QUOTES, 'UTF-8');
                $list_file = "<a href='" . $laporan . "' target='_blank' rel='noopener' class='btn btn-sm btn-primary'> <i class='bi bi-download' aria-hidden='true'></i>  Lihat </a>";
            }else{
                $list_file = "<span class='text-muted'>Tidak ada file</span>";
            }

        echo "<tr>
                <td>" . $no++ . "</td>
                <td>" . $satuanKerja . "</td>
                <td>" . $mulai . " <br>sampai dengan<br> " . $sampai . "</td>
                <td>" . $tim . "</td>
                <td>" . $list_file . "</td>
                <td>" . $catatan . "</td>
                <td>
                    <button title='Hapus' class='btn btn-sm btn-danger mb-1' onclick='hapusData(" . $id_kegiatan . ")'>X</button>
                    <button title='Edit' class='btn btn-sm btn-warning mb-1' onclick='editData(" . $id_kegiatan . ")'>E</button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>Belum ada data kegiatan.</td></tr>";
}
?>
