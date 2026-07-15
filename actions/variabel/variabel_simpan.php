<?php
foreach($_POST as $key=>$value){$$key=$value;}
$sql = "INSERT INTO master_variabel (var_nomor 
,var_keterangan 
,var_model 
,var_jenis 
,var_tabel 
,var_field 
,var_sql_data 
,var_fungsi_nama 
,var_default_data 
)
				VALUES
				('$var_nomor'
				,'$var_keterangan'
				,'$var_model'
				,'$var_jenis'
				,'$var_tabel'
				,'$var_field'
				,'".mysqli_real_escape_string($koneksi,$var_sql_data)."'
				,'$var_fungsi_nama'
				,'$var_default_data'
)"; 
$query=	mysqli_query($koneksi,$sql);
//echo "$sql";
if(mysqli_affected_rows($koneksi)<>1){
	echo "Penyimpanan Gagal";
}else{
	echo "Penyimpanan Variabel dengan Nomor : $var_nomor, Nama : $var_keterangan Berhasil";
}
mysqli_close($koneksi);
?>