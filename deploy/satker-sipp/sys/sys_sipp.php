<?php
// Read-only expanded SIPP client, compatible with PHP 5.6.
require_once __DIR__ . '/sys_transport.php';
require_once __DIR__ . '/sys_akta_cerai.php';

function kasuariSippCatalogLoad()
{
    if (!function_exists('kasuari_sipp_catalog') && !function_exists('kasuariSippCatalog')) {
        $path = __DIR__ . '/sys_sipp_catalog.php';
        if (!is_file($path)) return array('error' => 'Katalog sinkronisasi SIPP belum tersedia.');
        require_once $path;
    }
    if (!function_exists('kasuari_sipp_catalog') && !function_exists('kasuariSippCatalog')) {
        return array('error' => 'Fungsi katalog sinkronisasi SIPP tidak tersedia.');
    }
    $catalog = function_exists('kasuari_sipp_catalog') ? kasuari_sipp_catalog() : kasuariSippCatalog();
    if (!is_array($catalog) || !$catalog) return array('error' => 'Katalog sinkronisasi SIPP tidak valid.');
    return array('catalog' => $catalog);
}

function kasuariSippToken()
{
    $token = getenv('KASUARI_SIPP_TOKEN');
    $token = $token === false ? '' : trim($token);
    if ($token === '') {
        $path = __DIR__ . '/sys_sipp.local.php';
        if (is_file($path)) {
            $config = require $path;
            if (is_array($config) && isset($config['token'])) $token = trim((string) $config['token']);
        }
    }
    return preg_match('/^[a-f0-9]{64}$/i', $token) ? $token : '';
}

function kasuariSippIdentifier($name)
{
    return is_string($name) && preg_match('/^[a-z0-9_]+$/i', $name);
}

function kasuariSippQuery($connection, $sql, $stream = false)
{
    try {
        return @mysqli_query($connection, $sql, $stream ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT);
    } catch (Exception $exception) {
        return false;
    }
}

function kasuariSippSourcePlan($connection, $key, $dataset)
{
    if (!kasuariSippIdentifier($key) || !is_array($dataset) || !isset($dataset['table'], $dataset['fields'], $dataset['keys'])
        || !kasuariSippIdentifier($dataset['table']) || !is_array($dataset['fields']) || !$dataset['fields']
        || !is_array($dataset['keys']) || !$dataset['keys']) {
        return array('error' => 'Definisi katalog SIPP tidak valid.');
    }
    $fields = array_keys($dataset['fields']);
    foreach ($fields as $field) {
        if (!kasuariSippIdentifier($field)) return array('error' => 'Nama kolom katalog SIPP tidak valid.');
    }
    $required = isset($dataset['required']) ? $dataset['required'] : array();
    if (!is_array($required)) return array('error' => 'Kolom wajib katalog SIPP tidak valid.');
    $required = array_unique(array_merge($required, $dataset['keys']));
    foreach ($required as $field) {
        if (!in_array($field, $fields, true)) return array('error' => 'Kolom wajib/kunci SIPP tidak tercantum dalam fields: ' . $key . '.');
    }
    // For non-ready states count is the number sent, not a claimed source count.
    $entry = array('state' => 'unavailable', 'count' => 0, 'columns' => array(), 'message' => '');
    $result = kasuariSippQuery($connection, 'SHOW COLUMNS FROM `' . $dataset['table'] . '`');
    if (!$result) {
        if ((int) mysqli_errno($connection) !== 1146) {
            return array('error' => 'Gagal membaca struktur sumber SIPP ' . $key . '.');
        }
        $entry['message'] = 'Tabel sumber ' . $dataset['table'] . ' tidak tersedia pada SIPP lokal.';
        return array('manifest' => $entry);
    }
    $available = array();
    try {
        while (true) {
            $row = mysqli_fetch_assoc($result);
            if (!is_array($row)) break;
            $available[$row['Field']] = $row['Type'];
        }
    } catch (Exception $exception) {
        mysqli_free_result($result);
        return array('error' => 'Gagal membaca struktur sumber SIPP ' . $key . '.');
    }
    $readError = $row === false || mysqli_errno($connection);
    mysqli_free_result($result);
    if ($readError) return array('error' => 'Gagal membaca struktur sumber SIPP ' . $key . '.');
    $columns = array();
    $dates = array();
    $dateFields = isset($dataset['date']) ? (array) $dataset['date'] : array();
    foreach ($fields as $field) {
        if (!isset($available[$field])) continue;
        $columns[] = $field;
        if (preg_match('/^(date|datetime|timestamp)\b/i', $available[$field]) || in_array($field, $dateFields, true)) {
            $dates[] = $field;
        }
    }
    $entry['columns'] = $columns;
    $missing = array_values(array_diff($required, $columns));
    if ($missing) {
        $entry['state'] = 'missing_columns';
        $entry['message'] = 'Kolom wajib sumber ' . $dataset['table'] . ' belum tersedia: ' . implode(', ', $missing) . '.';
        return array('manifest' => $entry);
    }
    $quoted = array();
    foreach ($columns as $column) $quoted[] = '`' . $column . '`';
    $order = array();
    foreach ($dataset['keys'] as $column) $order[] = '`' . $column . '`';
    $entry['state'] = 'ready';
    $entry['count'] = 0;
    return array('manifest' => $entry, 'columns' => $columns, 'dates' => $dates, 'keys' => $dataset['keys'],
        'table' => $dataset['table'],
        'query' => 'SELECT ' . implode(', ', $quoted) . ' FROM `' . $dataset['table'] . '` ORDER BY ' . implode(', ', $order));
}

