<?php

$setting['server'] = [
    'host' => '0.0.0.0',
    'port' => 9529,
];

$setting['swoole'] = [
    'daemonize' => 1,
    'worker_num' => 4,
    'max_request' => 10000,
    'task_worker_num' => 4,
    'task_ipc_mode' => 3, // 使用消息队列通信，并设置为争抢模式
    'heartbeat_idle_time' => 600,
    'heartbeat_check_interval' => 180,
    'log_file' => dirname(__DIR__) . '/log/heartbeat.log',
];

$setting['mongo'] = [
    'host' => '10.10.11.15',
    'port' => 27017,
];

$setting['cache'] = [
    'servers' => [
        [
            'host' => '10.10.11.15',
            'port' => 11211,
            'weight' => 40,
        ],
        [
            'host' => '10.10.11.16',
            'port' => 11211,
            'weight' => 60,
        ],
    ],
];

return $setting;
