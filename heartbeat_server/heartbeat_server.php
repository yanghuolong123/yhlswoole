<?php

defined('ENV_DEV') or define('ENV_DEV', 0);
date_default_timezone_set('PRC');
$loader = require_once __DIR__ . '/vendor/autoload.php';
//spl_autoload_register(function($class) {
//    $file = __DIR__ . '/' . str_replace('\\', '/', trim($class, '\\')) . '.php';
//    if (is_file($file)) {
//        include_once $file;
//    }
//});
$loader->addPsr4('libs\\', 'libs/');
$loader->addPsr4('classes\\', 'classes/');

$cofig = ENV_DEV ? 'main-local.php' : 'main.php';
$setting = require_once __DIR__ . '/config/' . $cofig;
$protocol = new libs\Http();
$protocol->setStore(new \classes\Mongo($setting['mongo']['host'], $setting['mongo']['port']));
$protocol->setCache(new \classes\Memcached($setting['cache']));

unset($setting['mongo'], $setting['cache']);
$server = new libs\Server($protocol, $setting);
$server->run();

