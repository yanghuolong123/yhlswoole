<?php

define('MONGO_HOST', '127.0.0.1');

define('WEBPATH', __DIR__);
require __DIR__ . '/../framework/libs/lib_config.php';

Swoole\Loader::addNameSpace('App', __DIR__ . '/app/');

$appSer = new App\Server();
$appSer->loadSetting(__DIR__ . '/swoole.ini');
$appSer->setLogger(new Swoole\Log\EchoLog(TRUE));

$server = Swoole\Network\Server::autoCreate('0.0.0.0', 9500);
$server->setProtocol($appSer);
$server->daemonize();
$server->run([
    'worker_num' => 1,
    'max_request' => 0,
        // 'task_worker_num' => 1,
]);
