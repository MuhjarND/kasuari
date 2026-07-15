<?php

$sql = "SELECT * FROM pengadilan_agama WHERE aktif='Y'";
$query=mysqli_query($koneksi,$sql); 
while($data=mysqli_fetch_assoc($query)){ 
  $url_api=$data["ip_satker"].'/api_monitoring/get_data_api';
  $pn_id=$data["id"];
  $sql_banding="select * from pihak WHERE id IN(SELECT pihak_id FROM perkara_banding_detil) OR id IN (SELECT pemohon_id FROM perkara_banding_detil)";
  $kirim["req"]=base64_encode($sql_banding);
  $respon=json_decode(curl($url_api,$kirim));

  $no=0;
  $sql_insert="";
  if(count($respon)){
    foreach($respon AS $data_banding){
      $no++;
      if($no==1){
        $sql_insert.="REPLACE INTO pihak (pn_id, id, jenis_pihak_id, jenis_indentitas, nomor_indentitas, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, golongan_darah, alamat, rtrw, kelurahan, kecamatan, kabupaten_id, kabupaten, propinsi_id, propinsi, telepon, fax, email, agama_id, agama_nama, status_kawin, pekerjaan, pendidikan_id, pendidikan, warga_negara_id, warga_negara, nama_ayah, nama_ibu, keterangan, foto, difabel, diedit_oleh, diedit_tanggal, diinput_oleh, diinput_tanggal, diperbaharui_oleh, diperbaharui_tanggal ) values ('".$pn_id."', '".$data_banding->id."', '".$data_banding->jenis_pihak_id."', '".$data_banding->jenis_indentitas."', '".$data_banding->nomor_indentitas."', '".mysqli_real_escape_string($koneksi,$data_banding->nama)."', '".$data_banding->tempat_lahir."', '".$data_banding->tanggal_lahir."', '".$data_banding->jenis_kelamin."', '".$data_banding->golongan_darah."', '".mysqli_real_escape_string($koneksi,$data_banding->alamat)."', '".$data_banding->rtrw."', '".$data_banding->kelurahan."', '".$data_banding->kecamatan."', '".$data_banding->kabupaten_id."', '".$data_banding->kabupaten."', '".$data_banding->propinsi_id."', '".$data_banding->propinsi."', '".$data_banding->telepon."', '".$data_banding->fax."', '".$data_banding->email."', '".$data_banding->agama_id."', '".$data_banding->agama_nama."', '".$data_banding->status_kawin."', '".$data_banding->pekerjaan."', '".$data_banding->pendidikan_id."', '".$data_banding->pendidikan."', '".$data_banding->warga_negara_id."', '".$data_banding->warga_negara."', '".$data_banding->nama_ayah."', '".$data_banding->nama_ibu."', '".$data_banding->keterangan."', '".$data_banding->foto."', '".$data_banding->difabel."', '".$data_banding->diedit_oleh."', '".$data_banding->diedit_tanggal."', '".$data_banding->diinput_oleh."', '".$data_banding->diinput_tanggal."', '".$data_banding->diperbaharui_oleh."', '".$data_banding->diperbaharui_tanggal."') ";
      }else{
        $sql_insert.=", ('".$pn_id."', '".$data_banding->id."', '".$data_banding->jenis_pihak_id."', '".$data_banding->jenis_indentitas."', '".$data_banding->nomor_indentitas."', '".mysqli_real_escape_string($koneksi,$data_banding->nama)."', '".$data_banding->tempat_lahir."', '".$data_banding->tanggal_lahir."', '".$data_banding->jenis_kelamin."', '".$data_banding->golongan_darah."', '".mysqli_real_escape_string($koneksi,$data_banding->alamat)."', '".$data_banding->rtrw."', '".$data_banding->kelurahan."', '".$data_banding->kecamatan."', '".$data_banding->kabupaten_id."', '".$data_banding->kabupaten."', '".$data_banding->propinsi_id."', '".$data_banding->propinsi."', '".$data_banding->telepon."', '".$data_banding->fax."', '".$data_banding->email."', '".$data_banding->agama_id."', '".$data_banding->agama_nama."', '".$data_banding->status_kawin."', '".$data_banding->pekerjaan."', '".$data_banding->pendidikan_id."', '".$data_banding->pendidikan."', '".$data_banding->warga_negara_id."', '".$data_banding->warga_negara."', '".$data_banding->nama_ayah."', '".$data_banding->nama_ibu."', '".$data_banding->keterangan."', '".$data_banding->foto."', '".$data_banding->difabel."', '".$data_banding->diedit_oleh."', '".$data_banding->diedit_tanggal."', '".$data_banding->diinput_oleh."', '".$data_banding->diinput_tanggal."', '".$data_banding->diperbaharui_oleh."', '".$data_banding->diperbaharui_tanggal."') ";
      }
    }
    mysqli_query($koneksi,$sql_insert);
  }
  //echo "$no ".$data["nama"]." : ".count($respon)." Perkara<br><hr>";
}
//echo "$sql_insert<hr>";

$sql = "UPDATE singkron SET waktu='". date("Y-m-d H:i:s")."' WHERE id=2";
mysqli_query($koneksi,$sql);
//echo "$sql<hr>";
echo "Proses Selesai";
mysqli_close($koneksi);
exit;
?>