function kasuariSippNormalizeRow($row, $plan)
{
    $normalized = array();
    foreach ($plan['columns'] as $column) {
        $value = isset($row[$column]) ? (string) $row[$column] : null;
        if (in_array($column, $plan['dates'], true)
            && !in_array($column, $plan['keys'], true)
            && ($value === '' || strpos((string) $value, '0000-00-00') === 0)) $value = null;
        $normalized[$column] = $value;
    }
    return $normalized;
}

// The injected callable receives (endpoint, POST fields, HTTP settings).
function kasuariSippSendBatch($endpoint, $pnId, $requestId, $token, $key, $rowsJson, $transport)
{
    $payload = array('action' => 'sipp_batch', 'protocol' => 2, 'pn_id' => $pnId,
        'request_id' => $requestId, 'sipp_token' => $token, 'dataset' => $key,
        'rows_json' => $rowsJson, 'rows_hash' => hash('sha256', $rowsJson));
    try {
        $response = call_user_func($transport, $endpoint, $payload, array('attempts' => 3, 'timeout' => 180));
    } catch (Exception $exception) {
        return array('error' => 'Pengiriman SIPP ' . $key . ' gagal.');
    }
    if (!is_array($response) || !isset($response['status'], $response['body'])
        || (int) $response['status'] !== 200 || $response['body'] !== 'SIPP_BATCH_OK') {
        // Never echo an untrusted response: it could reflect the authentication token.
        return array('error' => 'Pengiriman SIPP ' . $key . ' gagal; pusat tidak mengonfirmasi SIPP_BATCH_OK.');
    }
    return array();
}

function kasuariSippSyncSnapshot($connection, $endpoint, $pnId, $requestId, $health, $maxBytes, $transport = null)
{
    $sync = array('included' => false, 'manifest' => array(), 'warning' => '');
    if (PHP_SAPI === 'cli' && getenv('KASUARI_SYNC_DRY_RUN') === '1') return $sync;
    $warnings = array();
    if (!isset($health['sipp_portal']) || (int) $health['sipp_portal'] !== 1) {
        $warnings[] = 'kapabilitas sipp_portal belum tersedia di pusat';
    }
    $token = kasuariSippToken();
    if ($token === '') $warnings[] = 'token SIPP belum dikonfigurasi atau tidak valid';
    if ($warnings) {
        $sync['warning'] = 'Peringatan: sinkronisasi SIPP tambahan dilewati karena ' . implode('; ', $warnings) . '.';
        return $sync;
    }
    if (!($connection instanceof mysqli)) return array('error' => 'Database SIPP lokal belum terhubung.');
    $loaded = kasuariSippCatalogLoad();
    if (isset($loaded['error'])) return $loaded;
    if ($transport === null) $transport = 'kasuariSatkerHttpRequest';
    if (!is_callable($transport)) return array('error' => 'Transport sinkronisasi SIPP tidak tersedia.');
    $maxBytes = (int) $maxBytes;
    if ($maxBytes < 2) return array('error' => 'Batas ukuran paket SIPP tidak valid.');
    foreach ($loaded['catalog'] as $key => $dataset) {
        $plan = kasuariSippSourcePlan($connection, $key, $dataset);
        if (isset($plan['error'])) return $plan;
        $sync['manifest'][$key] = $plan['manifest'];
        if ($plan['manifest']['state'] !== 'ready') continue;
        // Unbuffered reads keep memory bounded even for large datasets. Do not query
        // this connection again until the cursor is freed; HTTP uses no DB handle.
        $result = kasuariSippQuery($connection, $plan['query'], true);
        if (!$result) return array('error' => 'Gagal membaca data sumber SIPP ' . $key . '.');
        $rows = array();
        $bytes = 2;
        $count = 0;
        $error = null;
        while (true) {
            try {
                $row = mysqli_fetch_assoc($result);
            } catch (Exception $exception) {
                $error = array('error' => 'Gagal membaca data sumber SIPP ' . $key . '.');
                break;
            }
            if (!is_array($row)) {
                if ($row === false || mysqli_errno($connection)) $error = array('error' => 'Gagal membaca data sumber SIPP ' . $key . '.');
                break;
            }
            $row = kasuariSippNormalizeRow($row, $plan);
            foreach ($plan['keys'] as $column) {
                if ($row[$column] === null || trim($row[$column]) === '') {
                    $error = array('error' => 'Identitas sumber SIPP ' . $key . ' pada kolom ' . $column . ' kosong.');
                    break;
                }
            }
            if ($error !== null) break;
            $json = json_encode($row);
            if ($json === false) {
                $error = array('error' => 'JSON SIPP ' . $key . ' tidak valid; periksa karakter UTF-8 sumber (kode ' . json_last_error() . ').');
                break;
            }
            if (strlen($json) + 2 > $maxBytes) {
                $error = array('error' => 'Satu record SIPP ' . $key . ' melebihi batas ukuran paket pusat.');
                break;
            }
            $addedBytes = strlen($json) + (count($rows) ? 1 : 0);
            if ($rows && (count($rows) >= 100 || $bytes + $addedBytes > $maxBytes)) {
                $sent = kasuariSippSendBatch($endpoint, $pnId, $requestId, $token, $key, '[' . implode(',', $rows) . ']', $transport);
                if (isset($sent['error'])) { $error = $sent; break; }
                $rows = array();
                $bytes = 2;
                $addedBytes = strlen($json);
            }
            $rows[] = $json;
            $bytes += $addedBytes;
            $count++;
        }
        mysqli_free_result($result);
        if ($error !== null) return $error;
        if ($rows) {
            $sent = kasuariSippSendBatch($endpoint, $pnId, $requestId, $token, $key, '[' . implode(',', $rows) . ']', $transport);
            if (isset($sent['error'])) return $sent;
        }
        $sync['manifest'][$key]['count'] = $count;
    }
    $sync['included'] = true;
    $sync['token'] = $token;
    return $sync;
}

