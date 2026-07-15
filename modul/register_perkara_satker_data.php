<?php
include_once("sys/sys_session.php");
include_once("sys/sys_koneksi.php");

$startGET = filter_input(INPUT_GET, "start", FILTER_SANITIZE_NUMBER_INT);
$start = $startGET ? intval($startGET) : 0;
$lengthGET = filter_input(INPUT_GET, "length", FILTER_SANITIZE_NUMBER_INT);
$length = $lengthGET ? intval($lengthGET) : 25;
$searchQuery = isset($_GET["searchQuery"]) ? $_GET["searchQuery"] : "";
$search = empty($searchQuery) || $searchQuery === "null" ? "" : mysqli_real_escape_string($koneksi, $searchQuery);
$satkerIdInput = filter_input(INPUT_GET, "satker_id", FILTER_SANITIZE_NUMBER_INT);
$satkerId = $satkerIdInput !== null && $satkerIdInput !== false ? (int) $satkerIdInput : 0;
$sortColumnIndex = filter_input(INPUT_GET, "sortColumn", FILTER_SANITIZE_NUMBER_INT);
$sortDirectionInput = isset($_GET["sortDirection"]) ? strtoupper($_GET["sortDirection"]) : "";
$sortDirection = $sortDirectionInput === "ASC" ? "ASC" : "DESC";

function satker_status_class($text) {
  $text = strtolower((string) $text);
  if ($text === '') return 'kosong';
  if (strpos($text, 'penetapan') !== false) return 'penetapan';
  if (strpos($text, 'sidang') !== false || strpos($text, 'persidangan') !== false) return 'sidang';
  if (strpos($text, 'minutasi') !== false) return 'minutasi';
  if (strpos($text, 'akta cerai') !== false) return 'akta';
  if (strpos($text, 'ikrar') !== false) return 'ikrar';
  if (strpos($text, 'pemberitahuan') !== false) return 'pemberitahuan';
  if (strpos($text, 'banding') !== false || strpos($text, 'permohonan banding') !== false) return 'banding';
  if (strpos($text, 'kasasi') !== false) return 'kasasi';
  if (strpos($text, 'verzet') !== false || strpos($text, 'perlawanan') !== false) return 'verzet';
  if (strpos($text, 'eksekusi') !== false) return 'eksekusi';
  if (strpos($text, 'putus') !== false || strpos($text, 'selesai') !== false) return 'putusan';
  if (strpos($text, 'cabut') !== false || strpos($text, 'gugur') !== false) return 'dicabut';
  return 'belum';
}

function satker_type_class($text) {
  $text = strtolower((string) $text);
  if (strpos($text, 'cerai gugat') !== false) return 'rose';
  if (strpos($text, 'cerai talak') !== false) return 'amber';
  if (strpos($text, 'waris') !== false) return 'purple';
  if (strpos($text, 'dispensasi') !== false || strpos($text, 'perwalian') !== false) return 'emerald';
  return 'blue';
}

$column = array(
  "perkara.id",
  "perkara.nomor_perkara",
  "pengadilan_agama.nama",
  "perkara.jenis_perkara_nama",
  "perkara.tanggal_pendaftaran",
  "putusan.tanggal_putusan",
  "perkara.tahapan_terakhir_text",
  "perkara.proses_terakhir_text",
  "perkara.id"
);

$baseSql = "SELECT
              perkara.id,
              perkara.nomor_perkara,
              perkara.jenis_perkara_nama,
              convert_tanggal_indonesia(perkara.tanggal_pendaftaran) AS tanggalpendaftaran,
              perkara.tahapan_terakhir_text,
              perkara.proses_terakhir_text,
              pengadilan_agama.nama AS pengaju,
              convert_tanggal_indonesia(putusan.tanggal_putusan) AS tanggalputusan
            FROM perkara
            LEFT JOIN pengadilan_agama ON pengadilan_agama.id = perkara.pn_id
            LEFT JOIN (
              SELECT pn_id, perkara_id, MAX(tanggal_putusan) AS tanggal_putusan
              FROM perkara_putusan
              GROUP BY pn_id, perkara_id
            ) putusan ON putusan.pn_id = perkara.pn_id AND putusan.perkara_id = perkara.perkara_id
            WHERE 1=1";

