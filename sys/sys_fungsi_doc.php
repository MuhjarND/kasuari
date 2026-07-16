<?php
require_once(__DIR__ . "/sys_koneksi.php");
function halaman_index($jabatan_id, $group, $url_sipp, $mulai_wajib_upload_bas_put)
{ 
$koneksi=isset($GLOBALS['koneksi']) ? $GLOBALS['koneksi'] : null;
if (!($koneksi instanceof mysqli)) {
	error_log('Koneksi database tidak tersedia pada halaman dokumen.');
	return;
}
$sekarang=date("Y-m-d");
	//hakim
	//10 20
	// require_once("Encrypt.php"); $x = new CI_Encrypt();$kunci   = "m4hk4m4h4gung";  
	
	if($group==1)
	{
		echo '<br><center><table border=0>
				<tr>
					<td style="border:none"><a class="btn"  style="text-decoration:none" href="dokumen_blangko" title="Blangko Dokumen">Blangko Dokumen</a></td>
					<td style="border:none"><a class="btn"  style="text-decoration:none"  href="dokumen_variabel" title="Variabel">Variabel</a></td>
					<td style="border:none"><a  class="btn"  style="text-decoration:none"  href="dokumen_tanya_jawab" title="Tanya Jawab">Tanya Jawab</a></td>
					<td style="border:none"><a  class="btn"  style="text-decoration:none"  href="update" title="Update Aplikasi">Update</a></td>
				</tr>
				</table><center>
		 
	 </p>';
		
		//daftar posita yang belum masuk
		$sql="SELECT 
				 perkara.perkara_id 
				 ,perkara.nomor_perkara 
				,perkara.posita 
				,perkara.petitum
				,convert_tanggal_indonesia(perkara.tanggal_pendaftaran) AS tanggal_pendaftaran  
			FROM 
				perkara   
			LEFT JOIN 
				perkara_putusan ON perkara_putusan.`perkara_id`=perkara.`perkara_id`
			 
			WHERE 
			perkara_putusan.tanggal_putusan IS NULL AND ( perkara.posita IS NULL OR perkara.petitum IS NULL) ORDER BY perkara_id ASC";
			$query=mysqli_query($koneksi,$sql);
			if ($query === false) {
				error_log('Gagal membaca daftar posita/petitum: '.mysqli_error($koneksi));
			}
			$jumlah=$query ? mysqli_num_rows($query) : 0;
			if($jumlah>=1)
			{
				
				echo "<h3 align=center>Perkara yang Postita atau Petitum Masih Kosong</h3>
				<div class='cssTable' id='tablePerkara' style='width:700px'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='10%'>
						<col width='40%'>
						<col width='30%'>  
						<col width='30%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Nomor Perkara</td>"; 
				echo "<td>Tanggal <br>Registrasi</td>"; 
				echo "<td>Operasi</td>";
				echo "</tr>";
				$no=0;
				while($h=mysqli_fetch_assoc($query)) 
				{
					$no++;foreach($h as $key=>$value) {$$key=$value;}
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=right>".$nomor_perkara."</td>";
					echo "<td align=center>".$tanggal_pendaftaran."</td> ";
					echo "<td align=center><a target=_blank title='Tentukan PHS' href='".$url_sipp."/perkara_detil_agama/".base64_encode($x->encode($perkara_id,$kunci))."'>Buka Perkara di SIPP</a></td>";
					echo "</tr>";
					
				}
				echo "</tbody></table>";
				echo "</div>";
			}
	}else		
	if($group==10 OR $group==20)
	{
		//perkara yang belum di PHS
		$sql="SELECT 
				perkara_hakim_pn.perkara_id
				,perkara.nomor_perkara 
				,perkara.jenis_perkara_nama 
				,perkara.para_pihak  
				,convert_tanggal_indonesia(perkara.tanggal_pendaftaran) AS tanggal_pendaftaran
				,convert_tanggal_indonesia(perkara_hakim_pn.tanggal_penetapan) AS tanggal_penetapan 
			FROM 
				perkara_hakim_pn 
			LEFT JOIN 
				perkara ON perkara.`perkara_id`=perkara_hakim_pn.`perkara_id`
			LEFT JOIN 
				perkara_penetapan ON perkara_penetapan.`perkara_id`=perkara_hakim_pn.`perkara_id`
			WHERE 
			perkara_hakim_pn.`hakim_id`=$jabatan_id  AND perkara_hakim_pn.`jabatan_hakim_id`=1 AND perkara_hakim_pn.`aktif`='Y' AND perkara_penetapan.`penetapan_hari_sidang` IS NULL";
			$query=mysqli_query($koneksi,$sql);
			$jumlah=$query ? mysqli_num_rows($query) : 0;
			if($jumlah>=1)
			{
				
				echo "<h3 align=center>Perkara yang perlu di tentukan Penetapan Hari Sidang</h3>
				<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='5%'>
						<col width='15%'>
						<col width='15%'>
						<col width='15%'>
						<col width='25%'>
						<col width='15%'>
						<col width='10%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Nomor Perkara</td>";
				echo "<td>Jenis Perkara</td>";
				echo "<td>Tanggal <br>Registrasi</td>";
				echo "<td>Para Pihak</td>";
				echo "<td>Tanggal Penetapan</td>";
				echo "<td>Operasi</td>";
				echo "</tr>";
				$no=0;
				while($h=mysqli_fetch_assoc($query)) 
				{
					$no++;foreach($h as $key=>$value) {$$key=$value;}
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=right>".$nomor_perkara."</td>";
					echo "<td align=center>".$jenis_perkara_nama."</td>";
					echo "<td align=center>".$tanggal_pendaftaran."</td>"; 
					echo "<td>".$para_pihak."</td>"; 
					echo "<td align=center>".$tanggal_penetapan."</td>";  
					echo "<td align=center><a target=_blank title='Tentukan PHS' href='".$url_sipp."/perkara_detil_agama/".base64_encode($x->encode($perkara_id,$kunci))."'>Buka Perkara di SIPP</a></td>";
					echo "</tr>";
					
				}
				echo "</tbody></table>";
				echo "</div>";
			}
		//perkara yang belum di PHS	
		
		//perkara diputus  KM
	  
		$sql_putus="SELECT 
						convert_tanggal_indonesia(a.tanggal_putusan) as tanggal_putus
						, b.`nomor_perkara`
						, b.`para_pihak`
						, a.`perkara_id`
						, b.`jenis_perkara_nama`   
						,d.`hakim_nama`
						,d.jabatan_hakim_id
						,d.`aktif`
						,d.`hakim_id`
						,f.nama as jenis_putusan
					FROM perkara_putusan AS a
					LEFT JOIN perkara AS b ON b.`perkara_id`=a.`perkara_id` 
					LEFT JOIN  perkara_hakim_pn AS d ON d.`perkara_id`=a.`perkara_id`
					LEFT JOIN  status_putusan AS f ON f.id=a.status_putusan_id
					 
					WHERE 
					a.tanggal_putusan >= '$mulai_wajib_upload_bas_put'
					AND 
					a.amar_putusan_dok IS NULL 
					AND
					d.`hakim_id`=$jabatan_id  
					AND 
					d.`jabatan_hakim_id`=1 AND d.`aktif`='Y'";
			$query_putus=mysqli_query($koneksi,$sql_putus);		
			$jumlah_putus=$query_putus ? mysqli_num_rows($query_putus) : 0;
			if($jumlah_putus>=1)
			{
			//echo "---<br>".$sql_putus."<br>--------------------";
				
				echo "<h3 align=center>Putusan yang belum dibuat / diupload  (sebagai Ketua Majelis)</h3>
				<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='6%'>
						<col width='20%'>
						<col width='15%'>
						<col width='15%'>  
						<col width='30%'>  
						<col width='14%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Nomor Perkara</td>";
				echo "<td>Jenis Perkara</td>"; 
				echo "<td>Tanggal <br>Status Putusan</td>";
				echo "<td>Para Pihak</td>";
				echo "<td>Operasi</td>";
				echo "</tr>";
				$no=0;
				while($h_putus=mysqli_fetch_assoc($query_putus)) 
				{
					$no++;foreach($h_putus as $key=>$value) {$$key=$value;}
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=center>".$nomor_perkara."</td>";
					echo "<td align=center>".$jenis_perkara_nama."</td>";  
					echo "<td align=center>".$tanggal_putus."<br>".$jenis_putusan."</td>";  
					echo "<td>".$para_pihak."</td>";  
					echo "<td align=center><a title='Buat Putusan' href='perkara_detail?perkara_id=".$perkara_id."'>Buka Perkara </a></td>";
					echo "</tr>";
				}
				echo "</tbody></table>";
				echo "</div>";
			}	 
				 	
			 	
		//perkara diputus  KM
		//perkara diputus  HAKIM ANGGOTA
		$sql_putus_terakir="SELECT 
									convert_tanggal_indonesia(a.tanggal_putusan) as tanggal_putus 
									, b.`nomor_perkara`
									, b.`para_pihak`
									, a.`perkara_id`
									, b.`jenis_perkara_nama`   
									,d.`hakim_nama`
									,d.jabatan_hakim_id
									,d.`aktif`
									,d.`hakim_id`
									,f.nama as jenis_putusan
								FROM perkara_putusan AS a
								LEFT JOIN perkara AS b ON b.`perkara_id`=a.`perkara_id` 
								LEFT JOIN  perkara_hakim_pn AS d ON d.`perkara_id`=a.`perkara_id`
								LEFT JOIN  status_putusan AS f ON f.id=a.status_putusan_id
								 
								WHERE 
								a.tanggal_putusan >= '$mulai_wajib_upload_bas_put'
									AND 
									a.amar_putusan_dok IS NULL 
											AND 
									d.`hakim_id`=$jabatan_id  AND d.`jabatan_hakim_id`=2 AND d.`aktif`='Y'
							  ";
			//echo "---<br>".$sql_putus_terakir."<br>--------------------";
			$query_putus_terakhir=mysqli_query($koneksi,$sql_putus_terakir);
			$jumlah_putus_terakhir=$query_putus_terakhir ? mysqli_num_rows($query_putus_terakhir) : 0;
			if($jumlah_putus_terakhir>=1)
			{
				echo "<h3 align=center>Putusan yang belum dibuat / diupload (sebagai Hakim Anggota)</h3>
					<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
					<colgroup>
							<col width='6%'>
							<col width='20%'>
							<col width='15%'>
							<col width='15%'>  
							<col width='30%'>  
							<col width='14%'>
					</colgroup>
					<tbody>
					";
					echo "<tr>";
					echo "<td>No</td>";
					echo "<td>Nomor Perkara</td>";
					echo "<td>Jenis Perkara</td>"; 
					echo "<td>Tanggal <br>Status Putusan</td>";
					echo "<td>Para Pihak</td>";
					echo "<td>Operasi</td>";
					echo "</tr>";
			while($h_putus_terakhir=mysqli_fetch_assoc($query_putus_terakhir)) 
				{ 
						$no++;foreach($h_putus_terakhir as $key=>$value) {$$key=$value;}
						echo "<tr>";
						echo "<td align=center>".$no."</td>";
						echo "<td align=center>".$nomor_perkara."</td>";
						echo "<td align=center>".$jenis_perkara_nama."</td>";  
						echo "<td align=center>".$tanggal_putus."<br>".$jenis_putusan."</td>";  
						echo "<td>".$para_pihak."</td>";  
						echo "<td align=center><a title='Buat Putusan' href='perkara_detail?perkara_id=".$perkara_id."'>Buka Perkara </a></td>";
						echo "</tr>";
				}
				echo "</tbody></table>";
				echo "</div>";
					 
				 
			}	
		//perkara diputus  HAKIM ANGGOTA
		 
		//persidangan selanjutnya 
		
		$sql_sidang="SELECT  
						  nama_hari (a.tanggal_sidang) AS hari,
						  convert_tanggal_indonesia (a.tanggal_sidang) AS sidange,
						  a.tanggal_sidang AS tanggale,
						  COUNT(a.perkara_id) AS jumlah,
						  'Ketua Majelis' AS sebagai ,
						  (SELECT hakim_id FROM perkara_hakim_pn WHERE   perkara_id=a.`perkara_id` AND jabatan_hakim_id = 1 AND aktif = 'Y'  LIMIT 1) AS id_hakim
						FROM
						  perkara_jadwal_sidang AS a 
						  LEFT JOIN perkara AS b 
							ON b.perkara_id = a.perkara_id 
						  
						WHERE a.tanggal_sidang >='$sekarang' 
						AND (SELECT hakim_id FROM perkara_hakim_pn WHERE   perkara_id=a.`perkara_id` AND jabatan_hakim_id = 1 AND aktif = 'Y'  LIMIT 1)=$jabatan_id
						 GROUP BY a.`tanggal_sidang`

						UNION 
						
						SELECT  
						  nama_hari (a.tanggal_sidang) AS hari,
						  convert_tanggal_indonesia (a.tanggal_sidang) AS sidange,
						  a.tanggal_sidang AS tanggale,
						  COUNT(a.perkara_id) AS jumlah,
						  'Hakim Anggota' AS sebagai ,
						  (SELECT hakim_id FROM perkara_hakim_pn WHERE   perkara_id=a.`perkara_id` AND jabatan_hakim_id = 2 AND aktif = 'Y'  LIMIT 1) AS id_hakim
						FROM
						  perkara_jadwal_sidang AS a 
						  LEFT JOIN perkara AS b 
							ON b.perkara_id = a.perkara_id 
						  
						WHERE a.tanggal_sidang >='$sekarang' 
						AND (SELECT hakim_id FROM perkara_hakim_pn WHERE   perkara_id=a.`perkara_id` AND jabatan_hakim_id = 2 AND aktif = 'Y'  LIMIT 1)=$jabatan_id
						
						GROUP BY a.`tanggal_sidang`
						ORDER BY tanggale ASC, sebagai DESC
						 
							  ";
			// echo "---<br>".$sql_sidang."<br>--------------------";
			$query_sidang=mysqli_query($koneksi,$sql_sidang);
			$jumlah_sidang=$query_sidang ? mysqli_num_rows($query_sidang) : 0;
			if($jumlah_sidang>=1)
			{
				
				echo "<h3 align=center>Agenda Sidang</h3>
				<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='8%'>
						<col width='26%'>
						<col width='26%'>
						<col width='26%'>    
						<col width='14%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Tanggal</td>";
				echo "<td>Jumlah<br>Perkara </td>"; 
				echo "<td>Sebagai</td>"; 
				echo "<td>Detail</td>";
				echo "</tr>";
				$no=0;
				while($h_sidang=mysqli_fetch_assoc($query_sidang)) 
				{
					$no++; //foreach($h_sidang as $key=>$value) {$$key=$value;}
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=center>".$h_sidang["hari"].", ".$h_sidang["sidange"]."</td>";
					echo "<td align=center>".$h_sidang["jumlah"]." Perkara</td>";  
					echo "<td align=center>".$h_sidang["sebagai"]."</td>";   
					echo "<td align=center><a title='Detail Agenda' href='agenda_sidang?tanggal=".$h_sidang["tanggale"]."'>Detail </a></td>";
					echo "</tr>";
					
				}
				echo "</tbody></table>";
				echo "</div>";
			}
		//persidangan selanjutnya  
		
	}
		
	//hakim
	else
	if($group==30 OR $group==430 OR $group==500 OR $group==1000 OR $group==1010)	
	//pp
	{
		//30 430 500 1000 1010
		//persidangan sebelumnya / hari ini
		 
			$sql_data_sidang="SELECT 
									perkara_putusan.tanggal_putusan,
								  `perkara_jadwal_sidang`.`id`                   AS `id`, 
								  `perkara_jadwal_sidang`.`perkara_id`           AS `perkara_id`, 
								  `perkara_jadwal_sidang`.`urutan`               AS `urutan`,
								  `perkara_jadwal_sidang`.`tanggal_sidang`       AS `tanggal_sidang`,
								  convert_tanggal_indonesia(`perkara_jadwal_sidang`.`tanggal_sidang`)       AS sidange,
								  `perkara_jadwal_sidang`.`jam_sidang`           AS `jam_sidang`,
								  `perkara_jadwal_sidang`.`sidang_keliling`      AS `sidang_keliling`,
								  `perkara_jadwal_sidang`.`agenda`               AS `agenda`,
								  `perkara_jadwal_sidang`.`ruangan_id`           AS `ruangan_id`,
								  `perkara_jadwal_sidang`.`ruangan`              AS `ruangan`,
								  `perkara_jadwal_sidang`.`ditunda`              AS `ditunda`,
								  `perkara_jadwal_sidang`.`alasan_ditunda`       AS `alasan_ditunda`, 
								  `perkara_jadwal_sidang`.`keterangan`       AS `keterangan`, 
								  `perkara`.`nomor_perkara`                      AS `nomor_perkara`, 
								  `perkara`.`jenis_perkara_nama`                      AS `jenis_perkara_nama`, 
								  `perkara`.`para_pihak`                         AS `para_pihak`,  
								  `perkara_penetapan`.`panitera_pengganti_id`    AS `panitera_pengganti_id` ,
								  if(perkara_putusan.tanggal_putusan=`perkara_jadwal_sidang`.`tanggal_sidang`, concat('  Putus ', status_putusan.nama), `perkara_jadwal_sidang`.`alasan_ditunda` ) AS ket
								 
									
								FROM  `perkara_jadwal_sidang`
									 LEFT JOIN `perkara`
									   ON  `perkara_jadwal_sidang`.`perkara_id` = `perkara`.`perkara_id` 
									LEFT JOIN `perkara_penetapan`
									  ON `perkara_jadwal_sidang`.`perkara_id` = `perkara_penetapan`.`perkara_id` 
								   LEFT JOIN `perkara_putusan`
									 ON  `perkara_putusan`.`perkara_id` = `perkara`.`perkara_id`  
									LEFT JOIN status_putusan
										on status_putusan.id=perkara_putusan.status_putusan_id	
								WHERE perkara_jadwal_sidang.tanggal_sidang>='$mulai_wajib_upload_bas_put'
								AND perkara_jadwal_sidang.tanggal_sidang<='$sekarang'
								AND perkara_jadwal_sidang.edoc_bas IS NULL	
								AND `perkara_penetapan`.`panitera_pengganti_id` =$jabatan_id
								ORDER BY perkara_jadwal_sidang.tanggal_sidang ASC,  ket asc, nomor_perkara asc	
								";
		//	echo "---<br>".$sql_data_sidang."<br>--------------------";
			$query_data_sidang=mysqli_query($koneksi,$sql_data_sidang);
			$jumlah_sidang=$query_data_sidang ? mysqli_num_rows($query_data_sidang) : 0;
			if($jumlah_sidang>=1)
			{
				
				echo "<h3 align=center>BAS yang belum dibuat / diupload</h3>
				<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='5%'>
						<col width='15%'>
						<col width='25%'>   
						<col width='20%'>    
						<col width='20%'>    
						<col width='5%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Nomor Perkara<br>Jenis Perkara </td>"; 
				echo "<td>Para Pihak </td>"; 
				echo "<td>Tanggal<br>Sidang Ke<br>Agenda</td>"; ; 
				echo "<td>Keterangan</td>"; 
				echo "<td>Detail</td>";
				echo "</tr>";
				$no=0;
				while($h_data_sidang=mysqli_fetch_assoc($query_data_sidang)) 
				{$no++;
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=center>".$h_data_sidang["nomor_perkara"]." <br>".$h_data_sidang["jenis_perkara_nama"]."</td>";  
					echo "<td>".$h_data_sidang["para_pihak"]."</td>";   
					echo "<td align=center>".$h_data_sidang["sidange"]."<br>".$h_data_sidang["urutan"]."<br>".$h_data_sidang["agenda"]."</td>";   
					echo "<td align=center>".$h_data_sidang["ket"]."</td>";   
					echo "<td align=center><a title='Buat Bas' href='perkara_detail?perkara_id=".$h_data_sidang["perkara_id"]."'>Detail </a></td>";
					echo "</tr>";
					
				}
				echo "</tbody></table>";
				echo "</div>";
				 
			}
		 
		
		//persidangan sebelumnya / hari ini
		//persidangan selanjutnya 
		
		$sql_sidang="  
						SELECT  
						  nama_hari (a.tanggal_sidang) AS hari,
						  convert_tanggal_indonesia (a.tanggal_sidang) AS sidange,
						  a.tanggal_sidang AS tanggale,
						  COUNT(a.perkara_id) AS jumlah,
						  'Panitera/ Panitera Pengganti' AS sebagai  
						FROM
						  perkara_jadwal_sidang AS a 
						  LEFT JOIN perkara AS b 
							ON b.perkara_id = a.perkara_id 
						  LEFT JOIN perkara_penetapan AS c 
							ON c.perkara_id = a.perkara_id 
						  
						WHERE a.tanggal_sidang >='$sekarang' 
						AND c.panitera_pengganti_id=$jabatan_id
						
						GROUP BY a.`tanggal_sidang`
						ORDER BY tanggale ASC, sebagai DESC
						 
							  ";
			// echo "---<br>".$sql_sidang."<br>--------------------";
			$query_sidang=mysqli_query($koneksi,$sql_sidang);
			$jumlah_sidang=$query_sidang ? mysqli_num_rows($query_sidang) : 0;
			if($jumlah_sidang>=1)
			{
				
				echo "<h3 align=center>Agenda Sidang</h3>
				<div class='cssTable' id='tablePerkara'> <table id=tablePerkaraAll>
				<colgroup>
						<col width='8%'>
						<col width='26%'>
						<col width='26%'>
						<col width='26%'>    
						<col width='14%'>
				</colgroup>
				<tbody>
				";
				echo "<tr>";
				echo "<td>No</td>";
				echo "<td>Tanggal</td>";
				echo "<td>Jumlah<br>Perkara </td>"; 
				echo "<td>Sebagai</td>"; 
				echo "<td>Detail</td>";
				echo "</tr>";
				$no=0;
				while($h_sidang=mysqli_fetch_assoc($query_sidang)) 
				{
					$no++; //foreach($h_sidang as $key=>$value) {$$key=$value;}
					echo "<tr>";
					echo "<td align=center>".$no."</td>";
					echo "<td align=center>".$h_sidang["hari"].", ".$h_sidang["sidange"]."</td>";
					echo "<td align=center>".$h_sidang["jumlah"]." Perkara</td>";  
					echo "<td align=center>".$h_sidang["sebagai"]."</td>";   
					echo "<td align=center><a title='Detail Agenda' href='agenda_sidang?tanggal=".$h_sidang["tanggale"]."'>Detail </a></td>";
					echo "</tr>";
					
				}
				echo "</tbody></table>";
				echo "</div>";
			}
		//persidangan selanjutnya  
	}
	//pp
}

