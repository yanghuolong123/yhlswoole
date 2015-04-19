<?php

namespace libs;

class Http {

    const HTTP_EOF = "\r\n\r\n";
    const MAX_SIZE = 2000000;

    protected $buffer = [];
    public $store;
    public $cache;
    public $server;

    public function setStore($store) {
        $this->store = $store;
    }

    public function setCache($cache) {
        $this->cache = $cache;
    }

    public function onStart($server) {
        echo "Server Start\n";
    }

    public function onConnect($server, $fd, $from_id) {
        echo date('Y-m-d H:i：s') . " {$fd} begin connect from {$from_id}\n";
    }

    public function onReceive($server, $fd, $from_id, $data) {
        $this->server->log($data);
        $this->parseData($fd, $data);
    }

    public function onClose($server, $fd, $from_id) {
        if (isset($this->buffer[$fd])) {
            unset($this->buffer[$fd]);
        }
        echo "Client {$fd} close connection\n";
    }

    public function onTask($serv, $task_id, $from_id, $data) {
        $data = unserialize($data);

//        $logMsg = "\n++++++++++++++++++\n";
//        $logMsg .= "header: " . var_export($data['header'], true);
//        $logMsg .= "\n body: " . var_export($data['reqBody'], true);
//        $logMsg .= "\n************************\n";
//        $this->server->log($logMsg);

        $body = json_decode($data['reqBody'], true);
        $fd = $data['fd'];
        if (empty($body)) {
            $this->sendJson($fd, 'error', ['success' => false, 'msg' => 'error data format (need json)']);
            return;
        }
        if (!$this->validate($fd, ['cmd', 'from', 'type'], $body)) {
            return;
        }
        $cache_fd = $this->cache->get('conn_' . $body['type'] . '_' . $body['from']);
        if ($fd !== $cache_fd) {
            $this->cache->set('conn_' . $body['type'] . '_' . $body['from'], $fd, 3600);
        }

        $func = 'cmd_' . $body['cmd'];
        if (method_exists($this, $func)) {
            $this->$func($fd, $body);
        } else {
            $this->sendJson($fd, $body['cmd'], ['success' => false, 'msg' => 'error cmd, please give a correct cmd']);
            return;
        }
    }

    public function onFinish($serv, $task_id, $data) {
        echo "Result: {$data}\n";
    }

    public function parseData($fd, $http_data) {
        if (isset($this->buffer[$fd])) {
            $http_data = $this->buffer[$fd] . $http_data;
        }
        $ret = strpos($http_data, self::HTTP_EOF);
        if ($ret === false) {
            $this->buffer[$fd] = $http_data;
            return;
        }

        list($header, $body) = explode(self::HTTP_EOF, $http_data, 2);
        if (empty($header) || strpos($header, 'GET') !== 0) {
            $this->buffer[$fd] = '';
            return;
        }

        $header = $this->parseHeader($header);
        if (isset($header['Content-Length'])) {
            $lenBody = intval($header['Content-Length']);
            if (strlen($body) < $lenBody) {
                $this->buffer[$fd] = $http_data;
                return;
            }
            $this->buffer[$fd] = substr($body, $lenBody + 1);
            if (strlen($this->buffer[$fd]) > self::MAX_SIZE) {
                $this->buffer[$fd] = '';
                return;
            }
            $body = substr($body, 0, $lenBody);
        }

        $this->server->serv->task(serialize([
            'fd' => $fd,
            'header' => $header,
            'reqBody' => $body,
        ]));

        if (!empty($this->buffer[$fd])) {
            $this->parseData($fd, $this->buffer[$fd]);
        }
    }

    public function parseHeader($headerLines) {
        if (is_string($headerLines)) {
            $headerLines = explode("\r\n", $headerLines);
        }
        $header = array();
        foreach ($headerLines as $_h) {
            $_h = trim($_h);
            if (empty($_h)) {
                continue;
            }
            $_r = explode(':', $_h, 2);
            $key = $_r[0];
            $value = isset($_r[1]) ? $_r[1] : '';
            $header[trim($key)] = trim($value);
        }
        return $header;
    }

