<?php

$setting['server'] = [
    'host' => '0.0.0.0',
    'port' => 9529,
];

$setting['swoole'] = [
    'daemonize' => 0,
    'worker_num' => 2,
    'task_worker_num' => 2,
    'log_file' => dirname(__DIR__) . '/log/heartbeat.log',
];

$setting['mongo'] = [
    'host' => '192.168.100.233',
    'port' => 27017,
];

$setting['cache'] = [
    'servers' => [
        [
            'host' => '192.168.100.233',
            'port' => 11211,
            'weight' => 40,
        ],
        [
            'host' => '192.168.100.199',
            'port' => 11211,
            'weight' => 60,
        ],
    ],
];

return $setting;