function tanya_jawab($perkara_id, $sidang_id, $variabel,$db_host2,$pihak_ke)
{
	$koneksi=isset($GLOBALS['koneksi']) ? $GLOBALS['koneksi'] : null;
	if (!($koneksi instanceof mysqli)) {
		error_log('Koneksi database tidak tersedia pada tanya jawab dokumen.');
		return;
	}
	//pilih template
	
	$sql="SELECT * from  $db_host2.template_keterangan_saksi_m WHERE pihak_id=$pihak_ke ";
	$query=mysqli_query($koneksi,$sql) ; 
	//echo "<br>".$sql."<br>";
	
	$no=0;
	echo "<select id='_pilihan_template' onchange='proses_pilih_template(this.value,".$variabel.")'>";
	echo "<option disabled selected> .: Pilih Template :. </option>";
	while($query && ($h=mysqli_fetch_assoc($query)))
	{
		$no++;foreach($h as $key=>$value) {$$key=$value;}
		echo "<option value='".$id."'>".$nama."</option>";
	}
	echo "</select>";
	//pilih template
	
	//data  
	echo "<div id='detail_".$variabel."'>";
	echo '<table id="tanyajawab_'.$variabel.'" class="w3-table w3-medium" border="1">
		<tbody>
		<tr style="color: white; background-color: dimgray;">
		<th width="5%">No.</th><th width="50%">Pertanyaan</th><th width="45%">Jawaban</th></tr>';

	//awal 
	//$sql="SELECT * from  $db_host2.template_keterangan_saksi_m ";
	//$query=mysqli_query($koneksi,$sql) ; 
	//echo "<br>".$sql."<br>";
	
	$no=0;
	$sql_data="SELECT * from  $db_host2.perkara_keterangan_saksi WHERE perkara_id=$perkara_id AND sidang_id=$sidang_id AND saksi_id=$variabel ORDER BY urutan_pertanyaan ASC ";
	$query_data=mysqli_query($koneksi,$sql_data) ;  
	while($query_data && ($h_data=mysqli_fetch_assoc($query_data)))
	{
		$no++;foreach($h_data as $key=>$value) {$$key=$value;}
?> 
	<tr>
		<td><div id="data_id<?php echo $id?>" ketid=""><?php echo $urutan_pertanyaan?></div></td> 
		<td style="width:350px"><div id="q_tanya<?php echo $id?>"  contenteditable="" class="tanya" onBlur="Edit_Detail(this.innerHTML,'pertanyaan',<?php echo $id?>, <?php echo $variabel?>)"><?php echo $pertanyaan?></div></td>
		<td style="width:350px"><div id="q_jawab<?php echo $id?>" contenteditable="" class="jawab" onBlur="Edit_Detail(this.innerHTML,'jawaban',<?php echo $id?>, <?php echo $variabel?>)" onkeydown="return myKeyPress(event,<?php echo $urutan_pertanyaan?>,this,<?php echo $id?>,<?php echo $perkara_id?>,<?php echo $sidang_id?>,<?php echo $variabel?>)"><?php echo $jawaban?></div></td> 
	</tr>
<?php 
	}
	if (!$query_data) {
		error_log('Gagal membaca tanya jawab dokumen: '.mysqli_error($koneksi));
	} elseif(mysqli_num_rows($query_data)==0)
	{
		 
		$urutan_pertanyaan=1;
		$pertanyaan=".";
		$jawaban=".";
		
		$sql="INSERT INTO ".$db_host2.".perkara_keterangan_saksi  (perkara_id, sidang_id, penanya_id, saksi_id, urutan_pertanyaan)  values($perkara_id, $sidang_id,1,$variabel, 1)";
		$simpan=mysqli_query($koneksi,$sql);
		$id=$simpan ? mysqli_insert_id($koneksi) : 0;
	?> 
	<tr>
		<td><div id="data_id<?php echo $id?>"   ketid=""><?php echo $urutan_pertanyaan?></div></td> 
		<td><div id="q_tanya<?php echo $id?>"   contenteditable=""  onBlur="Edit_Detail(this.innerHTML,'pertanyaan',<?php echo $id?>, <?php echo $variabel?>)"><?php echo $pertanyaan?></div></td>
		<td><div id="q_jawab<?php echo $id?>" contenteditable="" class="jawab" onBlur="Edit_Detail(this.innerHTML,'jawaban',<?php echo $id?>, <?php echo $variabel?>)" onkeydown="return myKeyPress(event,<?php echo $urutan_pertanyaan?>,this,<?php echo $id?>,<?php echo $perkara_id?>,<?php echo $sidang_id?>,<?php echo $variabel?>)"><?php echo $jawaban?></div></td> 
	</tr>
	<?php 	
	}
	//awal 
	echo '</tbody></table>';
	echo "<span style='color:red'>Tombol Enter: pada Kolom Jawaban Paling Akhir, untuk menambah Baris <br>
Tombol F2 : pada kolom jawaban, untuk menghapus Baris<br>
Tombol F3 : pada kolom jawaban, untuk Menyisipkan Baris<br>";
    if($variabel==5058)
    {?>
        <a href="#" onclick="copy_tanya_jawab(<?php echo $perkara_id?>, <?php echo $sidang_id?>, <?php echo $variabel?>,5059)">Duplikat Tanya Jawab ke Tanya Jawab Saksi 2</a><br></span>
    <?php 
    }else
    {?>
<a href="#" onclick="copy_tanya_jawab(<?php echo $perkara_id?>, <?php echo $sidang_id?>, <?php echo $variabel?>,5058)">Duplikat Tanya Jawab ke Tanya Jawab Saksi 1</a><br></span>
    <?php 
    }
	echo "</div>";
	
	
	//data 
	
}



