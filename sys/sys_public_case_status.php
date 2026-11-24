<?php
// Deliberately independent of the legacy sync/session helpers: this service performs SELECTs only.
if (is_file(__DIR__.'/sys_akta_cerai.php')) require_once __DIR__.'/sys_akta_cerai.php';
function kasuari_public_case_number($number)
{
    return is_string($number) ? strtoupper(preg_replace('/\s+/u', '', trim($number))) : '';
}

function kasuari_public_case_number_valid($number)
{
    return is_string($number) && preg_match('/\A[0-9]{1,7}\/[A-Z][A-Z0-9.()-]{0,24}\/(?:19|20)[0-9]{2}\/PA\.[A-Z0-9]{2,12}\z/', $number) === 1;
}

function kasuari_public_court_key($name)
{
    $name = preg_replace('/\A(?:pengadilan\s+agama|pa[.\s]+)\s*/i', '', trim($name));
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
}

function kasuari_public_date($date)
{
    if (!is_string($date) || !preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $date, $m)
        || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
    return $date;
}

function kasuari_public_text($text, $max)
{
    if (!is_string($text) || trim($text) === '') return null;
    return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($text))), 0, $max);
}

function kasuari_public_sync_time(PDO $db, $courtId)
{
    // Do not call schema-creation/backfill helpers from the public read path.
    $value = null;
    try {
        $query = $db->prepare('SELECT last_sync_at FROM sync_monitoring WHERE pn_id = ?');
        $query->execute(array($courtId));
        $value = $query->fetchColumn();
    } catch (PDOException $e) { /* Metadata may not be deployed on older installations. */ }
    if (!$value || $value === '0000-00-00 00:00:00') {
        try {
            $query = $db->prepare('SELECT MAX(tanggal) FROM log_singkron WHERE id_satker = ?');
            $query->execute(array($courtId));
            $value = $query->fetchColumn();
        } catch (PDOException $e) { return null; }
    }
    return kasuari_public_timestamp($value);
}

function kasuari_public_timestamp($value)
{
    if (!is_string($value) || !preg_match('/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/', $value)) return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('Asia/Jayapura'));
    return $date && $date->format('Y-m-d H:i:s') === $value ? $date->format('Y-m-d\TH:i:sP') : null;
}

function kasuari_public_akta(PDO $db, $courtId, $caseId, $caseType)
{
    $empty = array('state'=>'unavailable', 'issued_on'=>null, 'synced_at'=>null, 'collections'=>array());
    $nonDivorce = is_string($caseType) && trim($caseType) !== '' && stripos($caseType, 'cerai') === false;
    if (!function_exists('kasuari_akta_view')) {
        if ($nonDivorce) $empty['state'] = 'not_applicable';
        return $empty;
    }
    try {
        // Only the published snapshot is visible. Staged or interrupted deliveries are never queried.
        $query = $db->prepare('SELECT synced_at FROM kasuari_akta_cerai_sync WHERE pn_id = ? LIMIT 2');
        $query->execute(array($courtId));
        $snapshots = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($snapshots) !== 1 || !($synced = kasuari_public_timestamp($snapshots[0]['synced_at']))) {
            if ($nonDivorce) $empty['state'] = 'not_applicable';
            return $empty;
        }
        $query = $db->prepare('SELECT nomor_akta_cerai, tgl_akta_cerai, tgl_penyerahan_akta_cerai,
            tgl_penyerahan_akta_cerai_pihak2, pihak2_tersedia FROM kasuari_akta_cerai
            WHERE pn_id = ? AND perkara_id = ? LIMIT 2');
        $query->execute(array($courtId, $caseId));
        $records = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($records) > 1) return $empty;
        if (!$records) {
            return array_merge($empty, array('state'=>$nonDivorce ? 'not_applicable' : 'not_recorded', 'synced_at'=>$synced));
        }
        $record = $records[0];
        foreach (array('tgl_akta_cerai', 'tgl_penyerahan_akta_cerai', 'tgl_penyerahan_akta_cerai_pihak2') as $field) {
            $record[$field] = kasuari_public_date($record[$field]);
        }
        if (!in_array($record['pihak2_tersedia'], array(0, 1, '0', '1'), true)) return $empty;
        if (!(int)$record['pihak2_tersedia']) $record['tgl_penyerahan_akta_cerai_pihak2'] = null;
        $record['state'] = 'recorded';
        // Reuse Kasuari's issuance interpretation without changing its admin-only internal views.
        $view = kasuari_akta_view($record);
        $state = $view['tone'] === 'success' ? 'issued' : ($view['tone'] === 'warning' ? 'incomplete' : 'not_recorded');
        $party1 = $record['tgl_penyerahan_akta_cerai'];
        $party2 = $record['tgl_penyerahan_akta_cerai_pihak2'];
        return array('state'=>$state, 'issued_on'=>$record['tgl_akta_cerai'], 'synced_at'=>$synced,
            'collections'=>array(
                'party1'=>array('state'=>$party1 ? 'collected' : 'not_recorded', 'collected_on'=>$party1),
                'party2'=>array('state'=>!(int)$record['pihak2_tersedia'] ? 'unavailable' : ($party2 ? 'collected' : 'not_recorded'), 'collected_on'=>$party2),
            ));
    } catch (PDOException $e) {
        if ($nonDivorce) $empty['state'] = 'not_applicable';
        return $empty;
    }
}

