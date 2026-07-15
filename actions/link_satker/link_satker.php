<?php
$table='<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>Satker</th>
<th>Link</th>
</tr></thead><tbody>';
$sql = "SELECT * FROM pengadilan_agama ORDER BY nama ASC";
$query=mysqli_query($koneksi,$sql);
$no=0;
while($data=mysqli_fetch_assoc($query)){
	$no++;
	$table.='<tr><td class="w3-center" style="width:80px">'.$no.'</td>
	<td class="w3-left-align" style="width:280px">'.$data["nama"].'</td>
	<td class="w3-left-align"><input class="w3-input w3-border" value="'.$data["ip_satker"].'"  onchange="edit_tabel('."'pengadilan_agama',"."'ip_satker', 'id', ".$data["id"].', this.value)"></td>
	</tr>';
}
$table.="</tbody></table></div>";
echo "$table";
mysqli_close($koneksi);
?>