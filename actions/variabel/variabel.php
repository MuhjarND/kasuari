<?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>Nomor Variabel</th>
<th>Nama Variabel</th>
<th>Model</th>
<th>Jenis</th>
<th>Tabel</th>
<th>Field</th>
<th>Fungsi</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT * FROM master_variabel ORDER BY var_nomor ASC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
	$no++;
	$table.='<tr><td class="w3-center">'.$no.'</td>
	<td class="w3-left-align">'.$data["var_nomor"].'</td>
	<td class="w3-left-align">'.$data["var_keterangan"].'</td>
	<td class="w3-left-align">'.$data["var_model"].'</td>
	<td class="w3-left-align">'.$data["var_jenis"].'</td>
	<td class="w3-left-align">'.$data["var_tabel"].'</td>
	<td class="w3-left-align">'.$data["var_field"].'</td>
	<td class="w3-left-align">'.$data["var_fungsi_nama"].'</td>
	<td class="w3-left-align" id="kolom_ke'.$no.'"><a class="w3-btn w3-round w3-border w3-green w3-small" href="#kolom_ke'.$no.'" onclick="edit_variabel('."'".base64_encode($data["var_id"])."'".')" title="Edit">Edit</a></td>
	</tr>';
}
if($no==0){
	$table.='<tr><td class="w3-center w3-text-red" colspan="11">Tidak ada Data</td></tr>';
}
$table.="</tbody></table></div>";
echo "$table";
mysqli_close($koneksi);
?>