function kasuari_public_case_lookup(PDO $db, $court, $number)
{
    $result = array('version'=>1, 'state'=>'unavailable', 'court_key'=>null, 'case'=>null, 'synced_at'=>null);
    $number = kasuari_public_case_number($number);
    if (!is_string($court) || strlen($court) > 150 || !kasuari_public_case_number_valid($number)) return $result;
    $key = kasuari_public_court_key($court);
    if ($key === '') return $result;
    $query = $db->query("SELECT id, nama FROM pengadilan_agama WHERE aktif = 'Y'");
    $matches = array();
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        if (kasuari_public_court_key($row['nama']) === $key) $matches[] = $row;
    }
    // Never guess a court ID, and fail closed if aliases are ambiguous.
    if (count($matches) !== 1) return $result;
    $courtId = (int)$matches[0]['id'];
    $result['court_key'] = $key;
    $result['synced_at'] = kasuari_public_sync_time($db, $courtId);
    $query = $db->prepare("SELECT perkara_id, nomor_perkara, jenis_perkara_nama, tanggal_pendaftaran,
        tahapan_terakhir_text, proses_terakhir_text FROM perkara
        WHERE pn_id = ? AND UPPER(REPLACE(nomor_perkara, ' ', '')) = ? LIMIT 2");
    $query->execute(array($courtId, $number));
    $cases = $query->fetchAll(PDO::FETCH_ASSOC);
    if (count($cases) > 1) return $result;
    if (!$cases) {
        $query = $db->prepare('SELECT perkara_id FROM perkara WHERE pn_id = ? LIMIT 1');
        $query->execute(array($courtId));
        // An empty court dataset is not evidence that the requested case does not exist.
        if ($query->fetchColumn() !== false) $result['state'] = 'not_found';
        return $result;
    }
    $case = $cases[0];
    $query = $db->prepare("SELECT MAX(tanggal_putusan) FROM perkara_putusan
        WHERE pn_id = ? AND perkara_id = ? AND tanggal_putusan IS NOT NULL AND tanggal_putusan <> '0000-00-00'");
    $query->execute(array($courtId, $case['perkara_id']));
    $result['state'] = 'found';
    $result['case'] = array(
        'number'=>kasuari_public_text($case['nomor_perkara'], 100),
        'type'=>kasuari_public_text($case['jenis_perkara_nama'], 160),
        'registered_on'=>kasuari_public_date($case['tanggal_pendaftaran']),
        'decided_on'=>kasuari_public_date($query->fetchColumn()),
        'stage'=>kasuari_public_text($case['tahapan_terakhir_text'], 160),
        'process'=>kasuari_public_text($case['proses_terakhir_text'], 200),
    );
    $result['divorce_certificate'] = kasuari_public_akta($db, $courtId, $case['perkara_id'], $case['jenis_perkara_nama']);
    return $result;
}