$query = $baseSql;
if ($satkerId > 0) {
  $query .= " AND perkara.pn_id = " . $satkerId;
}
if ($search !== "") {
  $query .= " AND (
    perkara.nomor_perkara LIKE '%" . $search . "%'
    OR perkara.jenis_perkara_nama LIKE '%" . $search . "%'
    OR perkara.tahapan_terakhir_text LIKE '%" . $search . "%'
    OR perkara.proses_terakhir_text LIKE '%" . $search . "%'
    OR pengadilan_agama.nama LIKE '%" . $search . "%'
  )";
}

if ($sortColumnIndex !== null && $sortColumnIndex !== false && isset($column[(int) $sortColumnIndex])) {
  $query .= " ORDER BY " . $column[(int) $sortColumnIndex] . " " . $sortDirection . " ";
} else {
  $query .= " ORDER BY perkara.tanggal_pendaftaran DESC, perkara.nomor_urut_register DESC ";
}

$queryLimit = "";
if ($length != -1) {
  $queryLimit = " LIMIT " . $start . ", " . $length;
}

$result = mysqli_query($koneksi, $query . $queryLimit);
$filteredResult = mysqli_query($koneksi, $query);
$number_filter_row = $filteredResult ? mysqli_num_rows($filteredResult) : 0;
$number_all_data = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM perkara"));

$nomor = $start;
$data = array();
while ($row = mysqli_fetch_assoc($result)) {
  $nomor++;
  $id = (int) ($row['id'] ?? 0);
  $nomorPerkara = htmlspecialchars($row['nomor_perkara'] ?? "", ENT_QUOTES, 'UTF-8');
  $satker = htmlspecialchars(str_replace("PENGADILAN AGAMA", "PA", $row["pengaju"] ?? ""), ENT_QUOTES, 'UTF-8');
  $jenis = htmlspecialchars($row['jenis_perkara_nama'] ?? "", ENT_QUOTES, 'UTF-8');
  $tanggalDaftar = htmlspecialchars($row['tanggalpendaftaran'] ?? "", ENT_QUOTES, 'UTF-8');
  $tanggalPutusan = htmlspecialchars($row['tanggalputusan'] ?? "-", ENT_QUOTES, 'UTF-8');
  $tahapan = htmlspecialchars($row['tahapan_terakhir_text'] ?? "", ENT_QUOTES, 'UTF-8');
  $proses = htmlspecialchars($row['proses_terakhir_text'] ?? "", ENT_QUOTES, 'UTF-8');
  $typeClass = satker_type_class($row['jenis_perkara_nama'] ?? "");
  $stageClass = satker_status_class($row['tahapan_terakhir_text'] ?? "");
  $processClass = satker_status_class($row['proses_terakhir_text'] ?? "");
  $sub_array = array();
  $sub_array[] = "<span class='ks-row-number'>" . $nomor . "</span>";
  $sub_array[] = "<a class='ks-case-ref' href='perkara_detil_satker&id=" . $id . "'><span>" . $nomorPerkara . "</span></a>";
  $sub_array[] = "<span class='ks-satker-badge'>" . $satker . "</span>";
  $sub_array[] = "<span class='ks-type-badge " . $typeClass . "'>" . $jenis . "</span>";
  $sub_array[] = "<span class='ks-date-chip'>" . $tanggalDaftar . "</span>";
  $sub_array[] = "<span class='ks-date-chip muted'>" . $tanggalPutusan . "</span>";
  $sub_array[] = "<span class='ks-status-pill " . $stageClass . "'>" . $tahapan . "</span>";
  $sub_array[] = "<span class='ks-status-pill " . $processClass . "'>" . $proses . "</span>";
  $sub_array[] = "<a class='kasuari-action-link ks-view-action' href='perkara_detil_satker&id=" . $id . "' title='Lihat detail perkara' aria-label='Lihat detail perkara'><i class='bi bi-eye' aria-hidden='true'></i></a>";
  $data[] = $sub_array;
}

$output = array(
  "recordsTotal" => $number_all_data,
  "recordsFiltered" => $number_filter_row,
  "data" => $data
);

echo json_encode($output);
mysqli_close($koneksi);
?>
