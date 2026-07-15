<?php
$sql = "SELECT id, kode, nama, jenis_blangko_id FROM template_dokumen WHERE id=$id";
$query=mysqli_query($koneksi,$sql);
$emparray = array();
while($row=mysqli_fetch_assoc($query)){
   $emparray[] = $row;
}
echo json_encode($emparray);
mysqli_close($koneksi);
?>