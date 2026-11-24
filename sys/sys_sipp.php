<?php
require_once __DIR__ . '/sys_sipp_catalog.php';

function kasuari_sipp_schema($db, $create = false)
{
    if ($create) {
        $sql = file_get_contents(__DIR__ . '/../database/sipp_portal.sql');
        if ($sql === false) return false;
        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) !== '' && !mysqli_query($db, $statement)) {
                error_log('Skema portal SIPP: ' . mysqli_error($db));
                return false;
            }
        }
    }
    $result = mysqli_query($db, "SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
        AND ENGINE='InnoDB' AND TABLE_NAME IN ('kasuari_sipp_rows','kasuari_sipp_stage','kasuari_sipp_state','kasuari_sipp_tokens','kasuari_sipp_requests')");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row && (int) $row['total'] === 5;
}

function kasuari_sipp_statement($db, $sql, $types = '', $values = array())
{
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) throw new RuntimeException(mysqli_error($db));
    if ($types !== '') {
        $args = array($stmt, $types);
        foreach ($values as $i => $value) $args[] = &$values[$i];
        if (!call_user_func_array('mysqli_stmt_bind_param', $args)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Parameter query SIPP tidak valid.');
        }
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
        throw new RuntimeException($error);
    }
    return $stmt;
}

function kasuari_sipp_select($db, $sql, $types = '', $values = array())
{
    $stmt = kasuari_sipp_statement($db, $sql, $types, $values);
    $result = mysqli_stmt_get_result($stmt);
    $rows = array();
    if ($result) while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

function kasuari_sipp_auth($db, $pn, $token)
{
    if (!isset(kasuari_sipp_courts()[(int) $pn]) || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
    $rows = kasuari_sipp_select($db, 'SELECT token_hash FROM kasuari_sipp_tokens WHERE pn_id=?', 'i', array($pn));
    return $rows && hash_equals($rows[0]['token_hash'], hash('sha256', $token));
}

function kasuari_sipp_issue_token($db, $pn, $userId)
{
    if (!isset(kasuari_sipp_courts()[(int) $pn])) throw new InvalidArgumentException('Satker tidak dikenal.');
    $token = bin2hex(random_bytes(32));
    $stmt = kasuari_sipp_statement($db, 'INSERT INTO kasuari_sipp_tokens (pn_id, token_hash, created_at, created_by)
        VALUES (?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash), created_at=VALUES(created_at), created_by=VALUES(created_by)',
        'isi', array($pn, hash('sha256', $token), $userId));
    mysqli_stmt_close($stmt);
    return $token;
}

function kasuari_sipp_record($dataset, $row)
{
    $catalog = kasuari_sipp_catalog();
    if (!isset($catalog[$dataset]) || !is_array($row) || !$row) throw new InvalidArgumentException('Dataset atau record tidak valid.');
    $spec = $catalog[$dataset];
    if (array_diff(array_keys($row), array_keys($spec['fields']))) throw new InvalidArgumentException('Kolom tidak diizinkan.');
    foreach ($row as $key => $value) {
        if ($value !== null && !is_scalar($value)) throw new InvalidArgumentException('Nilai kolom tidak valid.');
        if (is_string($value) && (!preg_match('//u', $value) || strlen($value) > 2000000)) throw new InvalidArgumentException('Isi kolom tidak valid atau terlalu besar.');
    }
    $keys = array();
    foreach ($spec['keys'] as $key) {
        if (!isset($row[$key]) || trim((string) $row[$key]) === '') throw new InvalidArgumentException('Identitas record tidak lengkap.');
        $keys[] = (string) $row[$key];
    }
    $date = isset($row[$spec['date']]) ? (string) $row[$spec['date']] : '';
    $date = substr($date, 0, 10);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts) || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) $date = null;
    $caseId = isset($row[$spec['case_field']]) ? (string) $row[$spec['case_field']] : null;
    if ($caseId !== null && !preg_match('/^[0-9]{1,20}$/D', $caseId)) throw new InvalidArgumentException('ID perkara tidak valid.');
    $json = json_encode($row, JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new InvalidArgumentException('JSON data tidak valid.');
    $search = strip_tags(html_entity_decode(implode(' ', array_map(function ($v) { return (string) $v; }, $row)), ENT_QUOTES, 'UTF-8'));
    return array(hash('sha256', json_encode($keys)), $caseId, $date, $search, $json);
}

function kasuari_sipp_stage($db, $pn, $request, $dataset, $rows)
{
    if (!preg_match('/^[a-f0-9]{40}$/D', $request)) throw new InvalidArgumentException('Request tidak valid.');
    if (!is_array($rows) || !$rows || count($rows) > 100 || array_keys($rows) !== range(0, count($rows) - 1)) throw new InvalidArgumentException('Paket tidak valid.');
    kasuari_sipp_lock_request($db, $pn, $request);
    foreach ($rows as $row) {
        list($key, $case, $date, $search, $json) = kasuari_sipp_record($dataset, $row);
        $stmt = kasuari_sipp_statement($db, 'INSERT INTO kasuari_sipp_stage
            (pn_id, request_id, dataset, record_key, case_id, record_date, search_text, data_json, received_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE case_id=VALUES(case_id), record_date=VALUES(record_date),
            search_text=VALUES(search_text), data_json=VALUES(data_json), received_at=NOW()',
            'isssssss', array($pn, $request, $dataset, $key, $case, $date, $search, $json));
        mysqli_stmt_close($stmt);
    }
}

// Caller owns the transaction. A manifest must cover the full known catalog.
function kasuari_sipp_publish($db, $pn, $request, $manifest, $time)
{
    $catalog = kasuari_sipp_catalog();
    if (!is_array($manifest) || array_diff(array_keys($catalog), array_keys($manifest)) || array_diff(array_keys($manifest), array_keys($catalog))) {
        throw new InvalidArgumentException('Manifest SIPP tidak lengkap atau versi katalog berbeda.');
    }
    $requestRow = kasuari_sipp_lock_request($db, $pn, $request);
    $latest = kasuari_sipp_select($db, "SELECT generation FROM kasuari_sipp_requests WHERE pn_id=? AND state='completed' ORDER BY generation DESC LIMIT 1 FOR UPDATE", 'i', array($pn));
    if ($latest && $latest[0]['generation'] > $requestRow['generation']) throw new RuntimeException('Snapshot lebih baru sudah diterima. Kirim ulang dari satker.');
    foreach ($manifest as $dataset => $meta) {
        if (!is_array($meta) || !isset($meta['state'], $meta['count'], $meta['columns']) || !is_array($meta['columns']) ||
            !in_array($meta['state'], array('ready', 'unavailable', 'missing_columns'), true) || !is_int($meta['count']) || $meta['count'] < 0 ||
            array_diff($meta['columns'], array_keys($catalog[$dataset]['fields']))) throw new InvalidArgumentException('Manifest dataset tidak valid.');
        $state = $meta['state'];
        if ($state === 'ready') {
            if (array_diff($catalog[$dataset]['required'], $meta['columns'])) throw new InvalidArgumentException('Kolom wajib belum lengkap.');
            $rows = kasuari_sipp_select($db, 'SELECT COUNT(*) AS total FROM kasuari_sipp_stage WHERE pn_id=? AND request_id=? AND dataset=? FOR UPDATE', 'iss', array($pn, $request, $dataset));
            if ((int) $rows[0]['total'] !== $meta['count']) throw new RuntimeException('Paket ' . $dataset . ' belum lengkap.');
            $stmt = kasuari_sipp_statement($db, 'DELETE FROM kasuari_sipp_rows WHERE pn_id=? AND dataset=?', 'is', array($pn, $dataset)); mysqli_stmt_close($stmt);
            $stmt = kasuari_sipp_statement($db, 'INSERT INTO kasuari_sipp_rows (pn_id, dataset, record_key, case_id, record_date, search_text, data_json)
                SELECT pn_id, dataset, record_key, case_id, record_date, search_text, data_json FROM kasuari_sipp_stage
                WHERE pn_id=? AND request_id=? AND dataset=?', 'iss', array($pn, $request, $dataset)); mysqli_stmt_close($stmt);
        } elseif ($meta['count'] !== 0) throw new InvalidArgumentException('Dataset tidak tersedia harus tanpa record baru.');
        $message = isset($meta['message']) && is_string($meta['message']) ? substr($meta['message'], 0, 500) : '';
        $stmt = kasuari_sipp_statement($db, "INSERT INTO kasuari_sipp_state
            (pn_id, dataset, state, row_count, columns_json, message, checked_at, synced_at, request_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE state=VALUES(state),
            row_count=IF(VALUES(state)='ready', VALUES(row_count), row_count), columns_json=IF(VALUES(state)='ready', VALUES(columns_json), columns_json),
            message=VALUES(message), checked_at=VALUES(checked_at), synced_at=IF(VALUES(state)='ready', VALUES(synced_at), synced_at), request_id=VALUES(request_id)",
            'ississsss', array($pn, $dataset, $state, $meta['count'], json_encode($meta['columns']), $message, $time, $state === 'ready' ? $time : null, $request));
        mysqli_stmt_close($stmt);
    }
    $stmt = kasuari_sipp_statement($db, 'DELETE FROM kasuari_sipp_stage WHERE pn_id=? AND request_id=?', 'is', array($pn, $request)); mysqli_stmt_close($stmt);
    $stmt = kasuari_sipp_statement($db, "UPDATE kasuari_sipp_requests SET state='completed', manifest_hash=? WHERE pn_id=? AND request_id=?", 'sis', array(kasuari_sipp_manifest_hash($manifest), $pn, $request)); mysqli_stmt_close($stmt);
}

function kasuari_sipp_manifest_hash($manifest)
{
    if (!is_array($manifest)) throw new InvalidArgumentException('Manifest tidak valid.');
    ksort($manifest);
    return hash('sha256', json_encode($manifest));
}

function kasuari_sipp_lock_request($db, $pn, $request)
{
    if (!preg_match('/^[a-f0-9]{40}$/D', $request)) throw new InvalidArgumentException('Request tidak valid.');
    kasuari_sipp_select($db, 'SELECT pn_id FROM kasuari_sipp_tokens WHERE pn_id=? FOR UPDATE', 'i', array($pn));
    $stmt = kasuari_sipp_statement($db, "INSERT IGNORE INTO kasuari_sipp_requests (pn_id, request_id, state, started_at) VALUES (?, ?, 'open', NOW())", 'is', array($pn, $request)); mysqli_stmt_close($stmt);
    $rows = kasuari_sipp_select($db, 'SELECT * FROM kasuari_sipp_requests WHERE pn_id=? AND request_id=? FOR UPDATE', 'is', array($pn, $request));
    if (!$rows || $rows[0]['state'] !== 'open') throw new RuntimeException('Proses SIPP sudah ditutup. Gunakan pengiriman baru.');
    return $rows[0];
}

function kasuari_sipp_states($db, $pn)
{
    if (!kasuari_sipp_schema($db)) return array();
    $rows = kasuari_sipp_select($db, 'SELECT * FROM kasuari_sipp_state WHERE pn_id=?', 'i', array($pn));
    $map = array(); foreach ($rows as $row) $map[$row['dataset']] = $row;
    return $map;
}

function kasuari_sipp_page($db, $pn, $dataset, $filter)
{
    $catalog = kasuari_sipp_catalog();
    if (!isset($catalog[$dataset])) throw new InvalidArgumentException('Modul tidak dikenal.');
    $where = 'pn_id=? AND dataset=?'; $types = 'is'; $values = array($pn, $dataset);
    if (!empty($filter['q'])) {
        $where .= " AND search_text LIKE ? ESCAPE '!'"; $types .= 's';
        $values[] = '%' . strtr($filter['q'], array('!' => '!!', '%' => '!%', '_' => '!_')) . '%';
    }
    foreach (array('from' => '>=', 'to' => '<=') as $field => $operator) {
        if (!empty($filter[$field])) { $where .= ' AND record_date ' . $operator . ' ?'; $types .= 's'; $values[] = $filter[$field]; }
    }
    if (!empty($filter['case_id'])) { $where .= ' AND case_id=?'; $types .= 's'; $values[] = $filter['case_id']; }
    $count = kasuari_sipp_select($db, 'SELECT COUNT(*) AS total FROM kasuari_sipp_rows WHERE ' . $where, $types, $values);
    $size = min(100, max(10, (int) ($filter['size'] ?? 25)));
    $pages = max(1, (int) ceil($count[0]['total'] / $size));
    $page = min($pages, max(1, (int) ($filter['page'] ?? 1)));
    $rows = kasuari_sipp_select($db, 'SELECT record_key, case_id, data_json FROM kasuari_sipp_rows WHERE ' . $where .
        ' ORDER BY record_date DESC, LENGTH(case_id) DESC, case_id DESC, record_key LIMIT ? OFFSET ?', $types . 'ii', array_merge($values, array($size, ($page - 1) * $size)));
    foreach ($rows as &$row) $row['data'] = json_decode($row['data_json'], true);
    unset($row);
    $caseIds = array();
    foreach ($rows as $row) if ($row['case_id'] !== null) $caseIds[$row['case_id']] = (string) $row['case_id'];
    if ($caseIds) {
        $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
        $cases = kasuari_sipp_select($db, "SELECT case_id, data_json FROM kasuari_sipp_rows WHERE pn_id=? AND dataset='perkara' AND case_id IN ({$placeholders})", 'i' . str_repeat('s', count($caseIds)), array_merge(array($pn), array_values($caseIds)));
        $numbers = array();
        foreach ($cases as $case) { $data = json_decode($case['data_json'], true); $numbers[$case['case_id']] = $data['nomor_perkara'] ?? ''; }
        foreach ($rows as &$row) $row['case_number'] = $numbers[$row['case_id']] ?? '';
        unset($row);
    }
    return array('rows' => $rows, 'total' => (int) $count[0]['total'], 'page' => $page, 'pages' => $pages, 'size' => $size);
}

function kasuari_sipp_escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function kasuari_sipp_value($value, $field)
{
    if ($value === null || $value === '' || strpos((string) $value, '0000-00-00') === 0) return '-';
    if (preg_match('/^(tanggal|tgl|permohonan|putusan|penetapan|minutasi|dimulai|keputusan|pemberitahuan|pelaksanaan|pengiriman|penerimaan)/', $field)
        && preg_match('/^\d{4}-\d{2}-\d{2}$/D', (string) $value)) return kasuari_tanggal_indonesia($value);
    return trim(strip_tags(html_entity_decode(preg_replace('/<br\s*\/?\s*>/i', "\n", (string) $value), ENT_QUOTES, 'UTF-8')));
}

function kasuari_sipp_url($pn, $dataset = '', $params = array())
{
    return 'index.php?' . http_build_query(array_merge(array('modul' => 'sipp', 'satker' => $pn, 'bagian' => $dataset), $params));
}

function kasuari_sipp_statistics($db, $pn, $from, $to)
{
    $decisions = kasuari_sipp_select($db, "SELECT case_id, record_date FROM kasuari_sipp_rows WHERE pn_id=? AND dataset='putusan' ORDER BY record_date", 'i', array($pn));
    $dates = array(); foreach ($decisions as $row) if ($row['record_date']) $dates[$row['case_id']] = $row['record_date'];
    $cases = kasuari_sipp_select($db, "SELECT case_id, record_date, data_json FROM kasuari_sipp_rows WHERE pn_id=? AND dataset='perkara'", 'i', array($pn));
    $types = array();
    foreach ($cases as $case) {
        $data = json_decode($case['data_json'], true); $date = $case['record_date'];
        $type = trim($data['jenis_perkara_nama'] ?? '') ?: 'Jenis belum tercatat';
        if (!isset($types[$type])) $types[$type] = array('awal' => 0, 'masuk' => 0, 'putus' => 0, 'akhir' => 0);
        $decision = $dates[$case['case_id']] ?? null;
        if (!$date) continue;
        if ($date < $from && (!$decision || $decision >= $from)) $types[$type]['awal']++;
        if ($date >= $from && $date <= $to) $types[$type]['masuk']++;
        if ($date <= $to && $decision && $decision >= $from && $decision <= $to) $types[$type]['putus']++;
        if ($date <= $to && (!$decision || $decision > $to)) $types[$type]['akhir']++;
    }
    ksort($types);
    return array('types' => $types);
}

function kasuari_sipp_workload($db, $pn)
{
    $rows = kasuari_sipp_select($db, "SELECT dataset, case_id, data_json FROM kasuari_sipp_rows WHERE pn_id=? AND dataset IN ('hakim','pp','jurusita')", 'i', array($pn));
    $groups = array(); $fields = array('hakim' => 'hakim', 'pp' => 'panitera', 'jurusita' => 'jurusita');
    foreach ($rows as $row) {
        $data = json_decode($row['data_json'], true);
        if (!in_array(strtoupper((string) ($data['aktif'] ?? '')), array('1','Y','YA'), true)) continue;
        $field = $fields[$row['dataset']]; $id = $data[$field . '_id'] ?? ''; $name = $data[$field . '_nama'] ?? '';
        if (!$id || !$name) continue;
        $key = $row['dataset'] . ':' . $id;
        if (!isset($groups[$key])) $groups[$key] = array('role' => $row['dataset'] === 'pp' ? 'Panitera Pengganti' : ucfirst($row['dataset']), 'name' => $name, 'cases' => array());
        $groups[$key]['cases'][$row['case_id']] = true;
    }
    $result = array(); foreach ($groups as $group) $result[] = array('role' => $group['role'], 'name' => $group['name'], 'count' => count($group['cases']));
    usort($result, function ($a, $b) { return $b['count'] <=> $a['count']; });
    return $result;
}
