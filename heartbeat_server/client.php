<?php

        const EOF = "\r\n";

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
//设置事件回调函数
$client->on("connect", function($cli) {
    echo "Connet Success";
    $header = 'GET' . ' ' . '/get.php' . ' HTTP/1.1' . EOF;
    $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . EOF;
    $header .= 'Accept-Encoding: gzip,deflate' . EOF;
    $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . EOF;
    $header .= 'Host: ' . '127.0.0.1' . EOF;
    $header .= 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0' . EOF;
    $header .= 'Connection: Keep-Alive' . EOF;
    $body = json_encode(array(
        'cmd' => 'connet',
        'type' => 'mobile',
        'from' => '18210189803',
    ));
    $header .= 'Content-Length: ' . strlen($body) . EOF;

    $cli->send($header . EOF . $body);
});
$client->on("receive", function($cli, $data) {
    echo "\r\n =======================\n";
    echo "Received: " . $data . "\n";
    echo "\r\n *************************\n";

    $header = 'GET' . ' ' . '/get.php' . ' HTTP/1.1' . EOF;
    $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . EOF;
    $header .= 'Accept-Encoding: gzip,deflate' . EOF;
    $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . EOF;
    $header .= 'Host: ' . '127.0.0.1' . EOF;
    $header .= 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0' . EOF;
    $header .= 'Connection: Keep-Alive' . EOF;
    $body = json_encode(array(
        'cmd' => 'heartbeat',
        'type' => 'mobile',
        'from' => '18210189803',
        //'to' => '18210189803',
        //'data' => ['posit'=>'低俗低俗'],
    ));
    $header .= 'Content-Length: ' . strlen($body) . EOF;

    $cli->send($header . EOF . $body);
    sleep(2);
});
$client->on("error", function($cli) {
    echo "Connect failed\n";
});
$client->on("close", function($cli) {
    echo "Connection close\n";
});
//发起网络连接
$client->connect('heart.sinhonet.cn', 9529, 2);
//$client->connect('192.168.100.232', 9529, 2);
//$client->connect('0.0.0.0', 9529, 2);

