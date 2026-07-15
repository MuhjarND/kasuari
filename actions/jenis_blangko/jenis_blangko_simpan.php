<?php
foreach($_POST as $key=>$value){$$key=$value;}
$sql = "INSERT INTO jenis_blangko (jenis_blangko_nama	 
,jenis_blangko_parent_id 
,jenis_blangko_status 
,urutan
)
				VALUES
				('$jenis_blangko_nama'
				,'$jenis_blangko_parent_id'
				,'$jenis_blangko_status'
				,'$urutan'
)"; 
$query=	mysqli_query($koneksi,$sql);
//echo "$sql";
if(mysqli_affected_rows($koneksi)<>1){
	echo "Penyimpanan Gagal";
}else{
	echo "Penyimpanan Jenis Blangko dengan Nama : $jenis_blangko_nama Berhasil";
}
mysqli_close($koneksi);
?>