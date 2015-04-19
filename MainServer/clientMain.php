<?php

define('WEBPATH', __DIR__);
require __DIR__ . '/../framework/libs/lib_config.php';

$client = new Swoole\Client\WebSocket('127.0.0.1', 9999);

if(!$client->connect()) {
    echo 'connect failed!';
    exit(0);
} 

$client->send(json_encode([
    'cmd' => 'login',
    'name' => '111',
    'avatar' => 'dssd',
]));

//while (true) {
//    $client->send('Hello World!');
//    $msg = $client->recv();
//    if($msg === false) {
//        break;
//    }
//    echo "rev msg from server : $msg \n";
//    sleep(1);
//}
