<?php
include_once("sys/sys_session.php");
//echo base64_decode("dmFyaWFiZWxfc2ltcGFuX2VkaXQ=");exit;
    $nama_halaman="Variabel";
    include_once("sys/sys_header.php");
?>
<div class="w3-row" id="AppContent">
    <div class="w3-container w3-margin-bottom">
        <h3 class="w3-border-bottom">Variabel </h3>
        <button class="w3-btn w3-small w3-round w3-teal w3-right" onclick="tambah_variabel()">Tambah</button>
    </div>
    <div class="w3-row w3-margin " id="results_content">
      
    </div>
</div>

<!-- Modal -->
<div id="modal_detail" class="w3-modal" style="padding-top: 0px;">
  <div class="w3-modal-content" style="width: 100%">
    <div class="w3-row" id="div_modal_detail"></div>
  </div>
</div>
<!-- Modal -->
<link href="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.css" rel="stylesheet" type="text/css">
<script src="assets/plugins/vanilla-dataTables/vanilla-dataTables.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">  
<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function(){
    data_variabel()
  });
  function __(object){
    return document.getElementById(object);
  }
  function data_variabel(){
    __("loader").style = 'display:block';
    var xhr = new XMLHttpRequest();
    var url = 'api';
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        __("results_content").innerHTML=xhr.responseText;
        //__("modal_Carbillo").style.display='block';
        var table = new DataTable("#datane_result", {  perPage: 25,perPageSelect : [10, 25, 50, 100, 500] });
        __("loader").style.display='none'; 
      }
    }
    xhr.send("aksi=<?php echo base64_encode("variabel")?>");
  }
function tutup_modal(){
  document.getElementById("modal_detail").style.display="none"; 
}
function tambah_variabel(){
    var xhr = new XMLHttpRequest();
    xhr.open("POST","api", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        __("div_modal_detail").innerHTML=xhr.responseText;
        __("modal_detail").style.display="block";
        __("var_nomor").focus();
      }
    }
    xhr.send("aksi=<?php echo base64_encode("variabel_tambah")?>");
}
function edit_variabel(id){
    var xhr = new XMLHttpRequest();
    xhr.open("POST","api", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        __("div_modal_detail").innerHTML=xhr.responseText; 
        __("modal_detail").style.display="block"; 
      }
    }
    xhr.send("aksi="+btoa("variabel_edit")+"&id=" + id);
}
function hapus_variabel(id){
  var conf = confirm("Apakah anda yakin akan menghapus data ini?");
  if (conf == true) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST","http://raehman/project/ptaa/eksekusi/variabel/hapus", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
        data_variabel();
        __("modal_detail").style.display="none";
        tutup_modal();
      }
    }
    xhr.send("id=" + btoa(id));
  }
}
function serialize(form){ 
    var field, l, s = [];
    if (typeof form == 'object' && form.nodeName == "FORM") {
        var len = form.elements.length;
        for (var i=0; i<len; i++) {
            field = form.elements[i];
            if (field.name && !field.disabled && field.type != 'file' && field.type != 'reset' && field.type != 'submit' && field.type != 'button') {
                if (field.type == 'select-multiple') {
                    l = form.elements[i].options.length; 
                    for (var j=0; j<l; j++) {
                        if(field.options[j].selected)
                            s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
                    }
                } else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
                    s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
                }
            }
        }
    }
    return s.join('&').replace(/%20/g, '+');
}
function kirim_post(url){
  var var_nomor =__("var_nomor").value;
  var var_keterangan =__("var_keterangan").value;
  if(var_nomor==""){
    __("var_nomor").focus();
    notifier.show('Pesan!', '<h6 class="w3-text-red">Nomor Tidak Boleh Kosong</h6>', '', '', 5000);

    return false;
  }else
  if(var_keterangan==""){
    __("var_keterangan").focus();
    notifier.show('Pesan!', '<h6 class="w3-text-red">Nama Tidak Boleh Kosong</h6>', '', '', 5000);
    return false;
  }
  var xhr = new XMLHttpRequest();
  var data=serialize(f_variabel);  
  xhr.open("POST",url, true); 
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function(){
    if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
      var data=xhr.responseText;  
      data=data.replace(/\r?\n|\r/g, "");
      if(data==""){
        notifier.show('Pesan!',"<h6 class='w3-text-green'>Penyimpanan Berhasil</h6>", '', '', 4000);
      }else{
        notifier.show('Pesan!', data, '', '', 4000);
      }
      data_variabel();
      tutup_modal()
    }else   
    if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 500){
      notifier.show('Pesan!', '<h6 class="w3-text-red">Silahkan Cek Nomor atau Nama, dikarenakan tidak boleh ada Nomor atau Nama yang sama</h6>', '', '', 5000);
    }
  }
  xhr.send(data); 
}
function pilih_model_variabel(isi){
  if(isi=="text"){
    return true;
  }else{
    __("var_tabel").value='';
  }
  if(isi=="SQL"){
    return true;
  }else{
    __("var_sql_data").value='';
    __("var_field").value='';
  }
}
</script>
<?php include_once("sys/sys_footer.php");?>

