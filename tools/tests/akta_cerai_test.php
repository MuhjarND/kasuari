<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../../sys/sys_authorization.php';
require_once __DIR__ . '/../../sys/sys_fungsi.php';
require_once __DIR__ . '/../../sys/sys_akta_cerai.php';
require_once __DIR__ . '/../../deploy/satker-akta-cerai/sys/sys_akta_cerai.php';

function akta_assert($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS: ' . $message . "\n";
}
function akta_publish_test($db, $pn, $request, $count, $time) {
    mysqli_begin_transaction($db);
    try {
        kasuari_akta_publish($db, $pn, $request, $count, $time);
        mysqli_commit($db);
    } catch (Exception $error) {
        mysqli_rollback($db);
        throw $error;
    }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = mysqli_connect('127.0.0.1', 'root', '', '', 3306);
$name = 'kasuari_akta_test_' . getmypid();
mysqli_query($db, 'CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4');
mysqli_select_db($db, $name);
mysqli_set_charset($db, 'utf8mb4');
try {
    $_SESSION['group'] = 'administrator';
    $cases = array(
        array('pn_id' => 439, 'perkara_id' => 10, 'jenis_perkara_nama' => 'Cerai Gugat'),
        array('pn_id' => 431, 'perkara_id' => 10, 'jenis_perkara_nama' => 'Cerai Talak'),
        array('pn_id' => 429, 'perkara_id' => 10, 'jenis_perkara_nama' => 'Cerai Gugat'),
        array('pn_id' => 844, 'perkara_id' => 10, 'jenis_perkara_nama' => 'Cerai Gugat'),
        array('pn_id' => 439, 'perkara_id' => 999, 'jenis_perkara_nama' => 'Perwalian')
    );
    akta_assert(kasuari_akta_load($db, array()) === array(), 'Empty pages make no lookup.');
    akta_assert(kasuari_akta_schema_ready($db, true), 'Migration creates new InnoDB tables.');
    akta_assert(kasuari_akta_schema_ready($db, true), 'Migration is repeatable.');
    $row = array('perkara_id' => '10', 'nomor_akta_cerai' => '10/AC/2026/PA.Test', 'tgl_akta_cerai' => '2026-10-31',
        'tgl_penyerahan_akta_cerai' => '2026-11-01', 'tgl_penyerahan_akta_cerai_pihak2' => null, 'pihak2_tersedia' => 1);
    $request = str_repeat('a', 40);
    kasuari_akta_stage_rows($db, 439, $request, array($row));
    kasuari_akta_stage_rows($db, 439, $request, array($row));
    akta_assert(kasuari_akta_load($db, $cases)['439:10']['state'] === 'unavailable', 'Staged data stays hidden before finalize, including retries.');
    $failed = false;
    try { akta_publish_test($db, 439, $request, 2, '2026-11-01 12:00:00'); } catch (RuntimeException $error) { $failed = true; }
    akta_assert($failed && kasuari_akta_load($db, $cases)['439:10']['state'] === 'unavailable', 'Incomplete snapshot cannot publish.');
    akta_publish_test($db, 439, $request, 1, '2026-11-01 12:00:00');
    $other = $row; $other['nomor_akta_cerai'] = 'OTHER-COURT'; $other['pihak2_tersedia'] = 0;
    kasuari_akta_stage_rows($db, 431, $request, array($other));
    akta_publish_test($db, 431, $request, 1, '2026-11-01 13:00:00');
    akta_publish_test($db, 429, $request, 0, '2026-11-01 14:00:00');
    $map = kasuari_akta_load($db, $cases);
    akta_assert($map['439:10']['nomor_akta_cerai'] === $row['nomor_akta_cerai'] && $map['431:10']['nomor_akta_cerai'] === 'OTHER-COURT', 'Identical case IDs stay scoped to each satker.');
    akta_assert($map['429:10']['state'] === 'not_recorded' && $map['844:10']['state'] === 'unavailable', 'Empty completed snapshot differs from never synchronized.');
    $detail = kasuari_akta_html($cases[0], $map, true);
    akta_assert(strpos($detail, 'Akta terbit tercatat') !== false && strpos($detail, '01/11/2026 12:00:00 WIT') !== false, 'Detail shows issuance and actual WIT sync timestamp.');
    akta_assert(strpos($detail, 'Penyerahan pihak 1') !== false && strpos($detail, 'Belum tercatat di SIPP') !== false && strpos($detail, 'belum diunduh') === false, 'Each party is independent; missing delivery does not imply EAC state.');
    akta_assert(strpos(kasuari_akta_html($cases[1], $map, true), 'Tidak tersedia pada SIPP sumber') !== false, 'Missing source field is explicitly unknown.');
    akta_assert(kasuari_akta_html($cases[4], $map, true) === '', 'Non-divorce case with no akta has no extra status.');
    $incomplete = $map['439:10']; $incomplete['tgl_akta_cerai'] = null;
    akta_assert(kasuari_akta_view($incomplete)['tone'] === 'warning', 'Partial issuance is not labelled published.');
    $incomplete['nomor_akta_cerai'] = '<script>alert(1)</script>';
    $map['439:10'] = $incomplete;
    akta_assert(strpos(kasuari_akta_html($cases[0], $map, true), '<script>') === false, 'SIPP values are HTML escaped.');
    $_SESSION['group'] = '1';
    akta_assert(kasuari_akta_load($db, $cases) === array() && kasuari_akta_html($cases[0], $map, true) === '', 'Non-admin receives neither records nor rendered metadata.');
    $_SESSION['group'] = '0';
    $invalidRows = array(array_merge($row, array('pn_id' => 431)), array_merge($row, array('tgl_akta_cerai' => '2026-02-31')),
        array_merge($row, array('perkara_id' => '1 OR 1=1')), array_merge($row, array('nomor_akta_cerai' => str_repeat('x', 256))));
    foreach ($invalidRows as $invalid) {
        $failed = false;
        try { kasuari_akta_validate_row($invalid); } catch (InvalidArgumentException $error) { $failed = true; }
        akta_assert($failed, 'Invalid dates, identifiers, extra columns, or oversized fields rejected.');
    }
    $newRequest = str_repeat('b', 40);
    $row['nomor_akta_cerai'] = 'CORRECTED';
    kasuari_akta_stage_rows($db, 439, $newRequest, array($row));
    akta_assert(kasuari_akta_load($db, $cases)['439:10']['nomor_akta_cerai'] !== 'CORRECTED', 'Interrupted replacement preserves previous complete snapshot.');
    akta_publish_test($db, 439, $newRequest, 1, '2026-11-01 15:00:00');
    akta_assert(kasuari_akta_load($db, $cases)['439:10']['nomor_akta_cerai'] === 'CORRECTED', 'Resync publishes corrected metadata without duplicates.');

    mysqli_query($db, "CREATE TABLE perkara_akta_cerai (perkara_id BIGINT PRIMARY KEY, nomor_akta_cerai VARCHAR(255),
        tgl_akta_cerai DATE, tgl_penyerahan_akta_cerai DATE, tgl_penyerahan_akta_cerai_pihak2 DATE)");
    mysqli_query($db, "INSERT INTO perkara_akta_cerai VALUES (10,'A/2026','2026-10-31',NULL,NULL)");
    $plan = kasuariAktaSourcePlan($db);
    $before = mysqli_fetch_assoc(mysqli_query($db, $plan['snapshot']));
    $payload = kasuariAktaBuildPayloads($db, 65536);
    $decoded = json_decode($payload['batches'][0], true);
    akta_assert($payload['rows'] === 1 && $decoded[0]['perkara_id'] === '10', 'Client reads all source akta records without banding/year filter.');
    akta_assert(!array_diff(array_keys($decoded[0]), array_keys($row)), 'Payload excludes document paths and unrelated PII.');
    mysqli_query($db, "UPDATE perkara_akta_cerai SET tgl_penyerahan_akta_cerai_pihak2='2026-11-01'");
    $after = mysqli_fetch_assoc(mysqli_query($db, $plan['snapshot']));
    akta_assert($before['checksum'] !== $after['checksum'], 'Delivery-only changes participate in monitoring fingerprint.');
    mysqli_query($db, 'ALTER TABLE perkara_akta_cerai DROP COLUMN tgl_penyerahan_akta_cerai_pihak2');
    $payload = kasuariAktaBuildPayloads($db, 65536);
    $decoded = json_decode($payload['batches'][0], true);
    akta_assert((int) $decoded[0]['pihak2_tersedia'] === 0 && $decoded[0]['tgl_penyerahan_akta_cerai_pihak2'] === null, 'Older SIPP schema never fabricates party-two delivery.');
    mysqli_query($db, 'DELETE FROM perkara_akta_cerai');
    $payload = kasuariAktaBuildPayloads($db, 65536);
    akta_assert($payload['rows'] === 0 && $payload['batches'] === array(), 'Empty SIPP table is a valid complete snapshot.');
    echo "ALL AKTA CERAI CHECKS PASSED (isolated MySQL database only)\n";
} finally {
    mysqli_query($db, 'DROP DATABASE `' . $name . '`');
    mysqli_close($db);
}
