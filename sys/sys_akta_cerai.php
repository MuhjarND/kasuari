<?php
// Data SIPP dan waktu snapshot disimpan terpisah dari status perkara/banding.
function kasuari_akta_schema_ready($db, $create = false)
{
    if ($create) {
        $sql = file_get_contents(__DIR__ . '/../database/akta_cerai.sql');
        if ($sql === false) return false;
        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) !== '' && !mysqli_query($db, $statement)) {
                error_log('Kasuari skema akta cerai: ' . mysqli_error($db));
                return false;
            }
        }
    }
    $result = mysqli_query($db, "SELECT COUNT(*) AS total FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND ENGINE='InnoDB'
        AND TABLE_NAME IN ('kasuari_akta_cerai','kasuari_akta_cerai_stage','kasuari_akta_cerai_sync')");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row && (int) $row['total'] === 3;
}

function kasuari_akta_date($value)
{
    if ($value === null || $value === '' || $value === '0000-00-00') return null;
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
        throw new InvalidArgumentException('Tanggal akta cerai tidak valid.');
    }
    $parts = explode('-', $value);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        throw new InvalidArgumentException('Tanggal akta cerai tidak valid.');
    }
    return $value;
}

function kasuari_akta_validate_row($row)
{
    $keys = array('perkara_id', 'nomor_akta_cerai', 'tgl_akta_cerai',
        'tgl_penyerahan_akta_cerai', 'tgl_penyerahan_akta_cerai_pihak2', 'pihak2_tersedia');
    if (!is_array($row) || array_diff($keys, array_keys($row)) || array_diff(array_keys($row), $keys)) {
        throw new InvalidArgumentException('Kolom data akta cerai tidak sesuai.');
    }
    if (!is_scalar($row['perkara_id']) || !preg_match('/^[1-9][0-9]{0,14}$/D', (string) $row['perkara_id'])) {
        throw new InvalidArgumentException('Identitas perkara akta cerai tidak valid.');
    }
    if ($row['nomor_akta_cerai'] !== null && !is_string($row['nomor_akta_cerai'])) {
        throw new InvalidArgumentException('Nomor akta cerai tidak valid.');
    }
    if (!preg_match('/^.{0,255}$/usD', (string) $row['nomor_akta_cerai'])) {
        throw new InvalidArgumentException('Nomor akta cerai tidak valid.');
    }
    if (!in_array($row['pihak2_tersedia'], array(0, 1, '0', '1'), true)) {
        throw new InvalidArgumentException('Informasi penyerahan pihak kedua tidak valid.');
    }
    foreach (array_slice($keys, 2, 3) as $field) $row[$field] = kasuari_akta_date($row[$field]);
    $row['nomor_akta_cerai'] = trim((string) $row['nomor_akta_cerai']);
    $row['pihak2_tersedia'] = (int) $row['pihak2_tersedia'];
    if (!$row['pihak2_tersedia']) $row['tgl_penyerahan_akta_cerai_pihak2'] = null;
    return $row;
}

