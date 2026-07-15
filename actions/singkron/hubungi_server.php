<?php
function status($url,$satker,$id,$koneksi){
  $curl = curl_init($url); 
  curl_setopt($curl, CURLOPT_NOBODY, true); 
  $result = curl_exec($curl); 
  if ($result !== false){
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    if ($statusCode == 404) {
      $status = "<p><b>$satker</b> dengan alamat <a href='$url' target='_blank'>$url</a> </p>";
      $sql="UPDATE pengadilan_agama SET aktif='N' WHERE id=$id";
    }
    else {
      $status = "";
      $sql="UPDATE pengadilan_agama SET aktif='Y' WHERE id=$id";
    }
  }
  else {
    $status = "<p><b>$satker</b> dengan alamat <a href='$url' target='_blank'>$url</a></p>";
    $sql="UPDATE pengadilan_agama SET aktif='N' WHERE id=$id";
  }
  mysqli_query($koneksi,$sql); 
}
$sql = "SELECT * FROM pengadilan_agama";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
  $no++;
  $url=$data["ip_satker"].'/api_monitoring/get_data_api';
  //echo status($url, $data["nama"],$data["id"],$koneksi);
  $id=$data["id"];
  $curl = curl_init($url); 
  curl_setopt($curl, CURLOPT_NOBODY, true); 
  $result = curl_exec($curl); 
  if ($result !== false){
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    if ($statusCode == 404) {
      $status = "<p><b>$satker</b> dengan alamat <a href='$url' target='_blank'>$url</a> </p>";
      $sql1="UPDATE pengadilan_agama SET aktif='N' WHERE id=$id";
    }
    else {
      $status = "";
      $sql1="UPDATE pengadilan_agama SET aktif='Y' WHERE id=$id";
    }
  }
  else {
    $status = "<p><b>$satker</b> dengan alamat <a href='$url' target='_blank'>$url</a></p>";
    $sql1="UPDATE pengadilan_agama SET aktif='N' WHERE id=$id";
  }
  mysqli_query($koneksi,$sql1);
  //echo $sql1."<br>";
}
mysqli_close($koneksi);
exit;
?>