function tampilkan_data_tabel($nama_tabel, $nama_field, $filter, $order_by, $limit, $db_host2)
{
	$koneksi=isset($GLOBALS['koneksi']) ? $GLOBALS['koneksi'] : null;
	if (!($koneksi instanceof mysqli)) {
		return '';
	}
	//jumlah field
	$cek_field=explode("#",$nama_field); 
	$jumlah= count($cek_field);
//	echo "<br>".$jumlah."<br>";
	//jumlah field	
	//header
	$tabel='<table class="table w3-table-all w3-hoverable w3-medium"><thead>';
	$tabel.='<tr>';
	 $tabel.="<th>No</th>";
	for ($x = 1; $x <= $jumlah-1; $x=$x+1) 
	{
		$tabel.="<th>".$cek_field[$x]."</th>";
	}
	$tabel.='</tr></thead><tbody>';
	//jumlah field
	
	//isi
	$sql="SELECT ".str_replace("#",",",$nama_field)." FROM ".$db_host2.".".$nama_tabel."  ".$filter. 
		" ".$order_by ." ".$limit; 
	$query=mysqli_query($koneksi,$sql)	; 
	//echo "<br>".$sql."<br>";
	
	$no=0;
	while($query && ($h=mysqli_fetch_row($query)))
	{
		$no++;
		//foreach($h as $key=>$value) {$$key=$value;}  
		$tabel.="<tr><td>".$no."</td>";
		for ($x = 1; $x <= $jumlah-1; $x=$x+1) 
		{
			$tabel.='<td  contenteditable=true onBlur=Edit(this.innerHTML,"'.$cek_field[$x].'",'.$h[0].')>'.$h[$x].'</td>';
		}
		$tabel.="</tr>";  
	}
	//isi
	  
	 $tabel.= "</tbody></table><br> ";
	   
	return $tabel;
}
function tampilkan_data_variabel($nama_tabel, $nama_field, $nama_judul, $filter, $order_by, $limit, $db_host2)
{
	$koneksi=isset($GLOBALS['koneksi']) ? $GLOBALS['koneksi'] : null;
	if (!($koneksi instanceof mysqli)) {
		return '';
	}
	//jumlah field
	$cek_field=explode("#",$nama_field); 
	$cek_judul=explode("#",$nama_judul); 
	$jumlah= count($cek_field);
//	echo "<br>".$jumlah."<br>";
	//jumlah field	
	//header
	$tabel='<table class="table w3-table-all w3-hoverable w3-medium"><thead>';
	$tabel.='<tr>';
	 $tabel.="<th>No</th>";
	for ($x = 1; $x <= $jumlah-1; $x=$x+1) 
	{
		$tabel.="<th>".$cek_judul[$x]."</th>";
	}
	$tabel.='</tr></thead><tbody>';
	//jumlah field
	
	//isi
	$sql="SELECT ".str_replace("#",",",$nama_field)." FROM ".$db_host2.".".$nama_tabel."  ".$filter. 
		" ".$order_by ." ".$limit; 
	$query=mysqli_query($koneksi,$sql)	; 
	//echo "<br>".$sql."<br>";
	
	$no=0;
	while($query && ($h=mysqli_fetch_row($query)))
	{
		$no++;
		//foreach($h as $key=>$value) {$$key=$value;}  
		$tabel.="<tr><td>".$no."</td>";
		for ($x = 1; $x <= $jumlah-1; $x=$x+1) 
		{
			if($cek_field[$x]=="var_sql_data")
			{
			$tabel.='<td>'.$h[$x].'</td>';
			}else
			{
				$tabel.='<td  contenteditable=true onBlur=Edit(this.innerHTML,"'.$cek_field[$x].'",'.$h[0].')>'.$h[$x].'</td>';
			}
		}
		$tabel.="</tr>";  
	}
	//isi
	  
	 $tabel.= "</tbody></table><br> ";
	   
	return $tabel;
}
function makeInt($angka) {
	if ($angka === null || $angka === '' || !is_numeric($angka)) {
		return 0;
	}
	$angka = (float) $angka;
	if ($angka < -0.0000001) {
		return ceil($angka-0.0000001);
	} else {
		return floor($angka+0.0000001);
	}
}

	function convertToHijriah($tanggal) {
		$array_bulan = array("Muharram", "Safar", "Rabiul Awwal", "Rabiul Akhir","Jumadil Awwal","Jumadil Akhir", "Rajab", "Sya ban","Ramadhan","Syawwal", "Zulqaidah", "Zulhijjah");
		$tanggal = trim((string) $tanggal);
		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tanggal, $bagian)) {
			return '';
		}
		$year = makeInt($bagian[1]);
		$month = makeInt($bagian[2]);
		$date = makeInt($bagian[3]);
		if (!checkdate($month, $date, $year)) {
			return '';
		}
		if (($year>1582)||(($year == "1582") && ($month > 10))||(($year == "1582") && ($month=="10")&&($date >14))) {
			$jd = makeInt((1461*($year+4800+makeInt(($month-14)/12)))/4)+
			makeInt((367*($month-2-12*(makeInt(($month-14)/12))))/12)-
			makeInt( (3*(makeInt(($year+4900+makeInt(($month-14)/12))/100))) /4)+
			$date-32075;
		} else {
			$jd = 367*$year-makeInt((7*($year+5001+makeInt(($month-9)/7)))/4)+
			makeInt((275*$month)/9)+$date+1729777;
		}

		$wd = $jd%7;
		$l = $jd-1948440+10632;
		$n=makeInt(($l-1)/10631);
		$l=$l-10631*$n+354;
		$z=(makeInt((10985-$l)/5316))*(makeInt((50*$l)/17719))+(makeInt($l/5670))*(makeInt((43*$l)/15238));
		$l=$l-(makeInt((30-$z)/15))*(makeInt((17719*$z)/50))-(makeInt($z/16))*(makeInt((15238*$z)/43))+29;
		$m=makeInt((24*$l)/709);
		$d=$l-makeInt((709*$m)/24);
		$y=30*$n+$z-30;
		$g = ($m%12)-1;
		if ($g==-1){
			$g=11;
		}
		$final = "$d $array_bulan[$g] $y ";
		return $final;
	}

