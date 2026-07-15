<?php
$jenis_blangko_id=$_POST["jenis_blangko_id"];
$ds= DIRECTORY_SEPARATOR;
$storeFolder = 'template';
if (!empty($_FILES)){
	$nama_file= $_FILES["file"]["name"];
	$tempFile = $_FILES['file']['tmp_name'];
	$targetPath =  $storeFolder . $ds;  //4

	$filename = $nama_file;
	$kode=str_replace(".rtf", "",$filename);
	$sql="SELECT id FROM template_dokumen WHERE kode='$kode'";//echo "$sql";
	$query=	mysqli_query($koneksi,$sql);
	if(mysqli_num_rows($query)<>1){
		if (file_exists($filename)) {
			unlink($targetPath.$filename);
		}
		$targetFile =  $targetPath.$nama_file;  //5
		if(move_uploaded_file($tempFile,$targetFile)){
			$sql_insert = "INSERT INTO template_dokumen (kode 
						,nama 
						,jenis_blangko_id 
						)
						VALUES
						('$kode'
						,'$kode'
						,$jenis_blangko_id
						)"; //echo "$sql_insert";
						$query=	mysqli_query($koneksi,$sql_insert);
						$data=array('id' => mysqli_insert_id($koneksi));
						echo json_encode($data);
		}
	}else{
		echo "Dokumen yang diupload sudah Ada";
	}
}
mysqli_close($koneksi);
?>