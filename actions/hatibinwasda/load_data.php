<?php
include '../../sys/sys_koneksi.php';

// Ambil data kegiatan diurutkan dari yang terbaru
$sql = "SELECT hatibinwasda.*, convert_tanggal_indonesia(tgl_mulai) AS mulai, convert_tanggal_indonesia(tgl_sampai) AS sampai FROM hatibinwasda ORDER BY id DESC";
$result = $conn->query($sql);
$no = 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id_kegiatan = $row['id'];
        
        if($row['laporan'] != ""){
                // Link untuk melihat/mendownload file
                $list_file = "<a href='" . $row['laporan'] . "' target='_blank' class='btn btn-sm btn-primary'> <i class='bi bi-download' aria-hidden='true'></i>  Lihat </a>";
            }else{
                $list_file = "<span class='text-muted'>Tidak ada file</span>";
            }

        echo "<tr>
                <td>" . $no++ . "</td>
                <td>" . $row['satuan_kerja'] . "</td>
                <td>" . $row['mulai'] . " <br>sampai dengan<br> " . $row['sampai'] . "</td>
                <td>" . str_replace("\n", "<br>", $row['tim']) . "</td>
                <td>" . $list_file . "</td>
                <td>" . $row['catatan'] . "</td>
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