function cleanHtmlTag($text){
		$text = strip_tags(trim($text));
		$text = str_replace('&#39;',"'",$text);
		$text = str_replace('&#39;',"'",$text);
		$text = str_replace('&#150;',"-",$text);
		$text = str_replace('&#146;',"'",$text);
		$text = str_replace('&#160;',' ',$text);
		$text = str_replace(';','; ',$text);
	    $text = str_replace('&nsbp',' ',$text);
		$text = str_replace('MENGADILI','',$text);
	    $text = str_replace('Mengadili','',$text);
	    $text = str_replace(':','',$text);
	    $text = str_replace("&nbsp; ","",$text);
	    $text = str_replace("</p>","",$text);	   	    
		$tmp = explode(';', $text);
		$tmptext = '';

		for ($i=0; $i < count($tmp); $i++){
			if($i!=0 AND strlen($tmp[$i]) >5){
				$tmptext .='\\par';
			}
			
			$tmp[$i]= preg_replace('/\t+/', '', $tmp[$i]);
			$tmptext .= $tmp[$i].';';
		}
		$text = $tmptext;		
		$text = trim($text);

		return $text;
	}

	function cleanHtmlTagSpecialnya($text){
		$text = str_replace(chr(194),"",$text);
		$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
		// $text = str_replace("MENETAPKAN:","MENETAPKAN:"."\\pard"."\\qc"."\\sa100",$text);
		$text = str_replace("MENETAPKAN:"," ",$text);
		$text = str_replace("MENETAPKAN :"," ",$text);
		$text = str_replace("M E N E T A P K A N :"," ",$text);
		$text = str_replace("M E N G A D I L I"," ",$text);
		$text = str_replace("MENUNTUT :"," ",$text);
		$text = str_replace("  "," ",$text);
		$text = str_replace("<ol>","",$text);		
		$text = str_replace("</li>","",$text);
		$text = str_replace("<li>","",$text);
		$text = str_replace("<li >","",$text);
		$text = str_replace("</ol>","",$text);
		// $text = str_replace("</p>","\\par",$text);
		$text = str_replace("<strong>","",$text);
		$text = str_replace("</strong>","",$text);
		$text = str_replace("<p>","",$text);		
		$text = str_replace("<p >","",$text);		
		$text = strip_tags(trim($text));
		$text = str_replace('&#39;',"'",$text);
		$text = str_replace('&#34;','"',$text);
		$text = str_replace('&#160;',' ',$text);
		$text = str_replace(';','; ',$text);
	    $text = str_replace('&nsbp',' ',$text);
		$text = str_replace('MENGADILI','',$text);
	    $text = str_replace('Mengadili','',$text);
	    $text = str_replace("&nbsp; ","",$text);
	    $text = str_replace("</p>","",$text);
	    $text = str_replace('”'," ",$text);		
	    $text = str_replace('“'," ",$text);
		$text = trim($text);

		return $text;
	}		
	function clean($text){
		$text = str_replace(chr(194),"",$text);
	    $text = str_replace(chr(13),"XXXXX",$text);
	    $text = str_replace("\n","XXXXX",$text);
	    $text = str_replace(chr(9),"ZZZZZ",$text);
	    $text = str_replace("\t","ZZZZZ",$text);
		$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
		// $text = str_replace("MENETAPKAN:","MENETAPKAN:"."\\pard"."\\qc"."\\sa100",$text);
		$text = str_replace("MENETAPKAN:"," ",$text);
		$text = str_replace("MENETAPKAN :"," ",$text);
		$text = str_replace("M E N E T A P K A N :"," ",$text);
		$text = str_replace("M E N G A D I L I"," ",$text);
		$text = str_replace("MENUNTUT :"," ",$text);
		$text = str_replace("  "," ",$text);
		$text = str_replace("<ol>","",$text);		
		$text = str_replace("</li>","",$text);
		$text = str_replace("<li>","",$text);
		$text = str_replace("<li >","",$text);
		$text = str_replace("</ol>","",$text);
		// $text = str_replace("</p>","\\par",$text);
		$text = str_replace("<strong>","",$text);
		$text = str_replace("</strong>","",$text);
		$text = str_replace("<p>","",$text);		
		$text = str_replace("<p >","",$text);		
		$text = strip_tags(trim($text));
		$text = str_replace('&#39;',"'",$text);
		$text = str_replace('&#34;','"',$text);
		$text = str_replace('&#160;',' ',$text);
		$text = str_replace(';','; ',$text);
	    $text = str_replace('&nsbp',' ',$text);
		$text = str_replace('MENGADILI','',$text);
	    $text = str_replace('Mengadili','',$text);
	    $text = str_replace("&nbsp; ","",$text);
	    $text = str_replace("</p>","",$text);
	    $text = str_replace('”'," ",$text);		
	    $text = str_replace('“'," ",$text);
		$text = trim($text);

		return $text;
	}		
	function cleanHtmlTagSpecial($text){
		$text = str_replace(chr(194),"",$text);
		$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
		// $text = str_replace("MENETAPKAN:","MENETAPKAN:"."\\pard"."\\qc"."\\sa100",$text);
		$text = str_replace("MENETAPKAN:"," ",$text);
		$text = str_replace("MENETAPKAN :"," ",$text);
		$text = str_replace("M E N E T A P K A N :"," ",$text);
		$text = str_replace("M E N G A D I L I"," ",$text);
		$text = str_replace("MENUNTUT :"," ",$text);
		$text = str_replace("  "," ",$text);
		$text = str_replace("<ol>","<ol type='1'> ",$text);		
		$text = str_replace("</li>","</li> \\par ",$text);
		$text = str_replace("<li>","<li> \\tab ",$text);
		$text = str_replace("<li >","<li> \\tab ",$text);
		$text = str_replace("</ol>","</ol> ",$text);
		// $text = str_replace("</p>","\\par",$text);
		$text = str_replace("<strong>","\b",$text);
		$text = str_replace("</strong>","\b0",$text);
		$text = str_replace("<p>1.","\\pard"."\\qj"."\\li630"."\\linestarts 1.",$text);		
		$text = strip_tags(trim($text));
		$text = str_replace('&#39;',"'",$text);
		$text = str_replace('&#34;','"',$text);
		$text = str_replace('&#160;',' ',$text);
		$text = str_replace(';','; ',$text);
	    $text = str_replace('&nsbp',' ',$text);
		$text = str_replace('MENGADILI','',$text);
	    $text = str_replace('Mengadili','',$text);
	    $text = str_replace("&nbsp; ","",$text);
	    $text = str_replace("</p>","",$text);
	    $text = str_replace('”'," ",$text);		
	    $text = str_replace('“'," ",$text);
		$text = trim($text);

		return $text;
	}		 
