<?php
include_once("sys/sys_session.php");
//echo base64_decode("dmFyaWFiZWxfc2ltcGFuX2VkaXQ=");exit;
    $nama_halaman="Identitas Satker";
    include_once("sys/sys_header.php");
    $sql="SELECT  * FROM sys_konfig";
      $query=mysqli_query($koneksi,$sql);
      while($data=mysqli_fetch_assoc($query)){
        foreach($data as $key=>$value) {$$key=$value;}
      }
?>
<div class="w3-row" id="AppContent">
    <div class="w3-container w3-margin-bottom">
        <h3 class="w3-border-bottom">Identitas Satker </h3>
        <p class="">Untuk merubah silahkan ubah pada Isian </p>
    </div>
    <div class="w3-row w3-margin " id="results_content">
      <table class="w3-table-all">
        <tr>
          <td style="width: 200px">Nama PTA</td>
          <td><input class="w3-input w3-border" value="<?php echo $nama_pta?>" onchange="edit_tabel('sys_konfig','nama_pta', 'id', <?php echo $id?>, this.value)"></td>
        </tr>
        <tr>
          <td>Nama Ketua</td>
          <td><input class="w3-input w3-border" value="<?php echo $nama_ketua?>" onchange="edit_tabel('sys_konfig', 'nama_ketua','id', <?php echo $id?>, this.value)"></td>
        </tr>
        <tr>
          <td>Nama Wakil Ketua</td>
          <td><input class="w3-input w3-border" value="<?php echo $nama_wakil_ketua?>" onchange="edit_tabel('sys_konfig', 'nama_wakil_ketua','id', <?php echo $id?>, this.value)"></td>
        </tr>
        <tr>
          <td>Nama Panitera</td>
          <td><input class="w3-input w3-border" value="<?php echo $nama_panitera?>" onchange="edit_tabel('sys_konfig', 'nama_panitera','id', <?php echo $id?>, this.value)"></td>
        </tr>
        <tr>
          <td>Nama Panmud Banding</td>
          <td><input class="w3-input w3-border" value="<?php echo $nama_panmud_banding?>" onchange="edit_tabel('sys_konfig', 'nama_panmud_banding','id', <?php echo $id?>, this.value)"></td>
        </tr>
      </table>
    </div>
</div>

<link href="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">  
<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function(){
    //data_link_satker()
  });
  function __(object){
    return document.getElementById(object);
  }
  
function edit_tabel(tabel, field, kunci, id, isi){
  var xhr = new XMLHttpRequest();
  xhr.open("POST","api", true); 
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  xhr.onreadystatechange = function() {
    if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
       var pesan=xhr.responseText; 
       notifier.show('Pesan!', pesan, '', '',5000); 
    }
  }
  xhr.send("aksi="+btoa("edit_tabel")+"&tabel="+btoa(tabel)+"&field="+btoa(field)+"&kunci="+btoa(kunci)+"&id="+btoa(id)+"&isi="+btoa(isi)); 
}
</script>
<?php include_once("sys/sys_footer.php");?>

