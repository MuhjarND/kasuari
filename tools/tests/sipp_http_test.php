<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../../sys/sys_sipp.php';
function sipp_http_assert($value, $label) { if (!$value) throw new RuntimeException($label); echo 'PASS: ' . $label . "\n"; }
function sipp_request($url, $data = null) {
    $c = curl_init($url); curl_setopt_array($c, array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20));
    if ($data !== null) curl_setopt_array($c, array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)));
    $body = curl_exec($c); $status = curl_getinfo($c,CURLINFO_HTTP_CODE); curl_close($c);
    return array($status,$body);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$port = (int) (getenv('KASUARI_TEST_DB_PORT') ?: 33319);
$db = mysqli_connect('127.0.0.1','root','','',$port);
$name = 'kasuari_sipp_http_' . getmypid();
mysqli_query($db,'CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4'); mysqli_select_db($db,$name);
$root = sys_get_temp_dir() . '/kasuari-sipp-http-' . getmypid();
$repo = dirname(__DIR__,2); $created=array(); $server=null; $pipes=array();
try {
    kasuari_sipp_schema($db,true);
    mysqli_query($db,'CREATE TABLE perkara (pn_id INT, perkara_id BIGINT) ENGINE=InnoDB');
    mysqli_query($db,'CREATE TABLE log_singkron (id INT AUTO_INCREMENT PRIMARY KEY,id_satker INT,tanggal DATETIME,perkara INT) ENGINE=InnoDB');
    mysqli_query($db,'CREATE TABLE sync_requests (request_id CHAR(40) PRIMARY KEY,pn_id INT,completed_at DATETIME,jumlah_perkara INT) ENGINE=InnoDB');
    $token = kasuari_sipp_issue_token($db,439,1);
    foreach(array('sys','modul','database') as $dir) mkdir($root.'/'.$dir,0775,true);
    $files=array('index.php','modul/sync.php','modul/sipp.php','modul/sipp_config.php','sys/sys_authorization.php','sys/sys_monitoring.php','sys/sys_akta_cerai.php','sys/sys_sipp.php','sys/sys_sipp_catalog.php','sys/sys_sipp_view.php','sys/sys_fungsi.php','sys/sipp_menu_inventory.csv','database/akta_cerai.sql','database/sipp_portal.sql');
    foreach($files as $file) { copy($repo.'/'.$file,$root.'/'.$file); $created[]=$root.'/'.$file; }
    foreach(array('config'=>'sys_config','session'=>'sys_session','header'=>'header','footer'=>'footer') as $fixture=>$target) {
        copy(__DIR__.'/fixtures/sipp_'.$fixture.'.php',$root.'/sys/'.$target.'.php'); $created[]=$root.'/sys/'.$target.'.php';
    }
    copy(__DIR__.'/fixtures/sipp_router.php',$root.'/router.php'); $created[]=$root.'/router.php';
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error); $address=stream_socket_get_name($socket,false); fclose($socket);
    putenv('KASUARI_SIPP_TEST_DB='.$name); putenv('KASUARI_TEST_DB_PORT='.$port); putenv('KASUARI_SIPP_TEST_REPO='.$repo);
    $created[]=$root.'/server.out'; $created[]=$root.'/server.err';
    $server=proc_open(array(PHP_BINARY,'-S',$address,'-t',$root,$root.'/router.php'),array(0=>array('pipe','r'),1=>array('file',$root.'/server.out','a'),2=>array('file',$root.'/server.err','a')),$pipes,$root);
    if(!is_resource($server)) throw new RuntimeException('Test server failed.');
    for($i=0;$i<50;$i++){ $probe=@stream_socket_client('tcp://'.$address,$errno,$error,0.1);if($probe){fclose($probe);break;}usleep(100000); }
    $base='http://'.$address; $endpoint=$base.'/index.php?modul=sync'; $request=str_repeat('a',40);
    $row=array('perkara_id'=>'10','nomor_perkara'=>'10/Pdt.G/2026/PA.Test','tanggal_pendaftaran'=>'2026-10-15','jenis_perkara_nama'=>'Cerai Gugat');
    $json=json_encode(array($row));
    $batch=array('action'=>'sipp_batch','protocol'=>2,'pn_id'=>439,'request_id'=>$request,'sipp_token'=>$token,'dataset'=>'perkara','rows_json'=>$json,'rows_hash'=>hash('sha256',$json));
    $bad=$batch;unset($bad['sipp_token']);sipp_http_assert(sipp_request($endpoint,$bad)[0]===403,'HTTP: unsigned SIPP upload denied.');
    $bad=$batch;$bad['pn_id']=431;sipp_http_assert(sipp_request($endpoint,$bad)[0]===403,'HTTP: cross-court token denied.');
    $begin=array('action'=>'sipp_begin','protocol'=>2,'pn_id'=>439,'request_id'=>$request,'sipp_token'=>$token);
    $response=sipp_request($endpoint,$begin);sipp_http_assert($response[0]===200 && $response[1]==='SIPP_BEGIN_OK','HTTP: snapshot generation registered before reading source.');
    $response=sipp_request($endpoint,$batch);sipp_http_assert($response[0]===200 && $response[1]==='SIPP_BATCH_OK','HTTP: authenticated structured upload accepted.');
    $manifest=array();foreach(kasuari_sipp_catalog() as $key=>$spec)$manifest[$key]=array('state'=>'unavailable','count'=>0,'columns'=>array(),'message'=>'Fixture missing table');
    $manifest['perkara']=array('state'=>'ready','count'=>1,'columns'=>array_keys($row),'message'=>'');
    $final=array('action'=>'finalize','protocol'=>2,'pn_id'=>439,'request_id'=>$request,'sipp_included'=>1,'sipp_token'=>$token,'sipp_manifest'=>json_encode($manifest));
    $bad=$final;unset($bad['sipp_included'],$bad['sipp_manifest'],$bad['sipp_token']);sipp_http_assert(sipp_request($endpoint,$bad)[0]===409,'HTTP: cannot finalize SIPP request without component manifest.');
    $response=sipp_request($endpoint,$final);sipp_http_assert($response[0]===200 && strpos($response[1],'SYNC_OK|')===0,'HTTP: complete authenticated finalize succeeds.');
    sipp_http_assert(sipp_request($endpoint,$batch)[0]===422,'HTTP: completed request frozen against later batches.');
    sipp_http_assert(sipp_request($endpoint,$final)[0]===200,'HTTP: identical finalize replay is safe.');
    $bad=$final;$bad['sipp_manifest']='{}';sipp_http_assert(sipp_request($endpoint,$bad)[0]===409,'HTTP: incompatible manifest replay denied.');
    $otherToken=kasuari_sipp_issue_token($db,431,1);
    $bad=$final;$bad['pn_id']=431;$bad['sipp_token']=$otherToken;sipp_http_assert(sipp_request($endpoint,$bad)[0]===409,'HTTP: receipt cannot be reused by a different satker.');
    $response=sipp_request($base.'/index.php?modul=sipp&satker=439&bagian=perkara');
    sipp_http_assert($response[0]===200 && strpos($response[1],'10/Pdt.G/2026/PA.Test')!==false,'HTTP: administrator list renders synchronized row.');
    sipp_http_assert(sipp_request($base.'/index.php?modul=sipp&satker=439&test_user=1')[0]===403,'HTTP: non-administrator portal denied.');
    sipp_http_assert(sipp_request($base.'/index.php?modul=sipp&satker=999')[0]===404,'HTTP: unrecognized satker denied.');
    $export=sipp_request($base.'/index.php?modul=sipp&satker=439&bagian=perkara&export=csv');
    sipp_http_assert($export[0]===200 && substr($export[1],0,3)==="\xef\xbb\xbf" && strpos($export[1],'<html')===false,'HTTP: CSV contains clean data without HTML.');
    $badConfig=sipp_request($base.'/index.php?modul=sipp_config',array('satker'=>439,'aksi'=>'token','csrf'=>'bad'));
    sipp_http_assert($badConfig[0]===403,'HTTP: token configuration requires valid CSRF.');
    $config=sipp_request($base.'/index.php?modul=sipp_config',array('satker'=>439,'aksi'=>'token','csrf'=>'test-csrf'));
    sipp_http_assert($config[0]===200 && strpos($config[1],'<?php')===0 && strpos($config[1],'<html')===false,'HTTP: token config downloads as clean PHP.');
    if(getenv('KASUARI_TEST_NODE')) {
        putenv('KASUARI_SIPP_TEST_URL='.$base); putenv('KASUARI_SIPP_TEST_OUTPUT='.sys_get_temp_dir().'/kasuari-sipp-browser');
        $command=escapeshellarg(getenv('KASUARI_TEST_NODE')).' '.escapeshellarg(__DIR__.'/sipp_browser_test.cjs');
        passthru($command,$exit);sipp_http_assert($exit===0,'Browser: desktop/mobile integration checks.');
    }
    echo "ALL SIPP HTTP CHECKS PASSED\n";
} finally {
    if(is_resource($server)){proc_terminate($server);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($server);}
    putenv('KASUARI_SIPP_TEST_DB');putenv('KASUARI_SIPP_TEST_REPO');putenv('KASUARI_SIPP_TEST_URL');
    mysqli_query($db,'DROP DATABASE `'.$name.'`');mysqli_close($db);
    foreach($created as $file)if(is_file($file))unlink($file);
    foreach(array('sys','modul','database') as $dir)if(is_dir($root.'/'.$dir))rmdir($root.'/'.$dir);
    if(is_dir($root))rmdir($root);
}