function normalisasi_penanda_variabel_dokumen($isi_dokumen)
{
	$isi_dokumen=(string) $isi_dokumen;
	$hasil=preg_replace_callback('/#([^#]{0,1200})#/s',function($match){
		$teks=$match[1];
		$teks=preg_replace('/\\\\\'[0-9a-fA-F]{2}/','',$teks);
		$teks=preg_replace('/\\\\[a-zA-Z]+-?\\d* ?/','',$teks);
		$teks=str_replace(array('{','}','\\',"\r","\n","\t",' '),'',$teks);
		return preg_match('/^[0-9]+$/',$teks) ? '#'.$teks.'#' : $match[0];
	},$isi_dokumen);
	return $hasil === null ? $isi_dokumen : $hasil;
}

function variabel_dokumen($isi_dokumen)
{
	$matches=array();
	$isi_dokumen=normalisasi_penanda_variabel_dokumen($isi_dokumen);
	preg_match_all('/#([0-9]+)#/',$isi_dokumen,$matches);
	$variabel_unik=array_unique($matches[1]);
	return array_values($variabel_unik);
}
function  getBulanFull($bln){
	    switch  ($bln){
	        case 1: return  "Januari"; break;
	        case 2: return  "Februari"; break;
	        case 3: return  "Maret"; break;
	        case 4: return  "April"; break;
	        case 5: return  "Mei"; break;
	        case 6: return  "Juni"; break;
	        case 7: return  "Juli"; break;
	        case 8: return  "Agustus"; break;
	        case 9: return  "September"; break;
	        case 10: return "Oktober"; break;
	        case 11: return "November"; break;
	        case 12: return "Desember"; break;
	    }
	}
    function validateDate($date){
	    if (empty($date)) return false;
	    $date = str_replace('/', '-', $date);
		$d = DateTime::createFromFormat('Y-m-d', $date);
	    return $d && $d->format('Y-m-d') == $date;
	}
    function convertKeTglIndo($tgl){
    	# contoh: 21 April 2014
	    if (!validateDate($tgl)) return $tgl; 
	    $tanggal_ = substr($tgl,8,2);
	    if($tanggal_>=10){
	    	$tanggal = $tanggal_;
	    }elseif($tanggal_<10){
	    	$tanggal = substr($tgl,9,2);
	    }
	    $bulan_ =  getBulanFull(substr($tgl,5,2));
	    $tahun_ =  substr($tgl,0,4);
	    return  $tanggal.' '.$bulan_.' '.$tahun_;

	}
	if ( ! function_exists('format_uang'))
	{ 
		function format_uang($nilai)
		{
			if((int)$nilai==0)
			{
				$nilai=0;
			}else
			{
				$nilai=number_format($nilai, 0, ',', '.');
			}
			return $nilai.",00";
		}  
	} 
	if ( ! function_exists('huruf_besar_awal_kata'))
	{ 
		function huruf_besar_awal_kata($teks)
		{
			 
			$teks=ucwords(strtolower($teks));
			 
			return $teks;
		}  
	} 	
	

	if ( ! function_exists('terbilang_rupiah'))
	{ 
		function terbilang_rupiah($bilangan)
		{
			  $bilangan=str_replace(".00", "", $bilangan);	
			   $angka = array('0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0',
        '0', '0', '0');
			    $kata = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh',
			        'delapan', 'sembilan');
			    $tingkat = array('', 'ribu', 'juta', 'milyar', 'triliun');

			    $panjang_bilangan = strlen($bilangan);

			    /* pengujian panjang bilangan */
			    if ($panjang_bilangan > 15)
			    {
			        $kalimat = "Diluar Batas";
			        return $kalimat;
			    }

			    /* mengambil angka-angka yang ada dalam bilangan,
			    dimasukkan ke dalam array */
			    for ($i = 1; $i <= $panjang_bilangan; $i++)
			    {
			        $angka[$i] = substr($bilangan, -($i), 1);
			    }

			    $i = 1;
			    $j = 0;
			    $kalimat = "";


			    /* mulai proses iterasi terhadap array angka */
			    while ($i <= $panjang_bilangan)
			    {
			        $subkalimat = "";
			        $kata1 = "";
			        $kata2 = "";
			        $kata3 = "";

			        /* untuk ratusan */
			        if ($angka[$i + 2] != "0")
			        {
			            if ($angka[$i + 2] == "1")
			            {
			                $kata1 = "seratus";
			            }
			            else
			            {
			                $kata1 = $kata[$angka[$i + 2]] . " ratus";
			            }
			        }

			        /* untuk puluhan atau belasan */
			        if ($angka[$i + 1] != "0")
			        {
			            if ($angka[$i + 1] == "1")
			            {
			                if ($angka[$i] == "0")
			                {
			                    $kata2 = "sepuluh";
			                }
			                elseif ($angka[$i] == "1")
			                {
			                    $kata2 = "sebelas";
			                }
			                else
			                {
			                    $kata2 = $kata[$angka[$i]] . " belas";
			                }
			            }
			            else
			            {
			                $kata2 = $kata[$angka[$i + 1]] . " puluh";
			            }
			        }

			        /* untuk satuan */
			        if ($angka[$i] != "0")
			        {
			            if ($angka[$i + 1] != "1")
			            {
			                $kata3 = $kata[$angka[$i]];
			            }
			        }

			        /* pengujian angka apakah tidak nol semua,
			        lalu ditambahkan tingkat */
			        if (($angka[$i] != "0") or ($angka[$i + 1] != "0") or ($angka[$i + 2] != "0"))
			        {
			            $subkalimat = "$kata1 $kata2 $kata3 " . $tingkat[$j] . " ";
			        }

			        /* gabungkan variabe sub kalimat (untuk satu blok 3 angka)
			        ke variabel kalimat */
			        $kalimat = $subkalimat . $kalimat;
			        $i = $i + 3;
			        $j = $j + 1;

			    }

			    /* mengganti satu ribu jadi seribu jika diperlukan */
			    if (($angka[5] == "0") and ($angka[6] == "0"))
			    {
			        $kalimat = str_replace("satu ribu", "seribu", $kalimat);
			    }

			    return str_replace("  "," ",str_replace("  "," ",trim($kalimat . " rupiah")));
			}
		
	} 	

	if ( ! function_exists('desa_kecamatan_kabupaten'))
	{ 
		function desa_kecamatan_kabupaten($teks)
		{
			$kecamatan=ambil_kabupaten($teks);
			$kabupaten=ambil_kabupaten($teks);
			$hasil_teks=$kecamatan." ".$kabupaten;	
			$hasil_teks=ucwords(strtolower($hasil_teks));
			 
			return $hasil_teks;
		}  
	} 

