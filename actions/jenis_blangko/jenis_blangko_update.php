<?php
$sql = "REPLACE INTO data_teks (var_nomor 
,perkara_id 
,pa_id 
,DATA
)
				VALUES
				('$var_nomor'
				,$perkara_id
				,$pn_id
				,'".mysqli_real_escape_string($koneksi,$DATA)."'
)"; 
///echo $sql;
$query=	mysqli_query($koneksi,$sql);
//echo "$sql";
if(mysqli_affected_rows($koneksi)==1){
	echo "Penyimpanan Berhasil";
}
mysqli_close($koneksi);
?>