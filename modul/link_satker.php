<?php
include_once("sys/sys_session.php");
//echo base64_decode("dmFyaWFiZWxfc2ltcGFuX2VkaXQ=");exit;
    $nama_halaman="Link_satker";
    include_once("sys/sys_header.php");
?>
<div class="w3-row" id="AppContent">
    <div class="w3-container w3-margin-bottom">
        <h3 class="w3-border-bottom">Link Satker </h3>
    </div>
    <div class="w3-row w3-margin " id="results_content">
      
    </div>
</div>

<link href="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">  
<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function(){
    data_link_satker()
  });
  function __(object){
    return document.getElementById(object);
  }
  function data_link_satker(){
    __("loader").style = 'display:block';
    var xhr = new XMLHttpRequest();
    var url = 'api';
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        __("results_content").innerHTML=xhr.responseText;
        //__("modal_Carbillo").style.display='block';
        var table = new DataTable("#datane_result", {  perPage: 36,perPageSelect : [10, 25, 36, 100, 500] });
        __("loader").style.display='none'; 
      }
    }
    xhr.send("aksi=<?php echo base64_encode("link_satker")?>");
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