    public function response($fd, $data) {
        $out = "HTTP/1.1 200 OK\r\n";
        $out .= "Server: mcctv-server\r\n";
        $out .= "Date:" . date("D, d M Y H:i:s T") . "\r\n";
        $out .= "Content-Type: application/json; charset=utf-8\r\n";
        $out .= "Content-Length: " . strlen($data) . "\r\n";
        $out .= "\r\n";
        $out .= $data;

        $this->server->send($fd, $out);
    }

    public function sendJson($fd, $cmd, array $arr) {
        $data['success'] = isset($arr['success']) ? $arr['success'] : true;
        $data['cmd'] = $cmd;
        $data['msg'] = isset($arr['msg']) ? $arr['msg'] : 'OK';
        $data['data'] = isset($arr['data']) ? $arr['data'] : [];

        $this->response($fd, json_encode($data));
    }

    public function validate($fd, array $fields, &$body) {
        foreach ($fields as $field) {
            if (!isset($body[$field]) || empty($body[$field])) {
                $this->sendJson($fd, $body['cmd'], ['success' => FALSE, 'msg' => "require field [{$field}]"]);
                return FALSE;
            }
        }
        return TRUE;
    }

    public function cmd_heartbeat($fd, &$body) {
        $identity_id = $this->cache->get('notify_alarm_' . $fd);
        if (!empty($identity_id)) {
            $info = $this->store->getInfoByIds([$identity_id]);
            $this->sendJson($fd, 'notify_alarm', ['data' => $info]);
            return;
        }
        $this->sendJson($fd, $body['cmd'], ['msg' => 'heartbeat ok']);
    }

    public function cmd_connet($fd, &$body) {
        $this->sendJson($fd, $body['cmd'], ['msg' => 'connet ok']);
    }

    public function cmd_req_video($fd, &$body) {
        if (!$this->validate($fd, ['to'], $body)) {
            return;
        }

        $to_fd = $this->cache->get('conn_' . 'box' . '_' . $body['to']);
        $data = empty($to_fd) ? ['data' => ['status' => 'offline']] : ['data' => $body['data']];

        $this->sendJson($to_fd, $body['cmd'], $data);
    }

    public function cmd_report_position($fd, &$body) {
        $this->store->reportPosition($body['data']);
        $this->sendJson($fd, $body['cmd'], ['msg' => 'report ok']);
    }

    public function cmd_identity_info($fd, &$body) {
        $identity_ids = $body['data'];
        if (!is_array($identity_ids)) {
            $this->sendJson($fd, $body['cmd'], ['success' => false, 'msg' => 'identity_ids is not array']);
            return;
        }

        $info = $this->store->getInfoByIds($identity_ids);
        $this->sendJson($fd, $body['cmd'], ['data' => $info]);
    }

    public function cmd_notify_alarm($fd, &$body) {
        $identity_id = $body['data'];
        $phone_ids = $this->store->getAccountByIds($identity_id);
        if (empty($phone_ids) || !is_array($phone_ids)) {
            return;
        }
        $info = $this->store->getInfoByIds([$identity_id]);

        foreach ($phone_ids as $phone_id) {
            if (empty($phone_id)) {
                continue;
            }
            $box_fd = $this->cache->get('conn_box_' . $phone_id);
            if ($box_fd) {
                $this->cache->set('notify_alarm_' . $box_fd, $identity_id, 180);
                $this->sendJson($box_fd, $body['cmd'], ['data' => $info]);
            }
            $mobile_fd = $this->cache->get('conn_mobile_' . $phone_id);
            if ($mobile_fd) {
                $this->cache->set('notify_alarm_' . $mobile_fd, $identity_id, 1800);
                $this->sendJson($mobile_fd, $body['cmd'], ['data' => $info]);
            }
        }
    }

}
