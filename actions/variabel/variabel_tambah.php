<div class="w3-container" id="div_modal_detail"><span class="w3-right"><button class="w3-btn w3-round w3-red w3-small" onclick="tutup_modal()">X</button>
</span><form class="w3-row" id="f_variabel" name="f_variabel"> 
	<input name="aksi" id="aksi" type="hidden" value="<?php echo base64_encode("variabel_simpan")?>">
	<input name="var_id" id="var_id" type="hidden" value="">
	<p><label>Nomor</label><input class="w3-input w3-border" name="var_nomor" id="var_nomor" value=""></p>
	<p><label>Nama</label><input class="w3-input w3-border" name="var_keterangan" id="var_keterangan" value=""></p>
	<p>Bentuk Inputan<br>
		<select class="w3-select w3-border w3-white" id="var_jenis" name="var_jenis">
			<option value="" selected=""></option>
			<option value="text">text</option><option value="textarea">textarea</option>		</select>
	</p> 
	<p>Model<br>
		<span id="model">
			<select class="w3-select w3-border w3-white" name="var_model" id="var_model" onchange="pilih_model_variabel(this.value)">
				<option value="" selected=""></option>
				<option value="text">Data dari Tabel</option><option value="tanya_jawab">Tanya Jawab</option><option value="sql">SQL</option><option value="pilihan">Pilihan</option>			</select>
		</span> 
	</p> 
	<p><label>Tabel</label>
		<select name="var_tabel" id="var_tabel" class="w3-select w3-border w3-white">
			<option value=""></option>
			<option selected="selected" value=""></option>
			<option value="data_teks">data_teks</option>
			<option value="perkara_banding">perkara_banding</option>
		</select>

	</p> 
	<p>Field<br>
		<select name="var_field" id="var_field" class="w3-select w3-border w3-white">
			<option value=""></option>
			<option selected="selected" value=""></option>

			<option value="DATA">DATA</option>
			<option value="PERKARA_BANDING" disabled="">PERKARA_BANDING</option>
			<option value="jenis_banding">jenis_banding</option>
<option value="alur_perkara_id">alur_perkara_id</option>
<option value="nomor_perkara_pn">nomor_perkara_pn</option>
<option value="putusan_pn">putusan_pn</option>
<option value="pihak_pembanding">pihak_pembanding</option>
<option value="permohonan_banding">permohonan_banding</option>
<option value="pemohon_banding">pemohon_banding</option>
<option value="para_pihak">para_pihak</option>
<option value="pemberitahuan_putusan_pn">pemberitahuan_putusan_pn</option>
<option value="pemberitahuan_permohonan_banding">pemberitahuan_permohonan_banding</option>
<option value="penerimaan_memori_banding">penerimaan_memori_banding</option>
<option value="penyerahan_memori_banding">penyerahan_memori_banding</option>
<option value="penerimaan_kontra_banding">penerimaan_kontra_banding</option>
<option value="penyerahan_kontra_banding">penyerahan_kontra_banding</option>
<option value="pemberitahuan_inzage">pemberitahuan_inzage</option>
<option value="pemberitahuan_inzage_pembanding">pemberitahuan_inzage_pembanding</option>
<option value="pemberitahuan_inzage_terbanding">pemberitahuan_inzage_terbanding</option>
<option value="pelaksanaan_inzage">pelaksanaan_inzage</option>
<option value="pelaksanaan_inzage_pembanding">pelaksanaan_inzage_pembanding</option>
<option value="pelaksanaan_inzage_terbanding">pelaksanaan_inzage_terbanding</option>
<option value="pengiriman_berkas_banding">pengiriman_berkas_banding</option>
<option value="nomor_surat_pengiriman_berkas_banding">nomor_surat_pengiriman_berkas_banding</option>
<option value="penerimaan_kembali_berkas_banding">penerimaan_kembali_berkas_banding</option>
<option value="nomor_urut_register">nomor_urut_register</option>
<option value="tanggal_pendaftaran_banding">tanggal_pendaftaran_banding</option>
<option value="nomor_perkara_banding">nomor_perkara_banding</option>
<option value="panitera_pembuat_akta_banding">panitera_pembuat_akta_banding</option>
<option value="hakim1_banding_id">hakim1_banding_id</option>
<option value="hakim1_banding">hakim1_banding</option>
<option value="hakim2_banding_id">hakim2_banding_id</option>
<option value="hakim2_banding">hakim2_banding</option>
<option value="hakim3_banding_id">hakim3_banding_id</option>
<option value="hakim3_banding">hakim3_banding</option>
<option value="hakim4_banding_id">hakim4_banding_id</option>
<option value="hakim4_banding">hakim4_banding</option>
<option value="hakim5_banding_id">hakim5_banding_id</option>
<option value="hakim5_banding">hakim5_banding</option>
<option value="majelis_hakim_banding">majelis_hakim_banding</option>
<option value="panitera_pengganti_banding_id">panitera_pengganti_banding_id</option>
<option value="panitera_pengganti_banding">panitera_pengganti_banding</option>
<option value="tanggal_penetapan_sidang_pertama">tanggal_penetapan_sidang_pertama</option>
<option value="tanggal_sidang_pertama">tanggal_sidang_pertama</option>
<option value="putusan_banding">putusan_banding</option>
<option value="sumber_hukum_id">sumber_hukum_id</option>
<option value="status_putusan_banding_id">status_putusan_banding_id</option>
<option value="status_putusan_banding_text">status_putusan_banding_text</option>
<option value="nomor_putusan_banding">nomor_putusan_banding</option>
<option value="amar_putusan_banding">amar_putusan_banding</option>
<option value="amar_putusan_banding_dok">amar_putusan_banding_dok</option>
<option value="tgl_kirim_salinan_putusan">tgl_kirim_salinan_putusan</option>
<option value="minutasi_banding">minutasi_banding</option>
<option value="tgl_minutasi">tgl_minutasi</option>
<option value="tgl_pengiriman_berkas_putusan">tgl_pengiriman_berkas_putusan</option>
<option value="pemberitahuan_putusan_banding">pemberitahuan_putusan_banding</option>
<option value="pemberitahuan_putusan_banding_pembanding">pemberitahuan_putusan_banding_pembanding</option>
<option value="pemberitahuan_putusan_banding_terbanding">pemberitahuan_putusan_banding_terbanding</option>
<option value="tgl_pemberitahuan_putusan">tgl_pemberitahuan_putusan</option>
<option value="catatan_banding">catatan_banding</option>
<option value="prodeo_banding">prodeo_banding</option>
<option value="status_banding_id">status_banding_id</option>
<option value="status_banding_text">status_banding_text</option>
<option value="tanggal_cabut">tanggal_cabut</option>

		</select>
	</p>
	<p>SQL Tampil<br>
		<textarea class="w3-input w3-border" id="var_sql_data" name="var_sql_data" rows="5"></textarea>
	</p>
	<p>Data Default<br>
		<textarea class="w3-input w3-border" id="var_default_data" name="var_default_data" rows="5"></textarea>
	</p>
	<p><label>Nama Fungsi</label>
		<select class="w3-select w3-border w3-white" id="var_fungsi_nama" name="var_fungsi_nama">
			<option value="" selected=""></option>
				<option value="hijriah">hijriah</option><option value="tanggal_indonesia">tanggal_indonesia</option><option value="format_uang">format_uang</option><option value="huruf_besar_awal_kata">huruf_besar_awal_kata</option>		</select>
	</p> 
	<p>
					<a onclick="kirim_post('api')" class="w3-button w3-green">Simpan</a>
				
	</p>
</form></div>
<?php mysqli_close($koneksi);?>