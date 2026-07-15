<?php
$template_id=$_POST["template_id"];
$ds= DIRECTORY_SEPARATOR;
$storeFolder = 'template';
if (!empty($_FILES)){
	$nama_file= $_FILES["file"]["name"];
	$tempFile = $_FILES['file']['tmp_name'];
	$targetPath =  $storeFolder . $ds;
	$sql="SELECT kode FROM template_dokumen WHERE id =$template_id";//echo "$sql";
	$query=	mysqli_query($koneksi,$sql);
	while($row=mysqli_fetch_assoc($query)){
		$filename=$row["kode"].".rtf";
			if (file_exists($targetPath.$filename)) {
				unlink($targetPath.$filename);
				echo "file dihapus";
			}else{
				echo "file tidak ada";
			}
			$targetFile =  $targetPath.$filename;  //5
			if(move_uploaded_file($tempFile,$targetFile)){
				echo "Berhasil";
			}
		 
	}
}
mysqli_close($koneksi);
?>