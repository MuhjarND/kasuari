<?php
$sql = "UPDATE $tabel SET $field ='$isi' WHERE $kunci=$id"; 
$query=	mysqli_query($koneksi,$sql);
echo "Edit Sudah dilakukan";
mysqli_query($koneksi,"UPDATE perkara_banding SET penerimaan_memori_banding =NULL WHERE penerimaan_memori_banding='0000-00-00'");
mysqli_query($koneksi,"UPDATE perkara_banding SET penerimaan_kontra_banding =NULL WHERE penerimaan_kontra_banding='0000-00-00'");

mysqli_query($koneksi,"UPDATE perkara_banding SET pelaksanaan_inzage =NULL WHERE pelaksanaan_inzage='0000-00-00'");
mysqli_query($koneksi,"UPDATE perkara_banding SET pelaksanaan_inzage_terbanding =NULL WHERE pelaksanaan_inzage_terbanding='0000-00-00'");
mysqli_close($koneksi);
?>