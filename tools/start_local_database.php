<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$home = dirname(PHP_BINARY, 2) . '/mysql';
$config = $home . '/kasuari-local-data/my.ini';
$server = $home . '/bin/mysqld.exe';
if (!is_file($config) || !is_file($server)) {
    fwrite(STDERR, "Database lokal khusus KASUARI belum dipasang.\n"); exit(1);
}
function kasuari_local_mysql_ready() {
    $socket = @stream_socket_client('tcp://127.0.0.1:3307', $code, $message, 0.3);
    if (!$socket) return false;
    fclose($socket); return true;
}
if (kasuari_local_mysql_ready()) { echo "MySQL lokal KASUARI sudah aktif pada port 3307.\n"; exit; }
$quote = function ($value) { return "'" . str_replace("'", "''", $value) . "'"; };
$command = 'Start-Process -FilePath ' . $quote($server)
    . ' -ArgumentList @(' . $quote('--defaults-file="' . $config . '"')
    . ", '--bind-address=127.0.0.1', '--port=3307', '--standalone')"
    . ' -WorkingDirectory ' . $quote($home) . ' -WindowStyle Hidden';
$process = proc_open(array('powershell.exe', '-NoProfile', '-Command', $command), array(
    0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')
), $pipes);
if (!is_resource($process)) { fwrite(STDERR, "MySQL belum dapat dijalankan.\n"); exit(1); }
fclose($pipes[0]);
$out = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$status = proc_close($process);
if ($status !== 0) { fwrite(STDERR, "MySQL belum dapat dijalankan. " . $error . "\n"); exit(1); }
for ($i = 0; $i < 30; $i++) {
    usleep(500000);
    if (kasuari_local_mysql_ready()) { echo "MySQL lokal KASUARI aktif. Buka http://localhost/kasuari/\n"; exit; }
}
fwrite(STDERR, "MySQL belum siap. Periksa log di folder kasuari-local-data.\n"); exit(1);
