<?php

$identity_id = '01001937';
$client = new swoole_client(SWOOLE_SOCK_TCP);
if (!$client->connect('heart.sinhonet.cn', 9529, -1)) {
    echo "connect failed. Error: {$client->errCode}\n";
    return;
}
$header = 'GET' . ' ' . '/' . ' HTTP/1.1'."\r\n";
$header .= "Content-Type: application/json; charset=utf-8\r\n";
$header .= 'Connection: Keep-Alive'."\r\n";
$body = json_encode(array(
    'cmd' => 'notify_alarm',
    'type' => 'sys',
    'from' => 'server',
    'data' => $identity_id,
        ));
$header .= 'Content-Length: ' . strlen($body) . "\r\n";
$client->send($header . "\r\n" . $body);
//echo $client->recv();
$client->close();
