<?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>PA Pengaju<br>Nomor Perkara<br>Permohonan Banding</th>
<th>Tanggal Pengiriman Berkas</th>
<th>Nomor Perkara Banding</th>
<th>Tanggal Putusan Banding</th>
<th>Tanggal Penerimaan Kembali Berkas</th>
<th>Tanggal Pemberitahuan Putusan</th>
<th>Status Banding</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT perkara_banding.*, pengadilan_agama.nama	AS pengaju FROM perkara_banding
LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id ORDER BY perkara_banding.permohonan_banding DESC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
	$no++;
	$table.='<tr><td class="w3-center">'.$no.'</td>
	<td class="w3-left-align">'.str_replace("PENGADILAN AGAMA", "PA", $data["pengaju"]).'<br>'.$data["nomor_perkara_pn"].'<br>'.$data["permohonan_banding"].'</td>
	<td class="w3-left-align">'.$data["pengiriman_berkas_banding"].'</td>
	<td class="w3-left-align">'.$data["nomor_perkara_banding"].'</td>
	<td class="w3-left-align">'.$data["putusan_banding"].'</td>
	<td class="w3-left-align">'.$data["penerimaan_kembali_berkas_banding"].'</td>
	<td class="w3-left-align">'.$data["pemberitahuan_putusan_banding"].'</td>
	<td class="w3-left-align">'.$data["status_banding_text"].'</td>
	<td class="w3-left-align"><a href="perkara_detil_banding&id='.$data["id"].'" title="Detail Perkara">Link</a></td>
	</tr>';
}
if($no==0){
	$table.='<tr><td class="w3-center w3-text-red" colspan="11">Tidak ada Data</td></tr>';
}
$table.="</tbody></table></div>";
echo "$table";
mysqli_close($koneksi);
?>