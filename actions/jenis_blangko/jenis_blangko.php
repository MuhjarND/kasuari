<?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>Jenis Blangko</th>
<th>Urutan</th>
<th>Status</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT jenis_blangko.* , z.jenis_blangko_nama AS sub_dari
		FROM jenis_blangko 
		LEFT JOIN (SELECT jenis_blangko_id, jenis_blangko_nama FROM jenis_blangko) AS z on z.jenis_blangko_id=jenis_blangko.jenis_blangko_parent_id
		ORDER BY jenis_blangko.urutan ASC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
	$no++;
	$table.='<tr><td class="w3-center">'.$no.'</td>
	<td class="w3-left-align">'.$data["jenis_blangko_nama"].'</td>
	<td class="w3-left-align">'.$data["urutan"].'</td>
	<td class="w3-left-align">'.$data["jenis_blangko_status"].'</td>
	<td class="w3-left-align" id="kolom_ke'.$no.'"><a class="w3-btn w3-round w3-border w3-green w3-small" href="#kolom_ke'.$no.'" onclick="edit_jenis_blangko('."'".base64_encode($data["jenis_blangko_id"])."'".')" title="Edit">Edit</a></td>
	</tr>';
}
if($no==0){
	$table.='<tr><td class="w3-center w3-text-red" colspan="11">Tidak ada Data</td></tr>';
}
$table.="</tbody></table></div>";
echo "$table";
mysqli_close($koneksi);
?>