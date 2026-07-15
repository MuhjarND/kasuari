<?php
$sql = "SELECT * FROM jenis_blangko WHERE jenis_blangko_id=$id";
$query=mysqli_query($koneksi,$sql);
while($data=mysqli_fetch_assoc($query)){
  foreach($data as $key=>$value) {$$key=$value;}
}
?><div class="w3-container" id="div_modal_detail"><span class="w3-right"><button class="w3-btn w3-round w3-red w3-small" onclick="tutup_modal()">X</button>
</span><form class="w3-row" id="f_jenis_blangko" name="f_jenis_blangko"> 
	<input name="aksi" id="aksi" type="hidden" value="<?php echo base64_encode("jenis_blangko_simpan_edit")?>">
	<input name="jenis_blangko_id" id="jenis_blangko_id" type="hidden" value="<?php echo  $jenis_blangko_id?>">
	<p><label>Jenis Blangko</label><input class="w3-input w3-border" name="jenis_blangko_nama" id="jenis_blangko_nama" value="<?php echo  $jenis_blangko_nama?>"></p>
	
	<p class="w3-hide">Sub Dari<br>
		<select class="w3-select w3-border w3-white" id="jenis_blangko_parent_id" name="jenis_blangko_parent_id">
			<option value="0" selected="selected"></option>
			<?php
				$sql = "SELECT * FROM jenis_blangko WHERE jenis_blangko_id<>$jenis_blangko_id						ORDER BY urutan ASC";
				$query=mysqli_query($koneksi,$sql);
				while($data=mysqli_fetch_assoc($query)){
					if($jenis_blangko_parent_id==$data["jenis_blangko_parent_id"]){
						$pil=" selected='selected' " ;
					}else{
						$pil='  ';
					}
					//echo "<option $pil value='".$data["jenis_blangko_id"]."'>".$data["jenis_blangko_nama"]."</option>";
				}
				?>
		</select>
	</p> 
	<p><label>Urutan</label>
		<input type="number" name="urutan" id="urutan" class="w3-select w3-border w3-white" value="<?php echo $urutan?>">
	</p> 
	<p>Aktif<br>
		<select name="jenis_blangko_status" id="jenis_blangko_status" class="w3-select w3-border w3-white">
			<?php
				$sql = "SELECT * FROM m_aktif";
				$query=mysqli_query($koneksi,$sql);
				while($data=mysqli_fetch_assoc($query)){
					if($jenis_blangko_status==$data["kode"]){
					$pil=" selected='selected' " ;
				}else{
					$pil='  ';
				}
					echo "<option $pil value='".$data["kode"]."'>".$data["aktif"]."</option>";
				}
				?>
		</select>
	</p>
	<p>
					<a onclick="kirim_post('api')" class="w3-button w3-green">Simpan</a>
				
	</p>
</form></div>
<?php mysqli_close($koneksi);?>