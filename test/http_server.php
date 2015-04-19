<?php
date_default_timezone_set('PRC');
define('DEBUG', 'on');
define("WEBPATH", realpath(__DIR__ . '/../'));
require __DIR__ . '/../framework/libs/lib_config.php';

Swoole\Loader::addNameSpace('App', __DIR__ . '/app/');

Swoole\Config::$debug = false;

$AppSvr = new App\Http();
$AppSvr->loadSetting(__DIR__.'/swoole.ini'); //加载配置文件
$AppSvr->setDocumentRoot(__DIR__.'/webroot');
$AppSvr->setLogger(new Swoole\Log\EchoLog(true)); //Logger

Swoole\Error::$echo_html = false;

$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);
$server->setProtocol($AppSvr);
//$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 2, 'task_worker_num'=>2, 'max_request' => 5000, 'log_file' => '/tmp/swoole.log'));
