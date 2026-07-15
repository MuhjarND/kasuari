<?php
include_once("sys/sys_session.php");
    $nama_halaman="Perkara Belum dikirim";
    include_once("sys/sys_header.php");
?>
<div class="w3-row" id="AppContent">
  <div class="w3-container">
    <div class="w3-row w3-margin-bottom">
        <h4 class="w3-border-bottom">Daftar Perkara yang dicabut</h4>
    </div>

    <div class="w3-row" id="results_content">
      <?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>PA Pengaju</th>
<th>Nomor Perkara Tk. 1</th>
<th>Permohonan Banding</th>
<th>Tanggal Cabut</th>
<th>Status Banding</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.permohonan_banding) as tanggalpermohonanbanding,
          convert_tanggal_indonesia(perkara_banding.tanggal_cabut) as tanggalcabut,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju,
          DATEDIFF(CURDATE(),perkara_banding.permohonan_banding) AS selisih
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE perkara_banding.tanggal_cabut IS NOT NULL 
        ORDER BY perkara_banding.tanggal_cabut DESC, pengadilan_agama.nama ASC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
  $no++;
  $table.='<tr>
  <td class="w3-center">'.$no.'</td>
  <td class="w3-left-align">'.str_replace("PENGADILAN AGAMA", "PA", $data["pengaju"]).'</td>
  <td class="w3-left-align">'.$data["nomor_perkara_pn"].'</td>
  <td class="w3-left-align">'.$data["tanggalpermohonanbanding"].'</td>
  <td class="w3-left-align">'.$data["tanggalcabut"].'</td>

  <td class="w3-left-align">'.$data["status_banding_text"].'</td>
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