function kasuari_akta_stage_rows($db, $pnId, $requestId, $rows)
{
    $statement = mysqli_prepare($db, "INSERT INTO kasuari_akta_cerai_stage
        (pn_id, request_id, perkara_id, nomor_akta_cerai, tgl_akta_cerai,
         tgl_penyerahan_akta_cerai, tgl_penyerahan_akta_cerai_pihak2, pihak2_tersedia, received_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE
        nomor_akta_cerai=VALUES(nomor_akta_cerai), tgl_akta_cerai=VALUES(tgl_akta_cerai),
        tgl_penyerahan_akta_cerai=VALUES(tgl_penyerahan_akta_cerai),
        tgl_penyerahan_akta_cerai_pihak2=VALUES(tgl_penyerahan_akta_cerai_pihak2),
        pihak2_tersedia=VALUES(pihak2_tersedia), received_at=NOW()");
    if (!$statement) throw new RuntimeException(mysqli_error($db));
    try {
        foreach ($rows as $row) {
            $row = kasuari_akta_validate_row($row);
            mysqli_stmt_bind_param($statement, 'issssssi', $pnId, $requestId, $row['perkara_id'],
                $row['nomor_akta_cerai'], $row['tgl_akta_cerai'], $row['tgl_penyerahan_akta_cerai'],
                $row['tgl_penyerahan_akta_cerai_pihak2'], $row['pihak2_tersedia']);
            if (!mysqli_stmt_execute($statement)) throw new RuntimeException(mysqli_stmt_error($statement));
        }
    } finally {
        mysqli_stmt_close($statement);
    }
}

// Dipanggil di dalam transaksi finalisasi yang sama dengan riwayat pengiriman.
function kasuari_akta_publish($db, $pnId, $requestId, $expectedCount, $syncedAt)
{
    $pnId = (int) $pnId;
    if (!preg_match('/^[a-f0-9]{40}$/D', $requestId)) throw new InvalidArgumentException('Request akta tidak valid.');
    $scope = "pn_id={$pnId} AND request_id='{$requestId}'";
    $result = mysqli_query($db, "SELECT COUNT(*) AS total FROM kasuari_akta_cerai_stage WHERE {$scope}");
    $count = $result ? mysqli_fetch_assoc($result) : null;
    if (!$count || (int) $count['total'] !== $expectedCount) {
        throw new RuntimeException('Paket akta cerai belum lengkap. Kirim ulang data dari satker.');
    }
    foreach (array(
        "DELETE FROM kasuari_akta_cerai WHERE pn_id={$pnId}",
        "INSERT INTO kasuari_akta_cerai (pn_id, perkara_id, nomor_akta_cerai, tgl_akta_cerai,
            tgl_penyerahan_akta_cerai, tgl_penyerahan_akta_cerai_pihak2, pihak2_tersedia)
         SELECT pn_id, perkara_id, nomor_akta_cerai, tgl_akta_cerai,
            tgl_penyerahan_akta_cerai, tgl_penyerahan_akta_cerai_pihak2, pihak2_tersedia
         FROM kasuari_akta_cerai_stage WHERE {$scope}"
    ) as $sql) {
        if (!mysqli_query($db, $sql)) throw new RuntimeException(mysqli_error($db));
    }
    $statement = mysqli_prepare($db, "INSERT INTO kasuari_akta_cerai_sync (pn_id, request_id, synced_at, row_count)
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),
        synced_at=VALUES(synced_at), row_count=VALUES(row_count)");
    if (!$statement) throw new RuntimeException(mysqli_error($db));
    mysqli_stmt_bind_param($statement, 'issi', $pnId, $requestId, $syncedAt, $expectedCount);
    $ok = mysqli_stmt_execute($statement);
    $error = mysqli_stmt_error($statement);
    mysqli_stmt_close($statement);
    if (!$ok) throw new RuntimeException($error);
    if (!mysqli_query($db, "DELETE FROM kasuari_akta_cerai_stage WHERE {$scope}")) {
        throw new RuntimeException(mysqli_error($db));
    }
}

function kasuari_akta_load($db, $cases)
{
    if (!function_exists('kasuari_is_admin') || !kasuari_is_admin() || !$cases) return array();
    $conditions = array(); $courts = array(); $map = array();
    foreach ($cases as $case) {
        $pn = (int) $case['pn_id']; $id = (int) $case['perkara_id'];
        $key = $pn . ':' . $id;
        $map[$key] = array('state' => 'unavailable');
        $conditions[$key] = "(pn_id={$pn} AND perkara_id={$id})";
        $courts[$pn] = $pn;
    }
    if (!kasuari_akta_schema_ready($db)) return $map;
    $result = mysqli_query($db, 'SELECT pn_id, synced_at FROM kasuari_akta_cerai_sync WHERE pn_id IN (' . implode(',', $courts) . ')');
    if (!$result) return $map;
    $sync = array();
    while ($row = mysqli_fetch_assoc($result)) $sync[$row['pn_id']] = $row['synced_at'];
    $result = mysqli_query($db, 'SELECT * FROM kasuari_akta_cerai WHERE ' . implode(' OR ', $conditions));
    if (!$result) return $map;
    foreach ($cases as $case) {
        if (isset($sync[$case['pn_id']])) {
            $map[$case['pn_id'] . ':' . $case['perkara_id']] = array('state' => 'not_recorded', 'synced_at' => $sync[$case['pn_id']]);
        }
    }
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($sync[$row['pn_id']])) {
            $row['state'] = 'recorded';
            $row['synced_at'] = $sync[$row['pn_id']];
            $map[$row['pn_id'] . ':' . $row['perkara_id']] = $row;
        }
    }
    return $map;
}

