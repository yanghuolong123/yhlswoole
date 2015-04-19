<?php

$http = new swoole_http_server('0.0.0.0', 9501);

$http->on('request', function($request, $response){
    var_dump($request);
    //var_dump($response);
    $response->end("<h2>Hello, World! ".  mt_rand(1, 100000));
});

$http->start();