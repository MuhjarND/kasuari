<?php
if(isset($_GET["cetak"])){

ini_set('display_errors', 0);
ini_set('log_errors', 0);
date_default_timezone_set("Asia/Jakarta");
    function lempar($url) {
        echo '<script language = "javascript">';
        echo 'window.location.href = "'.$url.'"';
        echo '</script>';
    } 
    include_once("sys/sys_koneksi.php");
    include_once("sys/sys_fungsi.php");
    $sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.permohonan_banding) as tanggalpermohonanbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_pn) as tanggalputusan,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju,
          DATEDIFF(CURDATE(),perkara_banding.permohonan_banding) AS selisih
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE 
        perkara_banding.pengiriman_berkas_banding  IS NULL AND perkara_banding.tanggal_pendaftaran_banding IS NULL AND  perkara_banding.tanggal_cabut IS NULL 
        AND year(perkara_banding.permohonan_banding)>=year(CURDATE())-1
         AND perkara_banding.nomor_perkara_banding=''
        ORDER BY pengadilan_agama.nama ASC, perkara_banding.permohonan_banding ASC, pengadilan_agama.nama ASC";
    $query=mysqli_query($koneksi,$sql);
    $rtf=file_get_contents("template/perkara_belum_dikirim.rtf");
      $tabelnya.='\trowd
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx500
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx3100
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx6000
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx8200 
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx9400\rin57\lin57
No\intbl\cell\rin57\lin57
Satker\intbl\cell\rin57\lin57
Nomor Perkara\intbl\cell\rin57\lin57
Permohonan Banding\intbl\cell\rin57\lin57
Lama\intbl\cell 
\row';
    $no=0;
    while($data=mysqli_fetch_assoc($query)){
      $no++;
      $tabelnya.='\trowd
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx500
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx3100
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx6000
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx8200 
\clbrdrt\brdrs\clbrdrl\brdrs\clbrdrb\brdrs\clbrdrr\brdrs
\cellx9400\rin57\lin57
'.$no.'\intbl\cell\rin57\lin57
'.str_replace("PENGADILAN AGAMA","PA",$data["pengaju"]).'\intbl\cell\rin57\lin57
'.$data["nomor_perkara_pn"].'\intbl\cell\rin57\lin57
'.$data["tanggalpermohonanbanding"].'\intbl\cell\rin57\lin57
'.$data["selisih"].' hari\intbl\cell 
\row';
    }
    $tabelnya.='\pard\par';
    $rtf= str_replace("#JUDUL1#","PERKARA BANDING YANG BELUM DIKIRIM",$rtf) ;
    $rtf= str_replace("#JUDUL2#","PADA PENGADILAN AGAMA SE PTA SEMARANG",$rtf) ;
    $rtf= str_replace("#JUDUL3#","PER ".strtoupper(tanggal_indon(date("Y-m-d"))." JAM ".date("H:i:s")),$rtf) ;
    $rtf= str_replace("#tanggal#",tanggal_indon(date("Y-m-d")),$rtf) ;
    $rtf= str_replace("#data#",$tabelnya,$rtf) ;
    $nama_file_hasil="perkara_belum_dikirim.rtf";
    $hasil_lokasi="hasil/".$nama_file_hasil;
    $hasil=file_put_contents($hasil_lokasi,$rtf);
    lempar($hasil_lokasi);
    exit;
}
include_once("sys/sys_session.php");
    $nama_halaman="Perkara Belum dikirim";
    include_once("sys/sys_header.php");

?>
<div class="w3-row" id="AppContent">
  <div class="w3-container">
    <div class="w3-row w3-margin-bottom">
        <h4 class="w3-border-bottom">Daftar Perkara yang mengajukan Banding Belum dikirim (Tanggal Pengiriman Berkas Banding Kosong/ Tanggal Register Banding Kosong)</h4>
        <p><a class="w3-btn w3-right w3-border w3-teal w3-round w3-small" target="_blank" href="register_perkara_belum_dikirim&cetak">Cetak</a></p>
    </div>

    <div class="w3-row" id="results_content">
      <?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>PA Pengaju</th>
<th>Nomor Perkara Tk. 1</th>
<th>Tanggal Putusan</th>
<th>Permohonan Banding</th>
<th>Status Banding</th>
<th>Waktu</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.permohonan_banding) as tanggalpermohonanbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_pn) as tanggalputusan,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju,
          DATEDIFF(CURDATE(),perkara_banding.permohonan_banding) AS selisih
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE 
        perkara_banding.pengiriman_berkas_banding  IS NULL AND perkara_banding.tanggal_pendaftaran_banding IS NULL AND  perkara_banding.tanggal_cabut IS NULL AND perkara_banding.nomor_perkara_banding=''
        AND year(perkara_banding.permohonan_banding)>=year(CURDATE())-1
        ORDER BY perkara_banding.permohonan_banding ASC, pengadilan_agama.nama ASC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
  $no++;
  $table.='<tr>
  <td class="w3-center">'.$no.'</td>
  <td class="w3-left-align">'.str_replace("PENGADILAN AGAMA", "PA", $data["pengaju"]).'</td>
  <td class="w3-left-align">'.$data["nomor_perkara_pn"].'</td>
  <td class="w3-left-align">'.$data["tanggalputusan"].'</td>
  <td class="w3-left-align">'.$data["tanggalpermohonanbanding"].'</td>
  <td class="w3-left-align">'.$data["status_banding_text"].'</td>
  <td class="w3-left-align">'.$data["selisih"].' hari</td>
  <td class="w3-center"><a href="perkara_detil_banding&id='.$data["id"].'" title="Detail Perkara">Link</a></td>
  </tr>';
}
if($no==0){
  $table.='<tr><td class="w3-center w3-text-red" colspan="8">Tidak ada Data</td></tr>';
}
$table.="</tbody></table><br><br></div>";
echo "$table";
?>
  </div>
</div>
<link href="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<script type="text/javascript">
    var table = new DataTable("#datane_result", {  perPage: 50,perPageSelect : [10, 25, 50, 100, 500] });

  function __(object){
    return document.getElementById(object);
  }
</script>
<?php include_once("sys/sys_footer.php");?>

