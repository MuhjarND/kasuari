<?php  header('Content-Type: text/html; charset=utf-8'); 

function lempar($url) {
    echo '<script language = "javascript">';
    echo 'window.location.href = "'.$url.'"';
    echo '</script>';
} 
ini_set('display_errors', true);
ini_set('display_startup_errors', true); 
include "sys/sys_koneksi.php";
$perkara_id=$_POST["perkara_id"];
$template_id=$_POST["template_id"];
$sql_dokumen="SELECT kode    FROM  template_dokumen WHERE id=".$template_id; 
//echo "$sql_dokumen";
$rtf="";
$query_template=mysqli_query($koneksi,$sql_dokumen);
while($template_info=mysqli_fetch_assoc($query_template)) 
{
	$kode_dokumen=$template_info["kode"].".rtf"; 
	$rtf=file_get_contents("template/".$kode_dokumen);	
	 
	foreach($_POST as $key=>$value) 
	{ 
		if($key==5058 OR $key==5059 OR $key==5060 OR $key==5061 OR $key==8100 OR $key==8101 OR $key==5062 OR $key==5063 OR $key==5064 OR $key==5065 OR $key==20000)
		{
			//lama
			//$value=str_replace("&nbsp;"," ", $value);
			//$value=str_replace(";;",";", $value);
			//$value=str_replace("^"," \par \pard\li3254\sa200\sl360\slmult1\qj ", $value);
			//$value=str_replace("|"," \par \pard\sa200\sl360\slmult1\qj\lang33 ", $value);
			//$rtf= str_replace("#".$key."#",$value,$rtf) ; 
			//lama
			
			//Baru
				$value=str_replace("&nbsp;"," ", $value);
				$value=str_replace("   "," ", $value);
				$value=str_replace("  "," ", $value);
				$isinya=explode("|",$value);
				$jml_tanya_jawab=count($isinya);
				$tabelnya="";
				for ($tanya_jawab_posisi = 0; $tanya_jawab_posisi < $jml_tanya_jawab-1; $tanya_jawab_posisi++) 
				{
					$data_baris=$isinya[$tanya_jawab_posisi];
					$pecah_tanya_jawab=explode("^",$data_baris);
					$tabelnya.='\trowd\cellx3800\cellx8500\intbl '.trim($pecah_tanya_jawab[0]);
					$tabelnya.='\cell\intbl \cell\row \trowd\cellx3800\cellx8500\intbl\cell\intbl '.trim($pecah_tanya_jawab[1]).'\cell\row'; 
				}
				$tabelnya.='\pard\par';
				$rtf= str_replace("#".$key."#",$tabelnya,$rtf) ;
			//Baru 
		}else
		{ 
			$value=str_replace(";;",";", $value);
			$value=str_replace(chr(13),";", $value);
			$value=str_replace(chr(10),";", $value);
			//$value=str_replace(chr(9),"\tab ", $value);
		//	$value=str_replace('\t',"\tab ", $value);
			$value=str_replace('\n',";", $value);
			$value=str_replace('; ;',";", $value);
			$value=str_replace(';;',";", $value);
			$value=str_replace(';;',";", $value);
			$value=str_replace('.;',";", $value);
			$value=str_replace(';;',";", $value);
			$value=str_replace('-;',";", $value);
			$value=str_replace(';',";\par ", $value);
			$value=str_replace("ï¿½","'", $value);
			$value=str_replace(" ,","", $value);
			$value=str_replace("\'ef\'bf\'bd\'ef\'bf\'bd\'ef\'bf\'bd\loch\f1","", $value);
			$rtf= str_replace("#".$key."#",$value,$rtf) ;
		}
	}
	//$nama_file_hasil=str_replace("/","_",@$nomor_perkara)."_".@$jenis_blangko_nama."_".date("Y-m-d").".rtf";
	$nama_file_hasil="preview.rtf";
	//replace karakter khusus
	$rtf= str_replace("\'ef\'bf\'bd\loch\f1","",$rtf) ;
	$rtf= str_replace("\'ef\'bf\'bd","",$rtf) ;
	//replace karakter khusus
	$hasil_lokasi="hasil/".$nama_file_hasil;
	$hasil=file_put_contents($hasil_lokasi,$rtf);
	//echo '<br><center><a href="'.$hasil_lokasi.'" class="w3-btn  w3-small w3-green">.:: Unduh Ulang ::.</a><center>';
	echo '^'.$hasil_lokasi;
}
mysqli_close($koneksi);
?> 