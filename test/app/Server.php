<?php

namespace App;

use Swoole;

class Server extends Swoole\Protocol\HttpServer {

    public $conn = array();

    public function __construct($config = array()) {
        parent::__construct($config);
    }

    public function onConnect($serv, $client_id, $from_id) {
        parent::onConnect($serv, $client_id, $from_id);
        $this->conn[] = $client_id;
        var_dump($this->conn);
    }

    public function onReceive($serv, $client_id, $from_id, $data) {
        //检测request data完整性
//        $ret = $this->checkData($client_id, $data);
//        switch ($ret) {
//            //错误的请求
//            case self::ST_ERROR;
//                $this->server->close($client_id);
//                return;
//            //请求不完整，继续等待
//            case self::ST_WAIT:
//                return;
//            default:
//                break;
//        }
//        //完整的请求
//        //开始处理
//        $request = $this->requests[$client_id];
//        $info = $serv->connection_info($client_id);
//        $request->remote_ip = $info['remote_ip'];
//        $_SERVER['SWOOLE_CONNECTION_INFO'] = $info;
//
//        $this->parseRequest($request);
//        $request->fd = $client_id;
//        $this->currentRequest = $request;
//
//        //处理请求，产生response对象
//        $response = $this->onRequest($request);
//        if ($response and $response instanceof Swoole\Response) {
//            //发送response
//            $this->response($request, $response);
//        }

//        //var_dump($data);
        
        parent::onReceive($serv, $client_id, $from_id, $data);
        while (true) {
            sleep(2);
            foreach ($this->conn as $conn) {
                echo "send to $conn\n";
                $ret = $this->server->send($conn, 'welcome to me!');
            }
        }
    }

}
