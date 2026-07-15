<?php
$sql = "SELECT * FROM pengadilan_agama WHERE aktif='Y'";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
  $no++;
  $url_api=$data["ip_satker"].'/api_monitoring/get_data_api';
  $pn_id=$data["id"];
  echo "<p>".$data["nama"]."<br>";
  $sql_banding="SELECT  perkara_id,jenis_perkara_id,jenis_perkara_kode,jenis_perkara_nama,nomor_perkara FROM perkara WHERE perkara_id IN (select perkara_id FROM perkara_banding)";
  $kirim["req"]=base64_encode($sql_banding);
  $respon=json_decode(curl($url_api,$kirim));
  $no=0;
  $sql_insert="";
  if(count($respon)){
    foreach($respon AS $data_banding){
      $no++;
      if($no==1){
        $sql_insert.="REPLACE INTO perkara (pn_id, perkara_id, jenis_perkara_id, jenis_perkara_kode, jenis_perkara_nama, nomor_perkara) values ('".$pn_id."', '".$data_banding->perkara_id."', '".$data_banding->jenis_perkara_id."', '".$data_banding->jenis_perkara_kode."', '".$data_banding->jenis_perkara_nama."', '".$data_banding->nomor_perkara."') "; 
      }else{
        $sql_insert.=", ('".$pn_id."', '".$data_banding->perkara_id."', '".$data_banding->jenis_perkara_id."', '".$data_banding->jenis_perkara_kode."', '".$data_banding->jenis_perkara_nama."', '".$data_banding->nomor_perkara."') "; 
      }
    }
     mysqli_query($koneksi,$sql_insert);
     echo "$sql_insert<hr>";
  }
   $sql_banding="SELECT  perkara_id,amar_putusan  FROM perkara_putusan WHERE perkara_id IN (select perkara_id FROM perkara_banding)";
  $kirim["req"]=base64_encode($sql_banding);
  $respon=json_decode(curl($url_api,$kirim));
  if(count($respon)){
    $no=0;
    $sql_insert="";
    foreach($respon AS $data_banding){
      $no++;
      if($no==1){
        $sql_insert.="REPLACE INTO perkara_putusan (pn_id, perkara_id, amar_putusan ) values ('".$pn_id."', '".$data_banding->perkara_id."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan)."') ";
      }else{
        $sql_insert.=", ('".$pn_id."', '".$data_banding->perkara_id."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan)."') ";
      }
         
       
    }
    mysqli_query($koneksi,$sql_insert);
    
     echo "$sql_insert<hr>";
  }
  
}
$sql = "UPDATE singkron SET waktu='". date("Y-m-d H:i:s")."' WHERE id=3";
mysqli_query($koneksi,$sql);
echo "Proses Selesai";
echo $sql;
mysqli_close($koneksi);
exit;
?>