<?php

define('DEBUG', 'on');
define('WEBPATH', __DIR__);
require WEBPATH . '/../framework/libs/lib_config.php';

$client = new Swoole\Async\HttpClient('http://127.0.0.1:8888/get.php');
//$client->reqHeader = [
//    'Connection' => 'keep-alive',
//];

$client->setHeader('Connection', 'Keep-Alive');
$client->setHeader('Keep-Alive', 30);
$client->setUserAgent('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0');
$client->onReady(function($cli, $body, $header) {
    var_dump($body, $header);
});


//for ($i = 0; $i < 10; $i++) {
//    $client->post(array('hello' => 'world'));
//    sleep(3);
//}

//$client->post(array('hello' => 'world'));
//echo "\n=========\n";
//$client->get(array('hello' => 'world'));

$client->get();
//sleep(10);

//$client = new Swoole\Async\HttpClient('http://www.baidu.com/');
//$client->onReady(function($cli, $body, $header){
//    var_dump($body, $header);
//});
//$client->get();
