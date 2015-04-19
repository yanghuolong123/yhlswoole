<?php

$http = new swoole_http_server("0.0.0.0", 9501);
$http->on('connect', function($ser, $fd, $from_id){
    echo "connect \n";
});
$http->on('request', function($request, $response) {
   // var_dump($request);
    $response->end("<h1>Hello Swoole1111111111. #".rand(1000, 9999)."</h1>");

});
$http->start();