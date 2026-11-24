<?php
require_once __DIR__ . '/sys_transport.php';
require_once __DIR__ . '/sys_akta_cerai.php';
$nm_bulan=array('01'=>'Januari','02'=>'Pebruari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September',
                    '10'=>'Oktober','11'=>'Nopember','12'=>'Desember','1'=>'Januari','2'=>'Pebruari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni',
                    '7'=>'Juli','8'=>'Agustus','9'=>'September',);
$hari = array ( 1 =>    'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu'
        );
if(!function_exists('pilihbulan')){ 
    function pilihbulan($bln){
      switch ($bln){case "01": return "Januari"; break; case "02": return "Pebruari"; break; case "03": return "Maret"; break; case "04": return "April"; break; case "05": return "Mei"; break; case "06": return "Juni"; break; case "07": return "Juli"; break; case "08": return "Agustus"; break; case "09": return "September"; break; case "10": return "Oktober"; break; case "11": return "Nopember"; break; case "12": return "Desember"; break; } }
} 
if(!function_exists('format_uang')){ 
  function format_uang($nilai){
    if((int)$nilai==0){
      $nilai='';
    }else{
      $nilai=number_format($nilai, 0, ',', '.');
    }
            return $nilai;
  }  
} 
function tanggal_indon($tanggal, $cetak_hari = false){
  if($tanggal=="0000-00-00" OR is_null($tanggal) OR $tanggal==""){
    $tgl_indo="";
  }else{
    $hari = array ( 1 =>    'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
    $bulan = array (1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $split    = explode('-', $tanggal);
    $tgl_indo = $split[2] . ' ' . $bulan[ (int)$split[1] ] . ' ' . $split[0];
    
    if ($cetak_hari) {
        $num = date('N', strtotime($tanggal));
        return $hari[$num] . ', ' . $tgl_indo;
    }
  }  
    return $tgl_indo;
}
function kasuari_array_value($data, $key, $default = ''){
  return isset($data[$key]) ? $data[$key] : $default;
}
function arr2md5($arrinput){ $hasil=''; foreach($arrinput as $val){ if($hasil==''){ $hasil=md5($val); } else { $code=md5($val); for($hit=0;$hit<min(array(strlen($code),strlen($hasil)));$hit++){ $hasil[$hit]=chr(ord($hasil[$hit]) ^ ord($code[$hit])); } } } return(md5($hasil)); } function getPassword($pase){ $pass = arr2md5($pase); return $pass; }
function kukurl($url, $datanya){
  $result = kasuariSatkerHttpRequest($url, $datanya, array('attempts' => 2, 'timeout' => 900));
  if ($result['error'] !== '') {
    error_log('Kasuari Satker cURL: ' . $result['error']);
  }
  return $result['status'] >= 200 && $result['status'] < 300 ? $result['body'] : false;
}

function curl($url, $data){
    return kukurl($url, $data);
}

if (!function_exists('kasuariLocalSyncSnapshot')) {
  function kasuariLocalSyncSnapshot($connection)
  {
    if (!($connection instanceof mysqli)) {
      return array('error' => 'Database SIPP lokal belum terhubung.');
    }

    $syncColumns = array(
      'perkara' => array(
        'perkara_id', 'jenis_acara', 'alur_perkara_id', 'tanggal_pendaftaran', 'jenis_perkara_id',
        'jenis_perkara_kode', 'jenis_perkara_nama', 'jenis_perkara_text', 'nomor_urut_register',
        'nomor_urut_perkara', 'nomor_perkara', 'nomor_indeks', 'tanggal_surat', 'nomor_surat',
        'pihak1_text', 'pengacara_pihak1', 'pihak2_text', 'pengacara_pihak2', 'pihak3_text',
        'pengacara_pihak3', 'pihak4_text', 'pengacara_pihak4', 'para_pihak', 'pihak_dipublikasikan',
        'posita', 'petitum', 'prodeo', 'terdakwa_anak', 'tahapan_terakhir_id', 'tahapan_terakhir_text',
        'proses_terakhir_id', 'proses_terakhir_text', 'nilai_sengketa', 'diedit_oleh', 'diedit_tanggal',
        'diinput_oleh', 'diinput_tanggal', 'diperbaharui_oleh', 'diperbaharui_tanggal'
      ),
      'perkara_banding' => array(
        'perkara_id', 'jenis_banding', 'alur_perkara_id', 'nomor_perkara_pn', 'putusan_pn',
        'pihak_pembanding', 'permohonan_banding', 'pemohon_banding', 'para_pihak',
        'pemberitahuan_putusan_pn', 'pemberitahuan_permohonan_banding', 'penerimaan_memori_banding',
        'penyerahan_memori_banding', 'penerimaan_kontra_banding', 'penyerahan_kontra_banding',
        'pemberitahuan_inzage', 'pemberitahuan_inzage_pembanding', 'pemberitahuan_inzage_terbanding',
        'pelaksanaan_inzage', 'pelaksanaan_inzage_pembanding', 'pelaksanaan_inzage_terbanding',
        'pengiriman_berkas_banding', 'nomor_surat_pengiriman_berkas_banding',
        'penerimaan_kembali_berkas_banding', 'nomor_urut_register', 'tanggal_pendaftaran_banding',
        'nomor_perkara_banding', 'panitera_pembuat_akta_banding', 'hakim1_banding_id', 'hakim1_banding',
        'hakim2_banding_id', 'hakim2_banding', 'hakim3_banding_id', 'hakim3_banding',
        'hakim4_banding_id', 'hakim4_banding', 'hakim5_banding_id', 'hakim5_banding',
        'majelis_hakim_banding', 'panitera_pengganti_banding_id', 'panitera_pengganti_banding',
        'tanggal_penetapan_sidang_pertama', 'tanggal_sidang_pertama', 'putusan_banding',
        'sumber_hukum_id', 'status_putusan_banding_id', 'status_putusan_banding_text',
        'nomor_putusan_banding', 'amar_putusan_banding', 'amar_putusan_banding_dok',
        'tgl_kirim_salinan_putusan', 'minutasi_banding', 'tgl_minutasi',
        'tgl_pengiriman_berkas_putusan', 'pemberitahuan_putusan_banding',
        'pemberitahuan_putusan_banding_pembanding', 'pemberitahuan_putusan_banding_terbanding',
        'tgl_pemberitahuan_putusan', 'catatan_banding', 'prodeo_banding', 'status_banding_id',
        'status_banding_text', 'tanggal_cabut', 'diedit_oleh', 'diedit_tanggal', 'diinput_oleh',
        'diinput_tanggal', 'diperbaharui_oleh', 'diperbaharui_tanggal'
      ),
      'perkara_banding_detil' => array(
        'id', 'perkara_id', 'status_pihak_id', 'status_pihak_text', 'urutan_banding',
        'permohonan_banding', 'pihak_asal', 'pihak_asal_text', 'pihak_id', 'pihak_nama',
        'pihak_diwakili', 'pemohon_id', 'pemohon_nama', 'pemohon_pekerjaan', 'pemohon_alamat',
        'pemohon_tanggal_surat', 'pemohon_nomor_surat', 'pemohon_banding', 'pemberitahuan_putusan_pn',
        'pemberitahuan_permohonan_banding', 'penerimaan_memori_banding', 'penyerahan_memori_banding',
        'penerimaan_kontra_banding', 'penyerahan_kontra_banding', 'pemberitahuan_inzage',
        'pelaksanaan_inzage', 'pemberitahuan_putusan_banding', 'tanggal_kirim_salinan_putusan',
        'tanggal_cabut'
      ),
      'perkara_putusan' => array('perkara_id', 'tanggal_putusan', 'amar_putusan'),
      'pihak' => array(
        'id', 'jenis_pihak_id', 'jenis_indentitas', 'nomor_indentitas', 'nama', 'tempat_lahir',
        'tanggal_lahir', 'jenis_kelamin', 'golongan_darah', 'alamat', 'rtrw', 'kelurahan',
        'kecamatan', 'kabupaten_id', 'kabupaten', 'propinsi_id', 'propinsi', 'telepon', 'fax',
        'email', 'agama_id', 'agama_nama', 'status_kawin', 'pekerjaan', 'pendidikan_id',
        'pendidikan', 'warga_negara_id', 'warga_negara', 'nama_ayah', 'nama_ibu', 'keterangan',
        'foto', 'difabel', 'diedit_oleh', 'diedit_tanggal', 'diinput_oleh', 'diinput_tanggal',
        'diperbaharui_oleh', 'diperbaharui_tanggal'
      )
    );

    $queries = array(
      'perkara' => "SELECT COUNT(*) AS jumlah,
        MAX(GREATEST(
          IFNULL(NULLIF(diperbaharui_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diedit_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diinput_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00')
        )) AS berubah,
        " . kasuariSnapshotChecksum($syncColumns['perkara']) . " AS checksum
        FROM perkara",
      'perkara_banding' => "SELECT COUNT(*) AS jumlah,
        MAX(GREATEST(
          IFNULL(NULLIF(diperbaharui_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diedit_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diinput_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00')
        )) AS berubah,
        " . kasuariSnapshotChecksum($syncColumns['perkara_banding']) . " AS checksum
        FROM perkara_banding WHERE permohonan_banding>='2023-01-01'",
      'perkara_banding_detil' => "SELECT COUNT(*) AS jumlah,
        MAX(GREATEST(
          IFNULL(NULLIF(permohonan_banding, '0000-00-00'), '1000-01-01'),
          IFNULL(NULLIF(penerimaan_memori_banding, '0000-00-00'), '1000-01-01'),
          IFNULL(NULLIF(penerimaan_kontra_banding, '0000-00-00'), '1000-01-01'),
          IFNULL(NULLIF(tanggal_cabut, '0000-00-00'), '1000-01-01')
        )) AS berubah,
        " . kasuariSnapshotChecksum($syncColumns['perkara_banding_detil']) . " AS checksum
        FROM perkara_banding_detil WHERE perkara_id IN
          (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01')",
      'perkara_putusan' => "SELECT COUNT(*) AS jumlah,
        MAX(NULLIF(tanggal_putusan, '0000-00-00')) AS berubah,
        " . kasuariSnapshotChecksum($syncColumns['perkara_putusan']) . " AS checksum
        FROM perkara_putusan",
      'pihak' => "SELECT COUNT(*) AS jumlah,
        MAX(GREATEST(
          IFNULL(NULLIF(diperbaharui_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diedit_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00'),
          IFNULL(NULLIF(diinput_tanggal, '0000-00-00 00:00:00'), '1000-01-01 00:00:00')
        )) AS berubah,
        " . kasuariSnapshotChecksum($syncColumns['pihak']) . " AS checksum
        FROM pihak WHERE id IN
          (SELECT pihak_id FROM perkara_banding_detil WHERE perkara_id IN
            (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01'))
          OR id IN
          (SELECT pemohon_id FROM perkara_banding_detil WHERE perkara_id IN
            (SELECT perkara_id FROM perkara_banding WHERE permohonan_banding>='2023-01-01'))"
    );

    $aktaPlan = kasuariAktaSourcePlan($connection);
    if (!isset($aktaPlan['error'])) $queries['akta_cerai'] = $aktaPlan['snapshot'];

    $components = array();
    $latestChangedAt = '';
    $localPerkaraCount = 0;
    foreach ($queries as $name => $sql) {
      $result = mysqli_query($connection, $sql);
      if (!$result) {
        error_log('Kasuari Satker gagal membuat snapshot ' . $name . ': ' . mysqli_error($connection));
        return array('error' => 'Gagal memeriksa perubahan tabel ' . $name . '.');
      }
      $row = mysqli_fetch_assoc($result);
      mysqli_free_result($result);
      $changedAt = isset($row['berubah']) ? trim((string) $row['berubah']) : '';
      if (strpos($changedAt, '1000-01-01') === 0) {
        $changedAt = '';
      }
      if ($changedAt !== '' && strlen($changedAt) === 10) {
        $changedAt .= ' 00:00:00';
      }
      if ($changedAt > $latestChangedAt) {
        $latestChangedAt = $changedAt;
      }
      $count = isset($row['jumlah']) ? (int) $row['jumlah'] : 0;
      if ($name === 'perkara') {
        $localPerkaraCount = $count;
      }
      $components[$name] = array(
        'count' => $count,
        'changed_at' => $changedAt,
        'checksum' => isset($row['checksum']) ? (string) $row['checksum'] : '0'
      );
    }

    return array(
      'local_perkara_count' => $localPerkaraCount,
      'local_data_changed_at' => $latestChangedAt,
      'local_signature' => hash('sha256', json_encode($components)),
      'components' => $components
    );
  }
}

if (!function_exists('kasuariSatkerReportStatus')) {
  function kasuariSatkerReportStatus($configuredUrl, $pnId, $appVersion, $snapshot)
  {
    if (!function_exists('curl_init')) {
      return array('ok' => false, 'message' => 'Ekstensi cURL belum aktif pada PHP satker.');
    }
    if (!is_array($snapshot) || isset($snapshot['error'])) {
      return array('ok' => false, 'message' => isset($snapshot['error']) ? $snapshot['error'] : 'Snapshot SIPP tidak valid.');
    }

    $payload = array(
      'action' => 'status',
      'protocol' => 2,
      'pn_id' => $pnId,
      'local_perkara_count' => $snapshot['local_perkara_count'],
      'local_data_changed_at' => $snapshot['local_data_changed_at'],
      'local_signature' => $snapshot['local_signature'],
      'app_version' => $appVersion
    );

    $transport = kasuariSatkerHttpRequest(kasuariSatkerSyncEndpoint($configuredUrl), $payload,
      array('attempts' => 2, 'connect_timeout' => 10, 'timeout' => 30));
    $body = $transport['body'];
    $statusCode = $transport['status'];

    if ($statusCode < 200 || $statusCode >= 300) {
      $message = strpos($body, 'SYNC_ERROR|') === 0
        ? substr($body, strlen('SYNC_ERROR|')) : $transport['error'];
      return array('ok' => false, 'message' => $message, 'http_status' => $statusCode);
    }

    $response = json_decode($body, true);
    if (!is_array($response) || !isset($response['status']) || $response['status'] !== 'ok') {
      return array('ok' => false, 'message' => 'Respons monitoring dari pusat tidak valid.', 'http_status' => $statusCode);
    }

    return array('ok' => true, 'data' => $response, 'http_status' => $statusCode);
  }
}
?>