function kasuari_sql_split_arguments($arguments)
{
	$parts=array();
	$start=0;
	$depth=0;
	$quote='';
	$length=strlen($arguments);
	for ($i=0; $i<$length; $i++) {
		$char=$arguments[$i];
		if ($quote !== '') {
			if ($char === $quote && ($i === 0 || $arguments[$i-1] !== '\\')) {
				$quote='';
			}
			continue;
		}
		if ($char === "'" || $char === '"' || $char === '`') {
			$quote=$char;
		} elseif ($char === '(') {
			$depth++;
		} elseif ($char === ')') {
			$depth--;
		} elseif ($char === ',' && $depth === 0) {
			$parts[]=trim(substr($arguments,$start,$i-$start));
			$start=$i+1;
		}
	}
	$parts[]=trim(substr($arguments,$start));
	return $parts;
}

function kasuari_sql_replace_function($sql, $functionName, $builder)
{
	$offset=0;
	$pattern='/\\b'.preg_quote($functionName,'/').'\\s*\\(/i';
	while (preg_match($pattern,$sql,$match,PREG_OFFSET_CAPTURE,$offset)) {
		$start=$match[0][1];
		$open=$start+strlen($match[0][0])-1;
		$depth=1;
		$quote='';
		$close=-1;
		$length=strlen($sql);
		for ($i=$open+1; $i<$length; $i++) {
			$char=$sql[$i];
			if ($quote !== '') {
				if ($char === $quote && $sql[$i-1] !== '\\') {
					$quote='';
				}
				continue;
			}
			if ($char === "'" || $char === '"' || $char === '`') {
				$quote=$char;
			} elseif ($char === '(') {
				$depth++;
			} elseif ($char === ')') {
				$depth--;
				if ($depth === 0) {
					$close=$i;
					break;
				}
			}
		}
		if ($close < 0) {
			break;
		}
		$arguments=substr($sql,$open+1,$close-$open-1);
		$replacement=call_user_func($builder,kasuari_sql_split_arguments($arguments));
		if (!is_string($replacement) || $replacement === '') {
			$offset=$close+1;
			continue;
		}
		$sql=substr($sql,0,$start).$replacement.substr($sql,$close+1);
		$offset=$start+strlen($replacement);
	}
	return $sql;
}

