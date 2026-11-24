<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../../sys/sys_authorization.php';
require_once __DIR__ . '/../../sys/sys_fungsi.php';
require_once __DIR__ . '/../../sys/sys_sipp_view.php';
require_once __DIR__ . '/../../deploy/satker-sipp/sys/sys_sipp.php';
function sipp_assert($value, $label) { if (!$value) throw new RuntimeException($label); echo 'PASS: ' . $label . "\n"; }
function sipp_publish_test($db, $pn, $request, $manifest) {
    mysqli_begin_transaction($db);
    try { kasuari_sipp_publish($db, $pn, $request, $manifest, '2026-11-16 13:00:00'); mysqli_commit($db); }
    catch (Exception $error) { mysqli_rollback($db); throw $error; }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$port = (int) (getenv('KASUARI_TEST_DB_PORT') ?: 33319);
$db = mysqli_connect('127.0.0.1', 'root', '', '', $port);
$name = 'kasuari_sipp_test_' . getmypid(); $sourceName = $name . '_source';
mysqli_query($db, 'CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4');
mysqli_query($db, 'CREATE DATABASE `' . $sourceName . '` CHARACTER SET utf8mb4');
mysqli_select_db($db, $name); mysqli_set_charset($db, 'utf8mb4');
$source = mysqli_connect('127.0.0.1', 'root', '', $sourceName, $port); mysqli_set_charset($source, 'utf8mb4');
$writer = mysqli_connect('127.0.0.1', 'root', '', $sourceName, $port);
try {
    sipp_assert(kasuari_sipp_schema($db, true) && kasuari_sipp_schema($db, true), 'Repeatable isolated schema migration.');
    $catalog = kasuari_sipp_catalog();
    sipp_assert(count($catalog) === 48, '48 explicit datasets.');
    $zeroDate = kasuariSippNormalizeRow(array('perkara_id'=>'10','tanggal_putusan'=>'0000-00-00','tanggal_minutasi'=>'0000-00-00'), array('columns'=>array('perkara_id','tanggal_putusan','tanggal_minutasi'),'keys'=>array('perkara_id','tanggal_putusan'),'dates'=>array('tanggal_putusan','tanggal_minutasi')));
    sipp_assert($zeroDate['tanggal_putusan'] === '0000-00-00' && $zeroDate['tanggal_minutasi'] === null && kasuari_sipp_record('putusan',$zeroDate)[2] === null, 'Legacy zero-date key preserves source identity without inventing a valid event date.');
    $token = kasuari_sipp_issue_token($db, 439, 1);
    sipp_assert(kasuari_sipp_auth($db, 439, $token) && !kasuari_sipp_auth($db, 431, $token), 'Token is scoped to one court.');
    foreach ($catalog as $key => $spec) {
        $definitions = array();
        foreach ($spec['fields'] as $field => $label) $definitions[] = '`' . $field . '` ' . (strpos($field, 'tanggal') !== false || strpos($field, 'tgl_') === 0 || $field === $spec['date'] ? 'DATE' : 'TEXT') . ' NULL';
        mysqli_query($source, 'CREATE TABLE `' . $spec['table'] . '` (' . implode(',', $definitions) . ') ENGINE=InnoDB');
        $row = array_fill_keys(array_keys($spec['fields']), null);
        foreach ($spec['keys'] as $field) $row[$field] = strpos($field, 'tanggal') !== false || $field === 'tanggal' ? '2026-10-15' : '1';
        if ($spec['case_field']) $row[$spec['case_field']] = '10';
        if ($spec['date']) $row[$spec['date']] = '2026-10-15';
        if (isset($spec['fields']['nomor_perkara'])) $row['nomor_perkara'] = '10/Pdt.G/2026/PA.Test';
        if (isset($spec['fields']['jenis_perkara_nama'])) $row['jenis_perkara_nama'] = 'Cerai Gugat';
        if (isset($spec['fields']['aktif'])) $row['aktif'] = '1';
        foreach (array('hakim', 'panitera', 'jurusita') as $role) {
            if (isset($spec['fields'][$role . '_id'])) $row[$role . '_id'] = '1';
            if (isset($spec['fields'][$role . '_nama'])) $row[$role . '_nama'] = 'Petugas Uji';
        }
        $columns = implode(',', array_map(function ($x) { return '`' . $x . '`'; }, array_keys($row)));
        $values = implode(',', array_map(function ($x) use ($source) { return $x === null ? 'NULL' : "'" . mysqli_real_escape_string($source, $x) . "'"; }, $row));
        mysqli_query($source, 'INSERT INTO `' . $spec['table'] . '` (' . $columns . ') VALUES (' . $values . ')');
    }
    for ($i=2; $i<=302; $i++) mysqli_query($source, "INSERT INTO perkara_biaya (id,perkara_id,tanggal_transaksi,jumlah) VALUES ('" . $i . "','10','2026-10-15','15000')");
    mysqli_query($source, "INSERT INTO dirput_dokumen (id,perkara_id,general_id,dokumen_ref_id,pihak_detil_id,pengajuan_id) VALUES ('1','10','1','1','2','1')");
    mysqli_query($source, 'DROP TABLE mediator');
    mysqli_query($source, 'ALTER TABLE perkara_jadwal_sidang DROP COLUMN ruangan');
    putenv('KASUARI_SIPP_TOKEN=' . $token);
    $request = str_repeat('c', 40); $batchCount = 0; $sourceChanged = false;
    $transport = function ($endpoint, $payload, $options) use ($db, $writer, &$batchCount, &$sourceChanged) {
        if ($payload['action'] === 'sipp_begin') {
            mysqli_begin_transaction($db);
            kasuari_sipp_lock_request($db, (int) $payload['pn_id'], $payload['request_id']);
            mysqli_commit($db);
            return array('status' => 200, 'body' => 'SIPP_BEGIN_OK');
        }
        sipp_assert(kasuari_sipp_auth($db, (int) $payload['pn_id'], $payload['sipp_token']), 'Batch authentication.');
        sipp_assert(hash('sha256', $payload['rows_json']) === $payload['rows_hash'], 'Batch checksum.');
        $rows = json_decode($payload['rows_json'], true);
        sipp_assert(count($rows) <= 100, 'Bounded record batch.');
        if (!$sourceChanged && $payload['dataset'] === 'perkara') {
            mysqli_query($writer, "UPDATE perkara_putusan SET tanggal_putusan='2026-10-16'");
            $sourceChanged = true;
        }
        mysqli_begin_transaction($db);
        try { kasuari_sipp_stage($db, (int) $payload['pn_id'], $payload['request_id'], $payload['dataset'], $rows); mysqli_commit($db); }
        catch (Exception $error) { mysqli_rollback($db); throw $error; }
        $batchCount++;
        return array('status' => 200, 'body' => 'SIPP_BATCH_OK');
    };
    $result = kasuariSippSync($source, 'https://example.test/sync', '439', $request, array('sipp_portal' => 1), 65536, $transport);
    sipp_assert(!isset($result['error']) && $result['included'] && count($result['manifest']) === 48, 'Client sends a complete catalog manifest.');
    sipp_assert($result['manifest']['ref_mediator']['state'] === 'unavailable', 'Absent optional source table is not zero activity.');
    sipp_assert(!in_array('ruangan', $result['manifest']['jadwal']['columns'], true), 'Missing optional column is explicit.');
    sipp_assert(count(kasuari_sipp_states($db, 439)) === 0, 'Staged records hidden before finalize.');
    $bad = $result['manifest']; $bad['perkara']['count']++;
    $failed = false; try { sipp_publish_test($db, 439, $request, $bad); } catch (Exception $error) { $failed = true; }
    sipp_assert($failed && count(kasuari_sipp_states($db, 439)) === 0, 'Incomplete manifest rolls back every dataset.');
    sipp_publish_test($db, 439, $request, $result['manifest']);
    $states = kasuari_sipp_states($db, 439);
    sipp_assert(count($states) === 48 && $states['perkara']['row_count'] == 1, 'Complete snapshot publishes all modules.');
    sipp_assert((int) $states['biaya']['row_count'] === 302, 'Multi-batch streaming preserves all financial rows.');
    sipp_assert((int) $states['publikasi']['row_count'] === 2, 'Publication composite key preserves records with identical id.');
    $decisions = kasuari_sipp_select($db, "SELECT record_date FROM kasuari_sipp_rows WHERE pn_id=439 AND dataset='putusan'");
    sipp_assert($sourceChanged && $decisions[0]['record_date'] === '2026-10-15', 'Source changes during upload do not mix snapshot versions.');
    $failed = false;
    try { kasuari_sipp_stage($db, 439, $request, 'perkara', array(array('perkara_id' => 10))); } catch (RuntimeException $error) { $failed = true; }
    sipp_assert($failed, 'Completed request cannot accept more batches.');
    $older = str_repeat('d', 40); $newer = str_repeat('e', 40);
    kasuari_sipp_stage($db, 439, $older, 'perkara', array(array('perkara_id' => 10, 'nomor_perkara' => 'OLD')));
    kasuari_sipp_stage($db, 439, $newer, 'perkara', array(array('perkara_id' => 10, 'nomor_perkara' => 'NEW')));
    $smallManifest = array();
    foreach ($catalog as $dataset => $spec) $smallManifest[$dataset] = array('state'=>'unavailable','count'=>0,'columns'=>array(),'message'=>'Fixture');
    $smallManifest['perkara'] = array('state'=>'ready','count'=>1,'columns'=>array('perkara_id','nomor_perkara'),'message'=>'');
    sipp_publish_test($db, 439, $newer, $smallManifest);
    $failed = false; try { sipp_publish_test($db, 439, $older, $smallManifest); } catch (RuntimeException $error) { $failed = true; }
    sipp_assert($failed, 'Late older snapshot cannot overwrite newer generation.');
    // Restore the complete initial fixture under a new request.
    $request = str_repeat('f', 40);
    $restore = kasuariSippSync($source, 'https://example.test/sync', '439', $request, array('sipp_portal'=>1), 65536, $transport);
    sipp_publish_test($db, 439, $request, $restore['manifest']);
    $filter = array('q' => '', 'from' => '', 'to' => '', 'case_id' => '', 'page' => 1, 'size' => 25);
    $page = kasuari_sipp_page($db, 439, 'perkara', $filter);
    sipp_assert($page['total'] === 1 && kasuari_sipp_page($db, 431, 'perkara', $filter)['total'] === 0, 'Every lookup is isolated by court.');
    $filter['q'] = "' OR 1=1 --";
    sipp_assert(kasuari_sipp_page($db, 439, 'perkara', $filter)['total'] === 0, 'Search injection treated as text.');
    $filter['q'] = '%'; sipp_assert(kasuari_sipp_page($db, 439, 'perkara', $filter)['total'] === 0, 'Wildcard search is literal.');
    $filter['q'] = ''; $filter['from'] = '2026-11-01'; sipp_assert(kasuari_sipp_page($db, 439, 'perkara', $filter)['total'] === 0, 'Date range filters the dataset date.');
    $filter['from'] = ''; $filter['page'] = 10000; sipp_assert(kasuari_sipp_page($db, 439, 'perkara', $filter)['page'] === 1, 'Out-of-range pages clamp.');
    $stats = kasuari_sipp_statistics($db, 439, '2026-10-01', '2026-10-31');
    sipp_assert($stats['types']['Cerai Gugat'] === array('awal'=>0,'masuk'=>1,'putus'=>1,'akhir'=>0), 'Historical report computes date-based balance.');
    sipp_assert(count(kasuari_sipp_workload($db, 439)) === 3, 'Active assignments counted by distinct case and person.');
    $failed = false; try { kasuari_sipp_record('perkara', array('perkara_id' => 1, 'password' => 'secret')); } catch (InvalidArgumentException $error) { $failed = true; }
    sipp_assert($failed, 'Unlisted credential columns cannot enter storage.');
    $noToken = kasuariSippSync(null, 'https://example.test/sync', '439', $request, array(), 65536, $transport);
    sipp_assert(!$noToken['included'] && $noToken['warning'] !== '', 'Old centre is skipped with explicit warning.');
    $_SESSION['sipp_csrf'] = 'test-csrf';
    ob_start(); kasuari_sipp_render($db, 439, '', $filter); $html = ob_get_clean();
    sipp_assert(strpos($html, 'Jurnal Biaya Perkara') !== false && strpos($html, 'PA Sorong') !== false, 'Overview includes complete dataset navigation.');
    ob_start(); kasuari_sipp_render($db, 439, 'perkara', $filter, $page['rows'][0]['record_key']); $html = ob_get_clean();
    sipp_assert(strpos($html, 'Riwayat dan data terkait') !== false, 'Record detail includes cross-module timeline.');
    sipp_assert(substr_count(kasuari_sipp_navigation(439, 'perkara'), 'Koneksi satker') === 4, 'Sidebar contains each of four satker trees.');
    $receiver = mysqli_connect('127.0.0.1','root','',$name,$port);
    mysqli_begin_transaction($db);
    kasuari_sipp_select($db, 'SELECT COUNT(*) AS total FROM kasuari_sipp_stage');
    mysqli_begin_transaction($receiver);
    $concurrentRequest = str_repeat('1',40);
    kasuari_sipp_stage($receiver,439,$concurrentRequest,'perkara',array(array('perkara_id'=>10,'nomor_perkara'=>'CURRENT')));
    mysqli_commit($receiver);
    kasuari_sipp_publish($db,439,$concurrentRequest,$smallManifest,'2026-11-16 13:00:00');
    mysqli_commit($db); mysqli_close($receiver);
    sipp_assert(kasuari_sipp_page($db,439,'perkara',array())['rows'][0]['data']['nomor_perkara'] === 'CURRENT', 'Finalization count reads current committed stage despite an earlier read view.');
    $newToken = kasuari_sipp_issue_token($db, 439, 1);
    sipp_assert(!kasuari_sipp_auth($db, 439, $token) && kasuari_sipp_auth($db, 439, $newToken), 'Rotating token revokes previous token.');
    echo "ALL SIPP PORTAL CHECKS PASSED\n";
} finally {
    putenv('KASUARI_SIPP_TOKEN');
    mysqli_close($writer);
    mysqli_close($source);
    mysqli_query($db, 'DROP DATABASE `' . $sourceName . '`'); mysqli_query($db, 'DROP DATABASE `' . $name . '`'); mysqli_close($db);
}
