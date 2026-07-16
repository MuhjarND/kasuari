<?php
include_once("sys/sys_session.php");
//error_reporting(E_ALL | E_STRICT);
$nama_halaman="Cetak Dokumen";
include "sys/sys_header.php";
include "sys/sys_fungsi_doc.php";
?>
<link rel="stylesheet" href="assets/plugins/pikaday/pikaday.css"> 
<script src="assets/plugins/pikaday/moment.js"></script>
<script src="assets/plugins/pikaday/id.js"></script>
<script src="assets/plugins/pikaday/pikaday.js"></script>
<div class="w3-container">
<?php
$template_id=isset($_GET['blangko_id']) ? (int) $_GET['blangko_id'] : 0;
$id_banding=isset($_GET['id_banding']) ? (int) $_GET['id_banding'] : 0;
$perkara_id=0;
$pn_id=0;
$kode_dokumen='';

function blangko_gagal($pesan, $detail='')
{
	if ($detail !== '') {
		error_log($detail);
	}
	echo "<div class='w3-panel w3-pale-red w3-leftbar w3-border-red'><p>".
		htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8')."</p></div>";
	include_once("sys/sys_footer.php");
	exit;
}

function blangko_teks($nilai)
{
	return htmlspecialchars(stripslashes((string) $nilai), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($template_id <= 0 || $id_banding <= 0) {
	blangko_gagal('Data perkara atau template dokumen tidak valid.');
}
//cek GET


//tanya jawab awal 
?> 

<?php 
//tanya jawab awal
//SEBUTAN PIHAK DAN SURAT
$sql_info="SELECT perkara_id, pn_id FROM perkara_banding WHERE id=".$id_banding." LIMIT 1";
$quer_info=mysqli_query($koneksi,$sql_info);
if ($quer_info === false) {
	blangko_gagal('Data perkara tidak dapat dibaca.', 'Gagal membaca perkara untuk blangko: '.mysqli_error($koneksi));
}
$perkara_info=mysqli_fetch_assoc($quer_info);
if (!$perkara_info) {
	blangko_gagal('Perkara banding tidak ditemukan.');
}
$perkara_id=(int) $perkara_info['perkara_id'];
$pn_id=(int) $perkara_info['pn_id'];
//SEBUTAN PIHAK DAN SURAT	


//TEMPLATE YANG DIGUNAKAN 
$sql_dokumen="SELECT kode FROM template_dokumen WHERE id=".$template_id." LIMIT 1";
//echo "$sql_dokumen";
$rtf="";
$query_template=mysqli_query($koneksi,$sql_dokumen);
if ($query_template === false) {
	blangko_gagal('Template dokumen tidak dapat dibaca.', 'Gagal membaca template dokumen: '.mysqli_error($koneksi));
}
$template_info=mysqli_fetch_assoc($query_template);
if (!$template_info || trim((string) $template_info['kode']) === '') {
	blangko_gagal('Template dokumen tidak ditemukan.');
}
$kode_dokumen=basename((string) $template_info['kode']).".rtf";
//TEMPLATE YANG DIGUNAKAN 

//BUKA TEMPLATE 
$lokasi_template="template/".$kode_dokumen;
if (!is_file($lokasi_template) || !is_readable($lokasi_template)) {
	blangko_gagal('Berkas template belum tersedia. Hubungi administrator.', 'Template tidak ditemukan: '.$lokasi_template);
}
$rtf=file_get_contents($lokasi_template);
if ($rtf === false) {
	blangko_gagal('Berkas template tidak dapat dibuka. Hubungi administrator.', 'Gagal membaca template: '.$lokasi_template);
}
$rtf=normalisasi_penanda_variabel_dokumen($rtf);
$variabel = variabel_dokumen($rtf);
$jml_variabel= count($variabel);$no=0;
//echo $jml_variabel."<br>"; 
//BUKA TEMPLATE 

echo "<form name='frm_cetak' id='frm_cetak' action='_dokumen_cetak' method=POST>";
echo "<input type='hidden' name='template_id' value='".$template_id."'>";
echo "<input type='hidden' name='perkara_id' id='perkara_id' value='".$perkara_id."'>";
echo "<input type='hidden' name='pn_id' id='pn_id' value='".$pn_id."'>";
echo "<center><b>File Blangko : ".blangko_teks($kode_dokumen)."</b></center>";
echo "<table class='w3-table-all' align=center><tr><th>Keterangan</th><th>Data</th></tr>";
for ($variabel_posisi = 0; $variabel_posisi < $jml_variabel; $variabel_posisi++){
	$no++; 
	$variabelnya=$variabel[$variabel_posisi]; 
	$sql="select * from master_variabel where var_nomor='$variabelnya'";
 	//echo "----".$sql."----<br>";
	$query=mysqli_query($koneksi,$sql);
	if ($query === false) {
		error_log('Gagal membaca master variabel '.$variabelnya.': '.mysqli_error($koneksi));
		echo "<tr><td colspan='2' class='w3-text-red'>Data variabel tidak dapat dibaca.</td></tr>";
		continue;
	}
	
	/////JIKA BELUM ADA VARIABEL
	if(mysqli_num_rows($query)==0){ 
		echo "<tr><td colspan=2 class=w3-text-red>Nomor Variabel ".$variabelnya." Belum Tersedia</td></tr>";
	}
	/////JIKA BELUM ADA VARIABEL
	
while($h_info=mysqli_fetch_assoc($query)){ 
	$var_keterangan=$h_info["var_keterangan"];
	$fungsi='';
	//$var_keterangan=str_replace("#0046#",$sebutan_pihak1,$var_keterangan);
	//$var_keterangan=str_replace("#0047#",$sebutan_pihak2,$var_keterangan); 
	//$var_keterangan=str_replace("#0053#",$gugatan_permohonan,$var_keterangan); 
	$var_model=$h_info["var_model"];
	$var_sumber_sipp=$h_info["var_sumber_sipp"];
	$var_sql_data=$h_info["var_sql_data"];
	$var_tabel=$h_info["var_tabel"];
	$var_field=$h_info["var_field"];
	$var_cek_sidang=$h_info["var_cek_sidang"];
	$var_fungsi_nama=$h_info["var_fungsi_nama"];
	//cadangan
	$sebutan_pihak1="";
	$sebutan_pihak2="";
	$gugatan_permohonan="";
	//$koneksi="";
	$id_sidang="";
	//cadangan
	if($h_info["var_model"]<>"tanya_jawab"){
		$isi="";
		$isi=isi_variabel($variabelnya, $perkara_id, $pn_id, $var_model, $var_sumber_sipp, $var_sql_data, $var_tabel, $var_field, $var_cek_sidang, $var_fungsi_nama,$sebutan_pihak1,$sebutan_pihak2,$gugatan_permohonan,$var_keterangan,$koneksi);	
		
		//echo "<br> Variabel ".$variabelnya. " Data ".mysqli_num_rows
		if($isi==""){
			$default_data=isset($h_info["var_default_data"]) ? (string) $h_info["var_default_data"] : '';
			
			$variabel_default_data = variabel_dokumen($default_data);
			$jml_variabel_default_data= count($variabel_default_data);$no_default_data=0;
			for ($variabel_posisi_default_data = 0; $variabel_posisi_default_data < $jml_variabel_default_data; $variabel_posisi_default_data++){
				$no++; 
				$variabelnya_default_data=$variabel_default_data[$variabel_posisi_default_data]; 
				$sql_default_data="select * from master_variabel where var_nomor='$variabelnya_default_data'";
				//echo "----".$sql."----<br>";
				$query_default_data=mysqli_query($koneksi,$sql_default_data);
				if ($query_default_data === false) {
					error_log('Gagal membaca variabel default '.$variabelnya_default_data.': '.mysqli_error($koneksi));
					continue;
				}
				 
				/////JIKA BELUM ADA VARIABEL
				 
				/////JIKA BELUM ADA VARIABEL
				
				while($h_info_default_data=mysqli_fetch_assoc($query_default_data)){ 	$var_keterangan_default_data=$h_info_default_data["var_keterangan"];
					$var_keterangan_default_data=str_replace("#0046#",$sebutan_pihak1,$var_keterangan_default_data);
					$var_keterangan_default_data=str_replace("#0047#",$sebutan_pihak2,$var_keterangan_default_data); 
					$var_keterangan_default_data=str_replace("#0053#",$gugatan_permohonan,$var_keterangan_default_data); 
					$var_model_default_data=$h_info_default_data["var_model"];
					$var_sumber_sipp_default_data=$h_info_default_data["var_sumber_sipp"];
					$var_sql_data_default_data=$h_info_default_data["var_sql_data"];
					$var_tabel_default_data=$h_info_default_data["var_tabel"];
					$var_field_default_data=$h_info_default_data["var_field"];
					$var_cek_sidang_default_data=$h_info_default_data["var_cek_sidang"];
					$var_fungsi_nama_default_data=$h_info_default_data["var_fungsi_nama"];
					$isi_default_data=isi_variabel($variabelnya_default_data, $perkara_id, $pn_id, $var_model_default_data, $var_sumber_sipp_default_data, $var_sql_data_default_data, $var_tabel_default_data, $var_field_default_data, $var_cek_sidang_default_data, $var_fungsi_nama_default_data,$sebutan_pihak1,$sebutan_pihak2,$gugatan_permohonan,$var_keterangan_default_data,$koneksi);
					///
					$default_data=str_replace("#".$variabelnya_default_data."#",$isi_default_data, $default_data);
				}
			}	
			$isi=$default_data;
		}
		//jenis_inputan
		 
		 if(!empty($h_info["var_tabel"])){$fungsi="onchange=edit_isi('".$variabelnya."',this.value)";}
		 

		echo "<tr><td valign='top' style='width:250px'>".blangko_teks($var_keterangan)."</td><td valign='top'>";
		
		if($h_info["var_jenis"]=='textarea'){
			$tinggi=max(3,min(18,(int) ceil(strlen((string) $isi)/60)));
			/////////////////////AMAR
			if($h_info["var_nomor"]=='0065'){
				$tinggi=10;
				$fungsi="onchange=edit_isi('".$variabelnya."',this.value)";
			//$tinggi=6;
			?>
			<select id="konsep_amar" onchange="pilih_amar()">
				<option value="">Pilih Amar</option>
				<?php 
					//$string = file_get_contents("_dokumen_konsep_amar.php?perkara_id=".$perkara_id."&sebutan_p=".$sebutan_pihak1."&sebutan_t=".$sebutan_pihak2."&sebutan_surat=".$gugatan_permohonan."&jenis_perkara_id=".$jenis_perkara_id);
					//echo $string;
					//include("_dokumen_konsep_amar.php");
				?>
			</select> 
			 
			<script type="text/javascript">
				function pilih_amar(){
					document.getElementById("0065").value=onchange=document.getElementById("konsep_amar").value;
				}
			</script>
			<br>
			<textarea <?php echo $fungsi?> style="min-height:50px" class="w3-input w3-border" rows="<?php echo $tinggi?>" id="<?php echo $h_info['var_nomor']?>" name="<?php echo $h_info['var_nomor']?>"><?php echo blangko_teks(str_replace(";;",";",$isi))?></textarea>
			<?php
			/////////////////////AMAR
			}else{
			$fungsi="onchange=edit_isi('".$variabelnya."',this.value)";
			//$tinggi=6;
			?><textarea <?php echo $fungsi?>  class="w3-input w3-border"  style="min-height:50px" rows="<?php echo $tinggi?>" id="<?php echo $h_info['var_nomor']?>" name="<?php echo $h_info['var_nomor']?>"><?php echo blangko_teks(str_replace(";;",";",$isi))?></textarea>
			<?php
			}
		}else
		if($h_info["var_fungsi_nama"]=="tanggal_indonesia"){	
			$fungsi="onBlur=edit_isi('".$variabelnya."',this.value)";	
			?><input <?php echo $fungsi?> class="w3-input w3-border" id="<?php echo $h_info['var_nomor']?>" name="<?php echo $h_info['var_nomor']?>" value="<?php echo blangko_teks($isi)?>">
			 <script> 
				 var picker_<?php echo $h_info['var_nomor']?> = new Pikaday({
					field: document.getElementById('<?php echo $h_info['var_nomor']?>'),
					format: 'DD MMMM YYYY', 
						firstDay: 1, 
						minDate: new Date('1900-01-01'),
						maxDate: new Date('<?php $tahun=date("Y")+1;echo $tahun?>-12-31'),
						disableWeekends:'true', 		
						i18n: {
					previousMonth : 'Bulan Sebelum',
					nextMonth     : 'Bulan Berikut',
					months        : ['Januari','Pebruari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','Nopember','Desember'],
					weekdays      : ['Ahad','Senin','Selasa','Rabu','Kamis','Jum at','Sabtu'],
					weekdaysShort : ['Mg','Sen','Sel','Rab','Kam','Jum','Sab'] 
				}
					
				});  
			 </script>
		<?php 
		}else{
			$fungsi="onchange=edit_isi('".$variabelnya."',this.value)";
			?><input <?php echo $fungsi?> class="w3-input w3-border" id="<?php echo $h_info['var_nomor']?>" name="<?php echo $h_info['var_nomor']?>" value="<?php echo blangko_teks($isi)?>">
			 <?php 
			  
		}
		//jenis_inputan
		echo "</td></tr>";	
	} else {
		$isi=isset($h_info["var_default_data"]) ? (string) $h_info["var_default_data"] : '';
		$fungsi="onchange=edit_isi('".$variabelnya."',this.value)";
		echo "<tr><td valign='top' style='width:250px'>".blangko_teks($var_keterangan)."</td><td valign='top'>";
		echo "<textarea ".$fungsi." class='w3-input w3-border' style='min-height:120px' rows='6' id='".blangko_teks($variabelnya)."' name='".blangko_teks($variabelnya)."'>".blangko_teks($isi)."</textarea>";
		echo "</td></tr>";
	}
}
	  
	 
}

echo "</table>" ;
echo "<center>"; 
echo '<div  id="loader" class="loader" style="display:none"></div>';
echo '<div  id="success"   style="display:none"></div><br>';
$nama_form='frm_cetak';
$tujuan='_dokumen_cetak';
echo "<input class='w3-btn w3-green' onclick=kirim_post('".$tujuan."') type=button  value=Cetak> ";
echo "<a href='perkara_detil_banding&id=".$id_banding."' class='w3-btn w3-red'><< Kembali</a> </center>" ;
echo "</form><br><br><br><br>" ;
?> 
<style>.loader{border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid blue;border-right:16px solid green;border-bottom:16px solid red;width:60px;height:60px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0deg)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}table,td{border:0}</style>
 <link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">  

<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script> 

<script>  
     
 
</script>
 
<script type="text/javascript">
	function serialize(form) 
{ 
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

function kirim_post(url)
{ 
	document.getElementById("loader").style='display:block' ;
	var xhr = new XMLHttpRequest();
	var data=serialize(frm_cetak);  
	xhr.open("POST",url, true); 
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhr.onreadystatechange = function() {//Call a function when the state changes.
		if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
			//document.getElementById("pesan_kirim").style="display.block" ;
			var str=xhr.responseText ;
			document.getElementById("loader").style='display:none' ; 
			document.getElementById("success").style='display:block' ;  
			var res = str.split("^");
			if(res[1])
			{
				document.getElementById("success").innerHTML=res[0];
				window.location.href =res[1];
			}else
			{
				document.getElementById("success").innerHTML=str;
			}
			
			
		}
	}
	xhr.send(data); 
}; 

function edit_isi(var_nomor, isi){
    var xhr = new XMLHttpRequest();
	var perkara_id=document.getElementById('perkara_id').value;
	xhr.open("POST","api", true); 
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhr.onreadystatechange = function() {
		if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
			 var pesan=xhr.responseText; 
			 notifier.show('Pesan!', pesan, '', '',5000); 
		}
	}
	xhr.send("aksi="+btoa("isi_variabel_update")+"&var_nomor="+btoa(var_nomor)+"&DATA="+btoa(isi)+"&perkara_id="+btoa("<?php echo $perkara_id?>")+"&pn_id="+btoa("<?php echo$pn_id?>")); 
} 
</script>
</div>
<?php include_once("sys/sys_footer.php");?>