function kasuari_normalisasi_sql_variabel($sql)
{
	$bulan="'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'";
	$romawi="'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'";
	$hari="'Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'";

	$sql=kasuari_sql_replace_function($sql,'get_umur',function($args){
		return count($args) === 2 ? 'TIMESTAMPDIFF(YEAR,('.$args[0].'),('.$args[1].'))' : '';
	});
	$sql=kasuari_sql_replace_function($sql,'bulan_romawi',function($args) use ($romawi){
		return count($args) === 1 ? 'ELT(CAST(('.$args[0].') AS UNSIGNED),'.$romawi.')' : '';
	});
	$sql=kasuari_sql_replace_function($sql,'nama_hari',function($args) use ($hari){
		return count($args) === 1 ? 'ELT(DAYOFWEEK('.$args[0].'),'.$hari.')' : '';
	});
	$sql=kasuari_sql_replace_function($sql,'convert_tanggal_indonesia',function($args) use ($bulan){
		if (count($args) !== 1) return '';
		$arg=$args[0];
		return "CASE WHEN ($arg) IS NULL THEN '' ELSE CONCAT(DAY($arg),' ',ELT(MONTH($arg),$bulan),' ',YEAR($arg)) END";
	});
	return $sql;
}

function isi_variabel($variabelnya, $perkara_id, $pn_id, $var_model, $var_sumber_sipp, $var_sql_data, $var_tabel, $var_field, $var_cek_sidang, $var_fungsi_nama,$sebutan_pihak1,$sebutan_pihak2,$gugatan_permohonan,$var_keterangan,$koneksi){	
	$isi="";
	$sql_isi="";
	if($var_model=='sql'){
			$sql_isi=$var_sql_data;
		//	echo "<br>".$sql_isi ."<br>";
			$sql_isi=str_replace('#perkara_id#',$perkara_id,$sql_isi); 
			$sql_isi=str_replace('#pn_id#',$pn_id,$sql_isi); 
			$sql_isi=kasuari_normalisasi_sql_variabel($sql_isi);
		//	$sql_isi=str_replace()
	}else
	if(!empty($var_tabel) AND $var_tabel=="data_teks" ){
			$tabel=$var_tabel;
			$sql_isi="SELECT ".$var_field." AS DATA from $tabel WHERE perkara_id=$perkara_id AND pa_id=$pn_id AND var_nomor='$variabelnya'"; 
	}else
	if(!empty($var_tabel) AND $var_tabel<>"data_teks" ){
			$tabel=$var_tabel;
			$sql_isi="SELECT ".$var_field." AS DATA from $tabel WHERE perkara_id=$perkara_id AND pn_id=$pn_id"; 
	}
	else{
			$isi=""; 
	}

	// echo "<br>".$variabelnya."----" .$sql_isi. "----<br>" ;
	if ($sql_isi === "" || !($koneksi instanceof mysqli)) {
		return "";
	}

	$query_isi=mysqli_query($koneksi,$sql_isi);
	if ($query_isi === false) {
		error_log("Kasuari gagal mengisi variabel #".$variabelnya."#: ".mysqli_error($koneksi));
		return "";
	}
	 
	while($h_info_isi=mysqli_fetch_assoc($query_isi)){
	//isi
		$isi=isset($h_info_isi['DATA']) && $h_info_isi['DATA'] !== null ? (string) $h_info_isi['DATA'] : "";
		$isi=str_replace("<<"," ",$isi);
		$isi=clean($isi);
		$isi = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $isi); 
		$isi=str_replace("XXXXXXXXXX","XXXXX",$isi);
		$isi=str_replace("XXXXXXXXXX","XXXXX",$isi);
		$isi=str_replace("ZZZZZZZZZZ","ZZZZZ",$isi);
		$isi=str_replace("ZZZZZZZZZZ","ZZZZZ",$isi);
		$isi=str_replace("ZZZZZ",chr(9),$isi);
		$isi=str_replace("XXXXX",chr(13),$isi);
		$isi=str_replace(" ,",",",$isi);
		$isi=str_replace(" ;",";",$isi);
		$isi=str_replace("; ",";",$isi);
		$isi=str_replace("( ","(",$isi);
		$isi=str_replace(" )",")",$isi);
		//$isi = preg_replace('/[\000-\031\200-\377]/', '', $isi);
		//$isi = preg_replace('/[[:^print:]]/', "", $isi);

		if($var_fungsi_nama=='hijriah'){ 
			$isi=trim(convertToHijriah($isi));
		}
		if($var_fungsi_nama=='tanggal_indonesia'){ 
			$isi=trim(convertKeTglIndo($isi));
		}
		if($var_fungsi_nama=='format_uang'){ 
			$isi=trim(format_uang($isi));
		}
		if($var_fungsi_nama=='huruf_besar_awal_kata'){ 
			$isi=trim(huruf_besar_awal_kata($isi));
		}

		if($var_fungsi_nama=='terbilang_rupiah'){ 
			$isi=trim(terbilang_rupiah($isi));
		}
		
	//isi 	
	}
	return $isi;
}