function kasuariSippSync($connection, $endpoint, $pnId, $requestId, $health, $maxBytes, $transport = null)
{
    if (!($connection instanceof mysqli) || empty($health['sipp_portal']) || kasuariSippToken() === ''
        || (PHP_SAPI === 'cli' && getenv('KASUARI_SYNC_DRY_RUN') === '1')) {
        return kasuariSippSyncSnapshot($connection, $endpoint, $pnId, $requestId, $health, $maxBytes, $transport);
    }
    if ($transport === null) $transport = 'kasuariSatkerHttpRequest';
    $begin = call_user_func($transport, $endpoint, array('action' => 'sipp_begin', 'protocol' => 2,
        'pn_id' => $pnId, 'request_id' => $requestId, 'sipp_token' => kasuariSippToken()), array('attempts' => 3, 'timeout' => 30));
    if (!is_array($begin) || !isset($begin['status'], $begin['body']) || (int) $begin['status'] !== 200 || $begin['body'] !== 'SIPP_BEGIN_OK') {
        return array('error' => 'Pusat belum dapat membuka proses snapshot SIPP.');
    }
    if (!kasuariSippQuery($connection, 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')
        || !kasuariSippQuery($connection, 'START TRANSACTION WITH CONSISTENT SNAPSHOT')) {
        return array('error' => 'Snapshot konsisten SIPP tidak dapat dimulai.');
    }
    try {
        $result = kasuariSippSyncSnapshot($connection, $endpoint, $pnId, $requestId, $health, $maxBytes, $transport);
        if (isset($result['error'])) { mysqli_rollback($connection); return $result; }
        if (!mysqli_commit($connection)) return array('error' => 'Snapshot SIPP belum dapat diselesaikan.');
        return $result;
    } catch (Exception $error) {
        mysqli_rollback($connection);
        return array('error' => 'Pembacaan snapshot SIPP gagal.');
    }
}

function kasuariSippSnapshotPlans($connection)
{
    if (kasuariSippToken() === '') return array('queries' => array(), 'components' => array());
    $loaded = kasuariSippCatalogLoad();
    $plans = array('queries' => array(), 'components' => array());
    if (isset($loaded['error'])) {
        $plans['components']['sipp_catalog'] = array('state' => 'unavailable', 'message' => $loaded['error']);
        return $plans;
    }
    foreach ($loaded['catalog'] as $key => $dataset) {
        $plan = kasuariSippSourcePlan($connection, $key, $dataset);
        if (isset($plan['error'])) return $plan;
        $name = 'sipp_' . $key;
        if ($plan['manifest']['state'] !== 'ready') {
            $plans['components'][$name] = $plan['manifest'];
            continue;
        }
        $dates = array();
        foreach ($plan['dates'] as $column) {
            $dates[] = "IFNULL(NULLIF(NULLIF(`" . $column . "`, '0000-00-00'), '0000-00-00 00:00:00'), '1000-01-01 00:00:00')";
        }
        $changed = 'NULL';
        if (count($dates) === 1) $changed = 'MAX(' . $dates[0] . ')';
        elseif ($dates) $changed = 'MAX(GREATEST(' . implode(', ', $dates) . '))';
        $plans['queries'][$name] = 'SELECT COUNT(*) AS jumlah, ' . $changed . ' AS berubah, '
            . kasuariSnapshotChecksum($plan['columns']) . ' AS checksum FROM `' . $plan['table'] . '`';
        $plans['components'][$name] = array('state' => 'ready', 'columns' => $plan['columns']);
    }
    return $plans;
}
