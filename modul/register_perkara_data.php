<?php
include_once("sys/sys_session.php");
include_once("sys/sys_koneksi.php");
include_once("sys/sys_fungsi.php");
header('Content-Type: application/json; charset=utf-8');

function register_banding_status_class($text) {
	$text = strtolower(trim((string) $text));
	if ($text === '') return 'kosong';
	if (strpos($text, 'minutasi') !== false) return 'minutasi';
	if (strpos($text, 'pemberitahuan') !== false) return 'pemberitahuan';
	if (strpos($text, 'putus') !== false) return 'putusan';
	if (strpos($text, 'cabut') !== false) return 'dicabut';
	return 'proses';
}
$startGET = filter_input(INPUT_GET, "start", FILTER_SANITIZE_NUMBER_INT);
$start = $startGET ? max(0, intval($startGET)) : 0;
$lengthGET = filter_input(INPUT_GET, "length", FILTER_SANITIZE_NUMBER_INT);
$length = $lengthGET ? intval($lengthGET) : 25;
$searchQuery = isset($_GET["searchQuery"]) ? trim((string) $_GET["searchQuery"]) : "";
$search = $searchQuery === "" || $searchQuery === "null" ? "" : mysqli_real_escape_string($koneksi, $searchQuery);
$sortColumnIndex = filter_input(INPUT_GET, "sortColumn", FILTER_SANITIZE_NUMBER_INT);
$sortDirectionInput = isset($_GET["sortDirection"]) ? strtoupper((string) $_GET["sortDirection"]) : "";
$sortDirection = $sortDirectionInput === "ASC" ? "ASC" : "DESC";
//echo $_GET['start'];exit;
//$start = isset($_GET['start'])?(int)$_GET['start'] : 0;
$column = array("perkara_banding.id", "perkara_banding.nomor_perkara_banding", "pengadilan_agama.nama", "perkara_banding.nomor_perkara_pn", "perkara_banding.tanggal_pendaftaran_banding", "perkara_banding.putusan_banding", "perkara_banding.status_banding_text", "");
$sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_banding,
          perkara_banding.nomor_perkara_pn,
          perkara_banding.tanggal_pendaftaran_banding AS tanggalpendaftaranbanding,
          perkara_banding.putusan_banding AS putusanbanding,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE perkara_banding.tanggal_pendaftaran_banding IS NOT NULL ";
$query=$sql;
$searchSql = '';
if ($search !== '') {
	$searchSql = " AND (
		SUBSTRING_INDEX(perkara_banding.nomor_perkara_pn,'/',1)='".$search."'
		OR SUBSTRING_INDEX(perkara_banding.nomor_perkara_banding,'/',1)='".$search."'
		OR perkara_banding.status_banding_text LIKE '%".$search."%'
		OR pengadilan_agama.nama LIKE '%".$search."%'
	) ";
}
$query .= $searchSql;


if($sortColumnIndex !== null && $sortColumnIndex !== false && !empty($column[(int) $sortColumnIndex])){
	$query.= ' ORDER BY '.$column[(int) $sortColumnIndex].' '.$sortDirection.' ';
}else{
	$query.= ' ORDER BY perkara_banding.tanggal_pendaftaran_banding DESC, perkara_banding.nomor_urut_register DESC ';
}

$query1 = '';

if($length != -1){
	$query1 = 'LIMIT ' . $start . ', ' . $length;
}
$query_all=$query.$query1;
$result=mysqli_query($koneksi,$query_all);
$countAllResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM perkara_banding WHERE tanggal_pendaftaran_banding IS NOT NULL");
$countFilteredSql = "SELECT COUNT(*) AS total
	FROM perkara_banding
	LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara_banding.pn_id
	WHERE perkara_banding.tanggal_pendaftaran_banding IS NOT NULL" . $searchSql;
$countFilteredResult = mysqli_query($koneksi, $countFilteredSql);

if (!$result || !$countAllResult || !$countFilteredResult) {
	error_log('KASUARI register_perkara_data query failed: ' . mysqli_error($koneksi));
	http_response_code(500);
	echo json_encode(array(
		"recordsTotal" => 0,
		"recordsFiltered" => 0,
		"data" => array(),
		"error" => "Data perkara banding belum dapat dimuat. Periksa struktur database server."
	));
	mysqli_close($koneksi);
	exit;
}

$countAllRow = mysqli_fetch_assoc($countAllResult);
$countFilteredRow = mysqli_fetch_assoc($countFilteredResult);
$number_all_data = (int) ($countAllRow['total'] ?? 0);
$number_filter_row = (int) ($countFilteredRow['total'] ?? 0);
$nomor=$start;
$data = array();
while($row=mysqli_fetch_assoc($result)){
	$nomor++;
	$sub_array = array();
	$sub_array[] = $nomor;
	$sub_array[] = htmlspecialchars((string) ($row['nomor_perkara_banding'] ?? ''), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars(str_replace("PENGADILAN AGAMA", "PA", (string) ($row["pengaju"] ?? '')), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars((string) ($row['nomor_perkara_pn'] ?? ''), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars(kasuari_tanggal_indonesia($row['tanggalpendaftaranbanding'] ?? ''), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars(kasuari_tanggal_indonesia($row['putusanbanding'] ?? ''), ENT_QUOTES, 'UTF-8');
	$statusBanding = htmlspecialchars($row['status_banding_text'] ?? "", ENT_QUOTES, 'UTF-8');
	$statusBandingClass = register_banding_status_class($row['status_banding_text'] ?? "");
	$sub_array[] = "<span class='ks-banding-status " . $statusBandingClass . "'>" . ($statusBanding !== '' ? $statusBanding : 'Belum ada status') . "</span>";
	$sub_array[] = "<a class='kasuari-action-link' href='perkara_detil_banding&id=".$row["id"]."' title='Detail Perkara'><i class='bi bi-eye' aria-hidden='true'></i> Detail</a>";
	$data[] = $sub_array;
}

$output = array(
	"recordsTotal"		=>	$number_all_data,
	"recordsFiltered"	=>	$number_filter_row,
	"data"				=>	$data
);

echo json_encode($output);
mysqli_close($koneksi);
?>
