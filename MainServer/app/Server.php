<?php

namespace App;

use Swoole;

class Server extends Swoole\Protocol\WebSocket {

    protected $store;
    protected $users = array();

    const MESSAGE_MAX_LEN = 1024;

    public function __construct($config = array()) {
        $this->getStore(new Mongo(MONGO_HOST));
        parent::__construct($config);
    }

    public function onConnect($serv, $fd, $from_id) {
        echo date('Y-m-d H:i：s') . " {$fd} begin connect from {$from_id}\n";
    }

    public function onClose($ser, $fd, $from_id) {
        if (isset($this->users[$fd])) {
            unset($this->users[$fd]);
        }
        echo "Client {$fd} close connection\n";
    }

    public function getStore($store) {
        $this->store = $store;
    }

    public function onTask($serv, $task_id, $from_id, $data) {

        return "from_id:$from_id ";
    }

    public function onFinish($serv, $task_id, $data) {
        $this->log('task finish! task_id:' . $task_id . ' ' . $data);
    }

    /**
     * 消息入口
     * 
     * @param type $client_id
     * @param type $ws
     * @return type
     */
    public function onMessage($client_id, $ws) {
        $this->log('onMessage: client_id=' . $client_id . ' msg=' . $ws['message']);
        $msg = json_decode($ws['message'], true);
        if (empty($msg['cmd'])) {
            $this->sendErrorMessage($client_id, 101, 'invalid command');
            return;
        }

        $func = 'cmd_' . $msg['cmd'];
        $this->$func($client_id, $msg);
    }

    /**
     * 登陆
     * 
     * @param type $client_id
     * @param type $msg
     */
    public function cmd_login($client_id, $msg) {
        $this->users[$client_id] = $client_id;

        $resMsg = [
            'cmd' => 'login',
            'fd' => $client_id,
            'name' => $msg['name'],
            'avatar' => $msg['avatar'],
            'uid' => $msg['uid'],
        ];

        $this->store->login($msg['uid'], $resMsg);
        $this->sendJson($client_id, $resMsg);

        $resMsg['cmd'] = 'newUser';
        $this->broadcastJson($client_id, $resMsg);

        $loginMsg = [
            'cmd' => 'fromMsg',
            'from' => 0,
            'channal' => 0,
            'data' => $msg['name'] . '上线了',
        ];
        $this->broadcastJson($client_id, $loginMsg);
    }

    public function cmd_getonline($client_id, $msg) {
        $resMeg = [
            'cmd' => 'getOnline',
        ];

        $users = $this->store->getOnlineUsers();
        $info = $this->store->getUsers(array_slice($users, 0, 100));

        $resMeg['users'] = $users;
        $resMeg['list'] = $info;
        $this->sendJson($client_id, $resMeg);
    }

    public function cmd_message($client_id, $msg) {
        $resMsg = $msg;
        $resMsg['cmd'] = 'fromMsg';

        if (strlen($msg['data']) > self::MESSAGE_MAX_LEN) {
            $this->sendErrorMessage($client_id, 102, 'message max len is ' . self::MESSAGE_MAX_LEN);
            return;
        }

        if ($msg['channal'] == 0) {
            $this->broadcastJson($client_id, $resMsg);
        } elseif ($msg['channal'] == 1) {
            $this->sendJson($msg['to'], $resMsg);
        }

        $data['user'] = $this->store->getUser($msg['uid']);
        $data['msg'] = $msg;
        $data['time'] = time();
        $this->store->saveMsg($data);
    }

    public function sendErrorMessage($client_id, $code, $msg) {
        $this->sendJson($client_id, [
            'cmd' => 'error',
            'code' => $code,
            'msg' => $msg,
        ]);
    }

    public function cmd_getHistory($client_id, $msg) {
        $resMsg['cmd'] = 'getHistory';
        $resMsg['history'] = $this->store->getHistory();
        $this->sendJson($client_id, $resMsg);
    }

    public function sendJson($client, $array) {
        $this->send($client, json_encode($array));
    }

    public function broadcastJson($client_id, $array) {
        $this->broadcast($client_id, json_encode($array));
    }

    public function broadcast($client_id, $msg) {
        foreach ($this->users as $client_user_id) {
            if ($client_id != $client_user_id) {
                $this->send($client_user_id, $msg);
            }
        }
    }

}