function ambil_kecamatan($alamat) {


		 $A = explode(' ',trim($alamat));
	     
	     for ($i=0; $i<=count($A)-1 ; $i++) { 
	      	
	      	 if (strtoupper($A[$i]) == 'KECAMATAN')  {
	      	 	break;
	         }
		       
		  } 

	   
	      for ($i=$i; $i<=count($A)-1 ; $i++) { 
	   	
	        if ((strtoupper($A[$i]) =='KABUPATEN') || (strtoupper($A[$i])== 'KOTA') || (strtoupper($A[$i])== 'KAB') || (strtoupper($A[$i]) == 'KAB.')) {
	      	 	break;
	         }
	     	  else {
		        
		         if (strtoupper($A[$i])<>'KECAMATAN') {
		     
		     // $kecamatan="kecamatan".$i." ".$A[$i];
		       
		       return  "kecamatan ".$A[$i];

		       }
	        }

		 }

	}

    
    function ambil_kabupaten($alamat) {
      
        $A = explode(' ',trim($alamat));

       for ($i=0; $i<=count($A)-1 ; $i++) { 
      	
      	 if (strtoupper($A[$i]) == 'KECAMATAN')  {
      	 	break;
         }
	       
	  } 

   
      for ($i=$i; $i<=count($A)-1 ; $i++) { 
      	
        if ((strtoupper($A[$i]) =='KABUPATEN') || (strtoupper($A[$i])== 'KOTA') || (strtoupper($A[$i])== 'KAB') || (strtoupper($A[$i]) == 'KAB.')) {
      	 	break;
         }
     	  else {
	        
	         if (strtoupper($A[$i])<>'KECAMATAN') {
	         	//echo "Kecamatan".$i." ".$A[$i]."<br>";
	         echo '';	
	       }
        }

	 }


	  for ($i=$i; $i<=count($A)-1 ; $i++) { 
      	
     
       if ((strtoupper($A[$i]) == 'KABUPATEN') || (strtoupper($A[$i]) == 'KOTA') || (strtoupper($A[$i]) == 'KAB') || (strtoupper($A[$i]) == 'KAB.')) {
         echo '';
       }
          else {

             	if ((strtoupper($A[$i]) <> 'KABUPATEN') || (strtoupper($A[$i]) <> 'KOTA') || (strtoupper($A[$i]) <> 'KAB') || (strtoupper($A[$i]) <> 'KAB.')) {
      	 	   
	      	 	     if ((strtoupper($A[$i-1]) == 'KABUPATEN') || (strtoupper($A[$i-1]) == 'KAB')) {

	                    $kabupaten="Kabupaten ";
	      	 	     
	      	 	     } else if (strtoupper($A[$i-1]) == 'KOTA') {

	                    $kabupaten="Kota ";

	      	 	     } 

      	 	         return $kabupaten." ".$A[$i];
              
               }


          }

	 }




    }
?>
