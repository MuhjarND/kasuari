<?php
foreach($_POST as $key=>$value){$$key=$value;}
$sql = "UPDATE master_variabel 
SET var_nomor ='$var_nomor'
,var_keterangan ='$var_keterangan'
,var_model ='$var_model'
,var_jenis ='$var_jenis'
,var_tabel ='$var_tabel'
,var_field ='$var_field'
,var_sql_data ='".mysqli_real_escape_string($koneksi,$var_sql_data)."'
,var_fungsi_nama ='$var_fungsi_nama'
,var_default_data ='$var_default_data'
WHERE var_id=$var_id"; 
$query=	mysqli_query($koneksi,$sql);
//echo "$sql";
if(mysqli_affected_rows($koneksi)<>1){
	echo "<p class='w3-text-red'>Penyimpanan Gagal</p>";
}else{
	echo "<p class='w3-text-green'>Penyimpanan Variabel dengan<br>Nomor : $var_nomor<br>Nama : $var_keterangan<br>Berhasil</p>";
}
mysqli_close($koneksi);
?>