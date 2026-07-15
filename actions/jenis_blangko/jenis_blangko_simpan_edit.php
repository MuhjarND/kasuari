<?php
foreach($_POST as $key=>$value){$$key=$value;}
$sql = "UPDATE jenis_blangko 
SET jenis_blangko_nama ='$jenis_blangko_nama'
,jenis_blangko_parent_id ='$jenis_blangko_parent_id'
,jenis_blangko_status ='$jenis_blangko_status'
,urutan ='$urutan'
WHERE jenis_blangko_id=$jenis_blangko_id"; 
$query=	mysqli_query($koneksi,$sql);
//echo "$sql";
if(mysqli_affected_rows($koneksi)<>1){
	echo "<p class='w3-text-red'>Penyimpanan Gagal</p>";
}else{
	echo "<p class='w3-text-green'>Penyimpanan Berhasil</p>";
}
//echo "$sql";
mysqli_close($koneksi);
?>