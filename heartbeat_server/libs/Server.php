<?php

namespace libs;

class Server {

    public $serv;
    public $protocol;

    public function __construct($protocol, $setting = []) {
        $this->serv = new \swoole_server($setting['server']['host'], $setting['server']['port']);
        $this->protocol = $protocol;
        $protocol->server = $this;
        $this->serv->set($setting['swoole']);
    }

    public function run() {
        $this->serv->on('Start', array($this->protocol, 'onStart'));
        $this->serv->on('Connect', array($this->protocol, 'onConnect'));
        $this->serv->on('Receive', array($this->protocol, 'onReceive'));
        $this->serv->on('Close', array($this->protocol, 'onClose'));
        $this->serv->on('Task', array($this->protocol, 'onTask'));
        $this->serv->on('Finish', array($this->protocol, 'onFinish'));
        $this->serv->start();
        $this->protocol->server = $this->serv;
    }

    public function send($fd, $data) {
        $this->serv->send($fd, $data);
    }

    public function log($msg) {
        echo "\n####################\n".date('[Y-m-d H:i:s]') . "\n{$msg}\n";
    }

}
