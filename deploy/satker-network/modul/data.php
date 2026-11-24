<?php
include_once("sys/sys_koneksi.php");
include_once("sys/sys_config.php");
include_once("sys/sys_fungsi.php");
$url_api = substr(kasuariSatkerSyncEndpoint($url_api), 0, -5) . '/sync_data';
$kirim["pn_id"]=base64_encode($pn_id);
$respon = kasuariSatkerHttpRequest($url_api, $kirim, array('attempts' => 2, 'timeout' => 30));
if ($respon['status'] >= 200 && $respon['status'] < 300) {
  echo $respon['body'];
} else {
  if (function_exists('http_response_code')) {
    http_response_code(502);
  }
  echo htmlspecialchars('Riwayat pengiriman belum dapat dimuat. ' . $respon['error'], ENT_QUOTES, 'UTF-8');
}
?>
