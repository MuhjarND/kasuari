<?php
if(!function_exists('curl')){ 
function curl($url, $data){
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch); 
    curl_close($ch);      
    return $output;
}
}
include_once("sys/sys_koneksi.php");
function proses_date_int($koneksi){
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_putusan_pn =NULL WHERE pemberitahuan_putusan_pn='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_permohonan_banding =NULL WHERE pemberitahuan_permohonan_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET penerimaan_memori_banding =NULL WHERE penerimaan_memori_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET penyerahan_memori_banding =NULL WHERE penyerahan_memori_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET penerimaan_kontra_banding =NULL WHERE penerimaan_kontra_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET penyerahan_kontra_banding =NULL WHERE penyerahan_kontra_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_inzage =NULL WHERE pemberitahuan_inzage='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_inzage_pembanding =NULL WHERE pemberitahuan_inzage_pembanding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_inzage_terbanding =NULL WHERE pemberitahuan_inzage_terbanding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pelaksanaan_inzage =NULL WHERE pelaksanaan_inzage='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pelaksanaan_inzage_pembanding =NULL WHERE pelaksanaan_inzage_pembanding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pelaksanaan_inzage_terbanding =NULL WHERE pelaksanaan_inzage_terbanding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pengiriman_berkas_banding =NULL WHERE pengiriman_berkas_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET penerimaan_kembali_berkas_banding =NULL WHERE penerimaan_kembali_berkas_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET nomor_urut_register =NULL WHERE nomor_urut_register='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tanggal_pendaftaran_banding =NULL WHERE tanggal_pendaftaran_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET panitera_pembuat_akta_banding =NULL WHERE panitera_pembuat_akta_banding='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET hakim1_banding_id =NULL WHERE hakim1_banding_id='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET hakim2_banding_id =NULL WHERE hakim2_banding_id='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET hakim3_banding_id =NULL WHERE hakim3_banding_id='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET hakim4_banding_id =NULL WHERE hakim4_banding_id='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET hakim5_banding_id =NULL WHERE hakim5_banding_id='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tanggal_penetapan_sidang_pertama =NULL WHERE tanggal_penetapan_sidang_pertama='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tanggal_sidang_pertama =NULL WHERE tanggal_sidang_pertama='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET putusan_banding =NULL WHERE putusan_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tgl_kirim_salinan_putusan =NULL WHERE tgl_kirim_salinan_putusan='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET minutasi_banding =NULL WHERE minutasi_banding='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tgl_minutasi =NULL WHERE tgl_minutasi='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tgl_pengiriman_berkas_putusan   =NULL WHERE tgl_pengiriman_berkas_putusan  ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_putusan_banding     =NULL WHERE pemberitahuan_putusan_banding    ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_putusan_banding_pembanding     =NULL WHERE pemberitahuan_putusan_banding_pembanding    ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET pemberitahuan_putusan_banding_terbanding      =NULL WHERE pemberitahuan_putusan_banding_terbanding     ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tgl_pemberitahuan_putusan      =NULL WHERE tgl_pemberitahuan_putusan     ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tanggal_cabut      =NULL WHERE tanggal_cabut     ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET tanggal_cabut      =NULL WHERE tanggal_cabut     ='0000-00-00'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET status_banding_id      =NULL WHERE status_banding_id     ='0'");
  mysqli_query($koneksi,"UPDATE perkara_banding SET status_putusan_banding_id      =NULL WHERE status_putusan_banding_id     ='0'");
}
$sql = "SELECT * FROM pengadilan_agama WHERE aktif='Y'";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
  $no++;
  $url_api=$data["ip_satker"].'/api_monitoring/get_data_api';
  $pn_id=$data["id"];
  $tahune=date("Y")-1;
  $sql_banding="SELECT * FROM perkara_banding WHERE year(permohonan_banding)>=$tahune";
  $kirim["req"]=base64_encode($sql_banding);
  $respon=json_decode(curl($url_api,$kirim));
  $no=0;
  $sql_insert="";
  if(count($respon)){
    foreach($respon AS $data_banding){
      $no++;
      if($no==1){ 
        $sql_insert.="REPLACE INTO perkara_banding  (pn_id, perkara_id, jenis_banding, alur_perkara_id, nomor_perkara_pn, putusan_pn, pihak_pembanding, permohonan_banding, pemohon_banding, para_pihak, pemberitahuan_putusan_pn, pemberitahuan_permohonan_banding, penerimaan_memori_banding, penyerahan_memori_banding, penerimaan_kontra_banding, penyerahan_kontra_banding, pemberitahuan_inzage, pemberitahuan_inzage_pembanding, pemberitahuan_inzage_terbanding, pelaksanaan_inzage, pelaksanaan_inzage_pembanding, pelaksanaan_inzage_terbanding, pengiriman_berkas_banding, nomor_surat_pengiriman_berkas_banding, penerimaan_kembali_berkas_banding, nomor_urut_register, tanggal_pendaftaran_banding, nomor_perkara_banding, panitera_pembuat_akta_banding, hakim1_banding_id, hakim1_banding, hakim2_banding_id, hakim2_banding, hakim3_banding_id, hakim3_banding, hakim4_banding_id, hakim4_banding, hakim5_banding_id, hakim5_banding, majelis_hakim_banding, panitera_pengganti_banding_id, panitera_pengganti_banding, tanggal_penetapan_sidang_pertama, tanggal_sidang_pertama, putusan_banding, sumber_hukum_id, status_putusan_banding_id, status_putusan_banding_text, nomor_putusan_banding, amar_putusan_banding, amar_putusan_banding_dok, tgl_kirim_salinan_putusan, minutasi_banding, tgl_minutasi, tgl_pengiriman_berkas_putusan, pemberitahuan_putusan_banding, pemberitahuan_putusan_banding_pembanding, pemberitahuan_putusan_banding_terbanding, tgl_pemberitahuan_putusan, catatan_banding, prodeo_banding, status_banding_id, status_banding_text, tanggal_cabut, diedit_oleh, diedit_tanggal, diinput_oleh, diinput_tanggal, diperbaharui_oleh, diperbaharui_tanggal)
          values
          ('".$pn_id."', '".$data_banding->perkara_id."', '".$data_banding->jenis_banding."', '".$data_banding->alur_perkara_id."', '".$data_banding->nomor_perkara_pn."', '".$data_banding->putusan_pn."', '".$data_banding->pihak_pembanding."', '".$data_banding->permohonan_banding."', '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_banding)."', '".$data_banding->para_pihak."', '".$data_banding->pemberitahuan_putusan_pn."', '".$data_banding->pemberitahuan_permohonan_banding."', '".$data_banding->penerimaan_memori_banding."', '".$data_banding->penyerahan_memori_banding."', '".$data_banding->penerimaan_kontra_banding."', '".$data_banding->penyerahan_kontra_banding."', '".$data_banding->pemberitahuan_inzage."', '".$data_banding->pemberitahuan_inzage_pembanding."', '".$data_banding->pemberitahuan_inzage_terbanding."', '".$data_banding->pelaksanaan_inzage."', '".$data_banding->pelaksanaan_inzage_pembanding."', '".$data_banding->pelaksanaan_inzage_terbanding."', '".$data_banding->pengiriman_berkas_banding."', '".$data_banding->nomor_surat_pengiriman_berkas_banding."', '".$data_banding->penerimaan_kembali_berkas_banding."', '".$data_banding->nomor_urut_register."', '".$data_banding->tanggal_pendaftaran_banding."', '".$data_banding->nomor_perkara_banding."', '".$data_banding->panitera_pembuat_akta_banding."', '".$data_banding->hakim1_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim1_banding)."', '".$data_banding->hakim2_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim2_banding)."', '".$data_banding->hakim3_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim3_banding)."', '".$data_banding->hakim4_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim4_banding)."', '".$data_banding->hakim5_banding_id."', '".$data_banding->hakim5_banding."', '".mysqli_real_escape_string($koneksi,$data_banding->majelis_hakim_banding)."', '".$data_banding->panitera_pengganti_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->panitera_pengganti_banding)."', '".$data_banding->tanggal_penetapan_sidang_pertama."', '".$data_banding->tanggal_sidang_pertama."', '".$data_banding->putusan_banding."', '".$data_banding->sumber_hukum_id."', '".$data_banding->status_putusan_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->status_putusan_banding_text)."', '".mysqli_real_escape_string($koneksi,$data_banding->nomor_putusan_banding)."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan_banding)."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan_banding_dok)."', '".$data_banding->tgl_kirim_salinan_putusan."', '".$data_banding->minutasi_banding."', '".$data_banding->tgl_minutasi."', '".$data_banding->tgl_pengiriman_berkas_putusan."', '".$data_banding->pemberitahuan_putusan_banding."', '".$data_banding->pemberitahuan_putusan_banding_pembanding."', '".$data_banding->pemberitahuan_putusan_banding_terbanding."', '".$data_banding->tgl_pemberitahuan_putusan."', '".mysqli_real_escape_string($koneksi,$data_banding->catatan_banding)."', '".$data_banding->prodeo_banding."', '".$data_banding->status_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->status_banding_text)."', '".$data_banding->tanggal_cabut."', '".$data_banding->diedit_oleh."', '".$data_banding->diedit_tanggal."', '".$data_banding->diinput_oleh."', '".$data_banding->diinput_tanggal."', '".$data_banding->diperbaharui_oleh."', '".$data_banding->diperbaharui_tanggal."')
         ";
      }else{
        $sql_insert.=",
         ('".$pn_id."', '".$data_banding->perkara_id."', '".$data_banding->jenis_banding."', '".$data_banding->alur_perkara_id."', '".$data_banding->nomor_perkara_pn."', '".$data_banding->putusan_pn."', '".$data_banding->pihak_pembanding."', '".$data_banding->permohonan_banding."', '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_banding)."', '".$data_banding->para_pihak."', '".$data_banding->pemberitahuan_putusan_pn."', '".$data_banding->pemberitahuan_permohonan_banding."', '".$data_banding->penerimaan_memori_banding."', '".$data_banding->penyerahan_memori_banding."', '".$data_banding->penerimaan_kontra_banding."', '".$data_banding->penyerahan_kontra_banding."', '".$data_banding->pemberitahuan_inzage."', '".$data_banding->pemberitahuan_inzage_pembanding."', '".$data_banding->pemberitahuan_inzage_terbanding."', '".$data_banding->pelaksanaan_inzage."', '".$data_banding->pelaksanaan_inzage_pembanding."', '".$data_banding->pelaksanaan_inzage_terbanding."', '".$data_banding->pengiriman_berkas_banding."', '".$data_banding->nomor_surat_pengiriman_berkas_banding."', '".$data_banding->penerimaan_kembali_berkas_banding."', '".$data_banding->nomor_urut_register."', '".$data_banding->tanggal_pendaftaran_banding."', '".$data_banding->nomor_perkara_banding."', '".$data_banding->panitera_pembuat_akta_banding."', '".$data_banding->hakim1_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim1_banding)."', '".$data_banding->hakim2_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim2_banding)."', '".$data_banding->hakim3_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim3_banding)."', '".$data_banding->hakim4_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->hakim4_banding)."', '".$data_banding->hakim5_banding_id."', '".$data_banding->hakim5_banding."', '".mysqli_real_escape_string($koneksi,$data_banding->majelis_hakim_banding)."', '".$data_banding->panitera_pengganti_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->panitera_pengganti_banding)."', '".$data_banding->tanggal_penetapan_sidang_pertama."', '".$data_banding->tanggal_sidang_pertama."', '".$data_banding->putusan_banding."', '".$data_banding->sumber_hukum_id."', '".$data_banding->status_putusan_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->status_putusan_banding_text)."', '".mysqli_real_escape_string($koneksi,$data_banding->nomor_putusan_banding)."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan_banding)."', '".mysqli_real_escape_string($koneksi,$data_banding->amar_putusan_banding_dok)."', '".$data_banding->tgl_kirim_salinan_putusan."', '".$data_banding->minutasi_banding."', '".$data_banding->tgl_minutasi."', '".$data_banding->tgl_pengiriman_berkas_putusan."', '".$data_banding->pemberitahuan_putusan_banding."', '".$data_banding->pemberitahuan_putusan_banding_pembanding."', '".$data_banding->pemberitahuan_putusan_banding_terbanding."', '".$data_banding->tgl_pemberitahuan_putusan."', '".mysqli_real_escape_string($koneksi,$data_banding->catatan_banding)."', '".$data_banding->prodeo_banding."', '".$data_banding->status_banding_id."', '".mysqli_real_escape_string($koneksi,$data_banding->status_banding_text)."', '".$data_banding->tanggal_cabut."', '".$data_banding->diedit_oleh."', '".$data_banding->diedit_tanggal."', '".$data_banding->diinput_oleh."', '".$data_banding->diinput_tanggal."', '".$data_banding->diperbaharui_oleh."', '".$data_banding->diperbaharui_tanggal."')
         ";
      }
      
    
    }
    //echo $sql_insert."<hr><hr>";
    mysqli_query($koneksi,$sql_insert);
    proses_date_int($koneksi);   
  }
  $sql_banding="SELECT * FROM perkara_banding_detil WHERE perkara_id IN (SELECT perkara_id FROM perkara_banding WHERE year(permohonan_banding)>=$tahune)";
  $kirim["req"]=base64_encode($sql_banding);
  $respon=json_decode(curl($url_api,$kirim));
  $no=0;
  $sql_insert="";
  if(count($respon)){
    foreach($respon AS $data_banding){
      $no++;
      if($no==1){
        $sql_insert.="REPLACE INTO perkara_banding_detil 
         (pn_id,
              id,
              perkara_id,
              alur_perkara_id,
              status_pihak_id,
              status_pihak_text,
              urutan_banding,
              permohonan_banding,
              pihak_asal,
              pihak_asal_text,
              pihak_id,
              pihak_nama,
              pihak_diwakili,
              pemohon_id,
              pemohon_nama,
              pemohon_pekerjaan,
              pemohon_alamat,
              pemohon_tanggal_surat,
              pemohon_nomor_surat,
              pemohon_banding,
              pemberitahuan_putusan_pn,
              pemberitahuan_permohonan_banding,
              penerimaan_memori_banding,
              penyerahan_memori_banding,
              penerimaan_kontra_banding,
              penyerahan_kontra_banding,
              pemberitahuan_inzage,
              pelaksanaan_inzage,
              pemberitahuan_putusan_banding,
              tanggal_kirim_salinan_putusan,
              tanggal_cabut,
              keterangan,
              diedit_oleh,
              diedit_tanggal,
              diinput_oleh,
              diinput_tanggal,
              diperbaharui_oleh,
              diperbaharui_tanggal
              )
                       values
                       ('".$pn_id."','".$data_banding->id."',
               '".$data_banding->perkara_id."',
              '".$data_banding->alur_perkara_id."',
              '".$data_banding->status_pihak_id."',
              '".$data_banding->status_pihak_text."',
              '".$data_banding->urutan_banding."',
              '".$data_banding->permohonan_banding."',
              '".$data_banding->pihak_asal."',
              '".$data_banding->pihak_asal_text."',
              '".$data_banding->pihak_id."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pihak_nama)."',
              '".$data_banding->pihak_diwakili."',
              '".$data_banding->pemohon_id."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_nama)."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_pekerjaan)."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_alamat)."',
              '".$data_banding->pemohon_tanggal_surat."',
              '".$data_banding->pemohon_nomor_surat."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_banding)."',
              '".$data_banding->pemberitahuan_putusan_pn."',
              '".$data_banding->pemberitahuan_permohonan_banding."',
              '".$data_banding->penerimaan_memori_banding."',
              '".$data_banding->penyerahan_memori_banding."',
              '".$data_banding->penerimaan_kontra_banding."',
              '".$data_banding->penyerahan_kontra_banding."',
              '".$data_banding->pemberitahuan_inzage."',
              '".$data_banding->pelaksanaan_inzage."',
              '".$data_banding->pemberitahuan_putusan_banding."',
              '".$data_banding->tanggal_kirim_salinan_putusan."',
              '".$data_banding->tanggal_cabut."',
              '".$data_banding->keterangan."',
              '".$data_banding->diedit_oleh."',
              '".$data_banding->diedit_tanggal."',
              '".$data_banding->diinput_oleh."',
              '".$data_banding->diinput_tanggal."',
              '".$data_banding->diperbaharui_oleh."',
              '".$data_banding->diperbaharui_tanggal."')
                       ";
      }else{
        $sql_insert.=",
                       ('".$pn_id."','".$data_banding->id."',
               '".$data_banding->perkara_id."',
              '".$data_banding->alur_perkara_id."',
              '".$data_banding->status_pihak_id."',
              '".$data_banding->status_pihak_text."',
              '".$data_banding->urutan_banding."',
              '".$data_banding->permohonan_banding."',
              '".$data_banding->pihak_asal."',
              '".$data_banding->pihak_asal_text."',
              '".$data_banding->pihak_id."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pihak_nama)."',
              '".$data_banding->pihak_diwakili."',
              '".$data_banding->pemohon_id."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_nama)."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_pekerjaan)."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_alamat)."',
              '".$data_banding->pemohon_tanggal_surat."',
              '".$data_banding->pemohon_nomor_surat."',
              '".mysqli_real_escape_string($koneksi,$data_banding->pemohon_banding)."',
              '".$data_banding->pemberitahuan_putusan_pn."',
              '".$data_banding->pemberitahuan_permohonan_banding."',
              '".$data_banding->penerimaan_memori_banding."',
              '".$data_banding->penyerahan_memori_banding."',
              '".$data_banding->penerimaan_kontra_banding."',
              '".$data_banding->penyerahan_kontra_banding."',
              '".$data_banding->pemberitahuan_inzage."',
              '".$data_banding->pelaksanaan_inzage."',
              '".$data_banding->pemberitahuan_putusan_banding."',
              '".$data_banding->tanggal_kirim_salinan_putusan."',
              '".$data_banding->tanggal_cabut."',
              '".$data_banding->keterangan."',
              '".$data_banding->diedit_oleh."',
              '".$data_banding->diedit_tanggal."',
              '".$data_banding->diinput_oleh."',
              '".$data_banding->diinput_tanggal."',
              '".$data_banding->diperbaharui_oleh."',
              '".$data_banding->diperbaharui_tanggal."')
                       ";
      }
      
         
         //proses_date_int($koneksi);
       
      ///echo "Nomor Perkara : ".$data_banding->perkara_id."<br>".$sql_data."<br>";
    }
    //echo $sql_insert."<hr><hr>";
    mysqli_query($koneksi,$sql_insert);
  }
  
}
$sql = "UPDATE singkron SET waktu='". date("Y-m-d H:i:s")."' WHERE id=1";
mysqli_query($koneksi,$sql);
echo "Proses Selesai";
mysqli_close($koneksi);
exit;
?>