<?php
include_once("sys/sys_session.php");
include_once("sys/sys_koneksi.php");

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
$start = $startGET ? intval($startGET) : 0;
$lengthGET = filter_input(INPUT_GET, "length", FILTER_SANITIZE_NUMBER_INT);
$length = $lengthGET ? intval($lengthGET) : 25;
$searchQuery = isset($_GET["searchQuery"]) ? trim((string) $_GET["searchQuery"]) : "";
$search = $searchQuery === "" || $searchQuery === "null" ? "" : mysqli_real_escape_string($koneksi, $searchQuery);
$sortColumnIndex = filter_input(INPUT_GET, "sortColumn", FILTER_SANITIZE_NUMBER_INT);
$sortDirectionInput = isset($_GET["sortDirection"]) ? strtoupper((string) $_GET["sortDirection"]) : "";
$sortDirection = $sortDirectionInput === "ASC" ? "ASC" : "DESC";
//echo $_GET['start'];exit;
//$start = isset($_GET['start'])?(int)$_GET['start'] : 0;
$column = array("id","nomor_perkara_banding", "pengadilan_agama.nama", "nomor_perkara_pn", "tanggal_pendaftaran_banding", "putusan_banding", "status_banding_text","");
$sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_banding,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.tanggal_pendaftaran_banding) as tanggalpendaftaranbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_banding) AS putusanbanding,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE perkara_banding.tanggal_pendaftaran_banding IS NOT NULL ";
$query=$sql;
$query.= " AND (
	SUBSTRING_INDEX(nomor_perkara_pn,'/',1)='".$search."'
	OR SUBSTRING_INDEX(nomor_perkara_banding,'/',1)='".$search."'
	OR status_banding_text LIKE '%".$search."%'
	OR pengadilan_agama.nama LIKE '%".$search."%' 
	)
	";


if($sortColumnIndex !== null && $sortColumnIndex !== false && isset($column[(int) $sortColumnIndex])){
	$query.= ' ORDER BY '.$column[(int) $sortColumnIndex].' '.$sortDirection.' ';
}else{
	$query.= ' ORDER BY perkara_banding.tanggal_pendaftaran_banding DESC, nomor_urut_register DESC ';
}

$query1 = '';

if($length != -1){
	$query1 = 'LIMIT ' . $start . ', ' . $length;
}
$query_all=$query.$query1;
$query_12=$query.$query1;
//echo "$query_all";
$result=mysqli_query($koneksi,$query_all);
$number_filter_row=mysqli_num_rows($result);
$number_all_data=mysqli_num_rows(mysqli_query($koneksi,$sql));
$nomor=$start;
$data = array();
while($row=mysqli_fetch_assoc($result)){
	$nomor++;
	$sub_array = array();
	$sub_array[] = $nomor;
	$sub_array[] = htmlspecialchars((string) ($row['nomor_perkara_banding'] ?? ''), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars(str_replace("PENGADILAN AGAMA", "PA", (string) ($row["pengaju"] ?? '')), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars((string) ($row['nomor_perkara_pn'] ?? ''), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars((string) ($row['tanggalpendaftaranbanding'] ?? '-'), ENT_QUOTES, 'UTF-8');
	$sub_array[] = htmlspecialchars((string) ($row['putusanbanding'] ?? '-'), ENT_QUOTES, 'UTF-8');
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
