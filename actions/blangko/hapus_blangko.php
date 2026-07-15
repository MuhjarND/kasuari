<?php
$sql=" SELECT * FROM template_dokumen where id=$id";
$query=mysqli_query($koneksi,$sql);
while($row=mysqli_fetch_assoc($query)){
	$ds= DIRECTORY_SEPARATOR;
	$storeFolder = 'template';
	$targetPath =  $storeFolder . $ds; 
	$filename = $row["kode"].".rtf";
	unlink($targetPath.$filename);
	$sql_hapus="DELETE FROM template_dokumen WHERE id=$id";
	mysqli_query($koneksi,$sql_hapus);
}
mysqli_close($koneksi);
?>