<?php
if (!function_exists('kasuariSnapshotChecksum')) {
    function kasuariSnapshotChecksum($columns)
    {
        $parts = array();
        foreach ($columns as $column) {
            if (!preg_match('/^[a-z0-9_]+$/i', $column)) continue;
            $parts[] = "CRC32(IFNULL(`" . $column . "`, ''))";
        }
        return "BIT_XOR(CRC32(CONCAT_WS('|', " . implode(', ', $parts) . ")))";
    }
}
// Hanya metadata; tidak membaca file PDF, tidak menulis ke database SIPP.
function kasuariAktaSourcePlan($db)
{
    $result = mysqli_query($db, 'SHOW COLUMNS FROM perkara_akta_cerai');
    if (!$result) return array('error' => 'Tabel perkara_akta_cerai tidak dapat dibaca dari SIPP.');
    $columns = array();
    while ($row = mysqli_fetch_assoc($result)) $columns[$row['Field']] = true;
    mysqli_free_result($result);
    $required = array('perkara_id', 'nomor_akta_cerai', 'tgl_akta_cerai', 'tgl_penyerahan_akta_cerai');
    foreach ($required as $column) {
        if (!isset($columns[$column])) return array('error' => 'Kolom SIPP perkara_akta_cerai.' . $column . ' belum tersedia.');
    }
    $pihak2 = isset($columns['tgl_penyerahan_akta_cerai_pihak2']);
    $select = implode(', ', $required) . ', '
        . ($pihak2 ? 'tgl_penyerahan_akta_cerai_pihak2' : 'NULL AS tgl_penyerahan_akta_cerai_pihak2')
        . ', ' . ($pihak2 ? '1' : '0') . ' AS pihak2_tersedia';
    $checksumColumns = $required;
    if ($pihak2) $checksumColumns[] = 'tgl_penyerahan_akta_cerai_pihak2';
    $changeDates = array();
    foreach (array('tgl_akta_cerai', 'tgl_penyerahan_akta_cerai', 'tgl_penyerahan_akta_cerai_pihak2',
        'diperbaharui_tanggal', 'diedit_tanggal', 'diinput_tanggal') as $column) {
        if (isset($columns[$column])) $changeDates[] = "IFNULL(NULLIF(`{$column}`, '0000-00-00'), '1000-01-01')";
    }
    return array('query' => 'SELECT ' . $select . ' FROM perkara_akta_cerai ORDER BY perkara_id',
        'snapshot' => 'SELECT COUNT(*) AS jumlah, MAX(GREATEST(' . implode(',', $changeDates) . ')) AS berubah, '
            . kasuariSnapshotChecksum($checksumColumns) . ' AS checksum FROM perkara_akta_cerai');
}

function kasuariAktaBuildPayloads($db, $maxBytes)
{
    $plan = kasuariAktaSourcePlan($db);
    if (isset($plan['error'])) return $plan;
    $result = mysqli_query($db, $plan['query']);
    if (!$result) return array('error' => 'Data status akta cerai SIPP tidak dapat dibaca.');
    $batches = array(); $rows = array(); $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        foreach (array('tgl_akta_cerai', 'tgl_penyerahan_akta_cerai', 'tgl_penyerahan_akta_cerai_pihak2') as $field) {
            if ($row[$field] === '' || $row[$field] === '0000-00-00') $row[$field] = null;
        }
        $candidate = $rows;
        $candidate[] = $row;
        $json = json_encode($candidate);
        if ($json === false) {
            mysqli_free_result($result);
            return array('error' => 'Data akta cerai mengandung karakter yang tidak dapat dikirim.');
        }
        if ($rows && (count($candidate) > 100 || strlen($json) > $maxBytes)) {
            $batches[] = json_encode($rows);
            $rows = array();
        }
        $rows[] = $row;
        if (strlen(json_encode($rows)) > $maxBytes) {
            mysqli_free_result($result);
            return array('error' => 'Satu data akta cerai melebihi batas ukuran paket pusat.');
        }
        $count++;
    }
    mysqli_free_result($result);
    if ($rows) $batches[] = json_encode($rows);
    return array('batches' => $batches, 'rows' => $count);
}
