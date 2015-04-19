<?php

//        const EOF = "\r\n";
//
//$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
////设置事件回调函数
//$client->on("connect", function($cli) {
//    echo "Connet Success";
//    $header = 'GET' . ' ' . '/get.php' . ' HTTP/1.1' . EOF;
//    $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . EOF;
//    $header .= 'Accept-Encoding: gzip,deflate' . EOF;
//    $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . EOF;
//    $header .= 'Host: ' . '127.0.0.1' . EOF;
//    $header .= 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0' . EOF;
//    $header .= 'Connection: Keep-Alive' . EOF;
//    $body = json_encode(array(
//        'cmd' => 'connet',
//        'from' => '18210189803',
//    ));
//    $header .= 'Content-Length: ' . strlen($body) . EOF;
//
//    $cli->send($header . EOF . $body);
//});
//$client->on("receive", function($cli, $data) {
//    echo "Received: " . $data . "\n";
//
////    $header = 'GET' . ' ' . '/get.php' . ' HTTP/1.1' . EOF;
////    $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . EOF;
////    $header .= 'Accept-Encoding: gzip,deflate' . EOF;
////    $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . EOF;
////    $header .= 'Host: ' . '127.0.0.1' . EOF;
////    $header .= 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0' . EOF;
////    $header .= 'Connection: Keep-Alive' . EOF;
////    $body = json_encode(array(
////        'cmd' => 'heartbeat',
////        'from' => '18210189803',
////    ));
////    $header .= 'Content-Length: ' . strlen($body) . EOF;
////
////    $cli->send($header . EOF . $body);
////    sleep(1); 
//});
//$client->on("error", function($cli) {
//    echo "Connect failed\n";
//});
//$client->on("close", function($cli) {
//    echo "Connection close\n";
//});
////发起网络连接
//$client->connect('127.0.0.1', 9501, 0.5);


$identity_id = '01006419';
$client = new swoole_client(SWOOLE_SOCK_TCP);
if (!$client->connect('127.0.0.1', 9501, -1)) {
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
echo $client->recv();
//$client->close();
