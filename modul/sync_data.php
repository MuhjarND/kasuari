<?php
$isi="";
include_once("sys/sys_config.php");
$isi='';
if(isset($_POST['pn_id'])){
  $pn_id=(int)base64_decode($_POST['pn_id']);
  $sqlquery=" SELECT * FROM log_singkron where id_satker=$pn_id ORDER BY id DESC LIMIT 5"; 
  $query=mysqli_query($koneksi,$sqlquery);
  $no=0;
  if (!$query) {
    error_log('KASUARI sync_data query failed: '.mysqli_error($koneksi));
  }
  while($query && ($row=mysqli_fetch_assoc($query))){
    $no++;
    $tanggal=htmlspecialchars((string) ($row["tanggal"] ?? ''), ENT_QUOTES, 'UTF-8');
    $perkara=htmlspecialchars((string) ($row["perkara"] ?? ''), ENT_QUOTES, 'UTF-8');
    $isi.= "<tr><td>".$no."</td><td>".$tanggal."</td><td>".$perkara."</td></tr>";
  }

}
echo base64_encode($isi);
?>