function kasuari_akta_view($record)
{
    if (!$record || $record['state'] === 'unavailable') {
        return array('tone' => 'secondary', 'label' => 'Akta belum disinkronkan');
    }
    $number = trim((string) ($record['nomor_akta_cerai'] ?? ''));
    $date = $record['tgl_akta_cerai'] ?? null;
    if ($number !== '' && $date) return array('tone' => 'success', 'label' => 'Akta terbit tercatat');
    if ($number !== '' || $date || !empty($record['tgl_penyerahan_akta_cerai']) || !empty($record['tgl_penyerahan_akta_cerai_pihak2'])) {
        return array('tone' => 'warning', 'label' => 'Data akta belum lengkap');
    }
    return array('tone' => 'secondary', 'label' => 'Akta belum tercatat di SIPP');
}

function kasuari_akta_html($case, $map, $detail = false)
{
    if (!function_exists('kasuari_is_admin') || !kasuari_is_admin()) return '';
    $key = $case['pn_id'] . ':' . $case['perkara_id'];
    $record = $map[$key] ?? array('state' => 'unavailable');
    $type = strtolower((string) ($case['jenis_perkara_nama'] ?? $case['jenis_banding'] ?? ''));
    if (strpos($type, 'cerai') === false && $record['state'] !== 'recorded') return '';
    $view = kasuari_akta_view($record);
    $escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    $html = '<div class="ks-akta-status"><span class="badge text-bg-' . $view['tone'] . '">' . $view['label'] . '</span>';
    if (!$detail) return $html . '</div>';
    $html .= '<dl class="ks-akta-fields">';
    if ($record['state'] === 'recorded') {
        $fields = array('Nomor akta cerai' => $record['nomor_akta_cerai'] ?: '-',
            'Tanggal terbit' => kasuari_tanggal_indonesia($record['tgl_akta_cerai']),
            'Penyerahan pihak 1' => kasuari_tanggal_indonesia($record['tgl_penyerahan_akta_cerai'], 'Belum tercatat di SIPP'),
            'Penyerahan pihak 2' => empty($record['pihak2_tersedia']) ? 'Tidak tersedia pada SIPP sumber'
                : kasuari_tanggal_indonesia($record['tgl_penyerahan_akta_cerai_pihak2'], 'Belum tercatat di SIPP'));
        foreach ($fields as $label => $value) $html .= '<div><dt>' . $label . '</dt><dd>' . $escape($value) . '</dd></div>';
    }
    if (!empty($record['synced_at'])) {
        $time = DateTime::createFromFormat('Y-m-d H:i:s', $record['synced_at'], new DateTimeZone('Asia/Jayapura'));
        $html .= '<div><dt>Sinkronisasi akta terakhir</dt><dd>' . $escape($time ? $time->format('d/m/Y H:i:s') . ' WIT' : '-') . '</dd></div>';
    }
    $html .= '</dl><p class="ks-akta-note">Sumber: SIPP satker. Status unduh di EAC tidak dipantau.</p></div>';
    return $html;
}
