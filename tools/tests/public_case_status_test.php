<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../../sys/sys_public_case_status.php';
$assertions = 0;
function case_assert($value, $message) {
    global $assertions;
    if (!$value) throw new RuntimeException($message);
    $assertions++;
}
$db = new PDO('sqlite::memory:', null, null, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$db->exec("CREATE TABLE pengadilan_agama (id INTEGER, nama TEXT, aktif TEXT);
    CREATE TABLE perkara (pn_id INTEGER, perkara_id INTEGER, nomor_perkara TEXT, jenis_perkara_nama TEXT,
        tanggal_pendaftaran TEXT, tahapan_terakhir_text TEXT, proses_terakhir_text TEXT, pihak1_text TEXT, petitum TEXT);
    CREATE TABLE perkara_putusan (pn_id INTEGER, perkara_id INTEGER, tanggal_putusan TEXT, amar_putusan TEXT);
    CREATE TABLE sync_monitoring (pn_id INTEGER, last_sync_at TEXT);
    CREATE TABLE log_singkron (id_satker INTEGER, tanggal TEXT);
    INSERT INTO pengadilan_agama VALUES (7,'PA Sorong','Y'),(8,'Pengadilan Agama Manokwari','Y'),(9,'PA Fakfak','Y'),(10,'PA Kaimana','N');
    INSERT INTO perkara VALUES (7,100,'123/Pdt.G/2026/PA.Srg','Cerai Gugat','2026-10-20','Persidangan','Penetapan hari sidang','PRIVATE PARTY','PRIVATE DOCUMENT'),
        (8,100,'123/Pdt.G/2026/PA.Mkw','Cerai Gugat','2026-10-21','Putusan','Minutasi','OTHER PRIVATE PARTY','OTHER PRIVATE DOCUMENT');
    INSERT INTO perkara_putusan VALUES (7,100,'2026-10-31','PRIVATE DECISION'), (7,100,'2026-11-01','LATEST PRIVATE DECISION'),
        (8,100,'2026-12-01','WRONG COURT DECISION');
    INSERT INTO sync_monitoring VALUES (7,'2026-11-01 10:30:00');
    INSERT INTO log_singkron VALUES (8,'2026-11-01 09:00:00');");
$case = kasuari_public_case_lookup($db,'Pengadilan Agama Sorong',' 123 / pdt.g / 2026 / pa.srg ');
case_assert($case['state']==='found','Exact normalized lookup');
case_assert($case['court_key']==='sorong','Court alias mapping');
case_assert($case['synced_at']==='2026-11-01T10:30:00+09:00','Actual sync time with WIT offset');
case_assert($case['case']['decided_on']==='2026-11-01','Latest decision scoped to composite court/case ID');
case_assert(count($case['case'])===6,'Six public case fields only');
case_assert(strpos(json_encode($case),'PRIVATE')===false,'No parties, documents or decision text');
case_assert(kasuari_public_case_lookup($db,'Pengadilan Agama Manokwari','123/Pdt.G/2026/PA.Srg')['state']==='not_found','Cross-court lookup is not found');
case_assert(kasuari_public_case_lookup($db,'Pengadilan Agama Manokwari','123/Pdt.G/2026/PA.Mkw')['synced_at']==='2026-11-01T09:00:00+09:00','Fallback to actual sync log');
case_assert(kasuari_public_case_lookup($db,'PA Fakfak','123/Pdt.G/2026/PA.Ffk')['state']==='unavailable','Empty court dataset is unavailable, not not-found');
case_assert(kasuari_public_case_lookup($db,'PA Kaimana','123/Pdt.G/2026/PA.Kmn')['state']==='unavailable','Inactive court not exposed');
case_assert(kasuari_public_case_lookup($db,'unknown','123/Pdt.G/2026/PA.Srg')['state']==='unavailable','No guessed court identity');
foreach (array('123', "123' OR 1=1", array('bad'), '123/Pdt.G/2026/PA.Srg%') as $bad) {
    case_assert(kasuari_public_case_lookup($db,'PA Sorong',$bad)['state']==='unavailable','No broad, wildcard, malformed or SQL lookup');
}
$db->exec("INSERT INTO perkara SELECT * FROM perkara WHERE pn_id=7");
case_assert(kasuari_public_case_lookup($db,'PA Sorong','123/Pdt.G/2026/PA.Srg')['state']==='unavailable','Duplicate case fails closed');
$db->exec("INSERT INTO pengadilan_agama VALUES (11,'Pengadilan Agama Sorong','Y')");
case_assert(kasuari_public_case_lookup($db,'PA Sorong','123/Pdt.G/2026/PA.Srg')['state']==='unavailable','Ambiguous court alias fails closed');
$db->exec('DROP TABLE sync_monitoring; DROP TABLE log_singkron');
case_assert(kasuari_public_case_lookup($db,'PA Manokwari','123/Pdt.G/2026/PA.Mkw')['synced_at']===null,'Missing metadata stays unknown');
case_assert(kasuari_public_date('2026-02-31')===null && kasuari_public_date('0000-00-00')===null,'Invalid dates stay unknown');

// Public akta uses the complete published snapshot, independently of general case synchronization.
case_assert($case['divorce_certificate']['state']==='unavailable','Older Kasuari without akta tables preserves the case result');
$db->exec("DELETE FROM pengadilan_agama WHERE id=11; DELETE FROM perkara WHERE pn_id=7;
    INSERT INTO perkara VALUES (7,100,'123/Pdt.G/2026/PA.Srg','Cerai Gugat','2026-10-20','Persidangan','Penetapan hari sidang','PRIVATE PARTY','PRIVATE DOCUMENT');
    CREATE TABLE kasuari_akta_cerai_sync (pn_id INTEGER PRIMARY KEY, synced_at TEXT);
    CREATE TABLE kasuari_akta_cerai (pn_id INTEGER, perkara_id INTEGER, nomor_akta_cerai TEXT, tgl_akta_cerai TEXT,
        tgl_penyerahan_akta_cerai TEXT, tgl_penyerahan_akta_cerai_pihak2 TEXT, pihak2_tersedia INTEGER);
    INSERT INTO kasuari_akta_cerai VALUES (7,100,'PRIVATE CERTIFICATE NUMBER','2026-10-30','2026-10-31',NULL,1),
        (8,100,'OTHER COURT CERTIFICATE','2026-10-28','2026-10-29','2026-10-30',1);");
case_assert(kasuari_public_akta($db,7,100,'Cerai Gugat')['state']==='unavailable','No published sync marker: hidden even when row exists');
$db->exec("INSERT INTO kasuari_akta_cerai_sync VALUES (7,'2026-11-01 11:30:00'),(8,'2026-11-01 12:30:00'),(9,'2026-11-01 13:30:00')");
$akta=kasuari_public_akta($db,7,100,'Cerai Gugat');
case_assert($akta['state']==='issued' && $akta['issued_on']==='2026-10-30','Issuance uses existing Kasuari classification');
case_assert($akta['synced_at']==='2026-11-01T11:30:00+09:00','Akta freshness uses its own completed snapshot');
case_assert($akta['collections']['party1']===array('state'=>'collected','collected_on'=>'2026-10-31'),'Party-one actual handover date');
case_assert($akta['collections']['party2']===array('state'=>'not_recorded','collected_on'=>null),'Party-two missing date stays not recorded');
case_assert(strpos(json_encode($akta),'PRIVATE')===false && strpos(json_encode($akta),'OTHER COURT')===false,'No certificate number, documents, names or cross-court data');
case_assert(kasuari_public_akta($db,8,100,'Cerai Talak')['collections']['party2']['collected_on']==='2026-10-30','Identical case IDs stay separated for akta too');
case_assert(kasuari_public_akta($db,9,100,'Cerai Gugat')['state']==='not_recorded','Completed empty snapshot is distinct from never-synced');
case_assert(kasuari_public_akta($db,9,100,'Perwalian')['state']==='not_applicable','No certificate panel for unrelated case without record');
$db->exec("UPDATE kasuari_akta_cerai SET pihak2_tersedia=0, tgl_penyerahan_akta_cerai_pihak2='2026-10-31', tgl_akta_cerai=NULL WHERE pn_id=7");
$akta=kasuari_public_akta($db,7,100,'Cerai Gugat');
case_assert($akta['state']==='incomplete','Partial issuance is not called issued');
case_assert($akta['collections']['party2']===array('state'=>'unavailable','collected_on'=>null),'Unavailable party-two source column overrides stray date');
$db->exec("UPDATE kasuari_akta_cerai SET tgl_akta_cerai='2026-02-31' WHERE pn_id=7");
case_assert(kasuari_public_akta($db,7,100,'Cerai Gugat')['issued_on']===null,'Invalid akta date is not invented');
$caseWithAkta=kasuari_public_case_lookup($db,'PA Sorong','123/Pdt.G/2026/PA.Srg');
case_assert($caseWithAkta['state']==='found' && $caseWithAkta['divorce_certificate']['state']==='incomplete','Public API includes only the scoped akta projection');
echo 'PASS: '.$assertions." public case API assertions (isolated SQLite; no live records modified).\n";
