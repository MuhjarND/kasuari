<?php
$isi="";
include_once("sys/sys_config.php");
if(isset($_POST['pn_id'])){
  $pn_id=(int)base64_decode($_POST['pn_id']);
  $sqlquery=" SELECT * FROM log_singkron where id_satker=$pn_id ORDER BY id DESC LIMIT 5"; 
  $query=mysqli_query($koneksi,$sqlquery);
  $no=0;
  while($row=mysqli_fetch_assoc($query)){
    $no++;
    $isi.= "<tr><td>".$no."</td><td>".$row["tanggal"]."</td><td>".$row["perkara"]."</td></tr>";
  }

}
echo base64_encode($isi);
?>