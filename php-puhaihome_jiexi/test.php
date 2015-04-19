<?php

require_once './config.php';

// Server
class Server {

    private $serv;
    private $_mongo;
    private $_db;
    public $analyzeData;

    public function __construct() {
        $this->serv = new swoole_server(LISTEN_HOST, 9528);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => DAEMONIZE,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'debug_mode' => 1,
            'log_file' => './log/swoole_' . date('Y-m-d') . '.log',
//            'package_max_length' => 128,
//            'open_length_check' => true,
//            'package_length_offset' => 15,
//            'package_body_offset' => 16,
//            'package_length_type' => 'N',
            'task_worker_num' => 8,
            'heartbeat_idle_time' => 7200,
            'heartbeat_check_interval' => 3600,
        ));

        $this->analyzeData = require_once './analyzedata.php';

//        $this->serv->on('Start', array($this, 'onStart'));
//        $this->serv->on('Connect', array($this, 'onConnect'));
//        $this->serv->on('Receive', array($this, 'onReceive'));
//        $this->serv->on('Close', array($this, 'onClose'));
//        $this->serv->on('Task', array($this, 'onTask'));
//        $this->serv->on('Finish', array($this, 'onFinish'));
//        $this->serv->start();
    }

    public function onStart($serv) {
        echo "Server Start\n";
    }

    public function onConnect($serv, $fd, $from_id) {
        echo date('Y-m-d H:i:s') . " {$fd} begin connect from {$from_id}\n";
        $serv->send($fd, "Connect Success {$fd}!");
    }

    public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
        $param['fd'] = $fd;
        $param['from_id'] = $from_id;
        $param['data'] = bin2hex($data);
        $serv->task(json_encode($param));

        echo date('Y-m-d H:i:s') . ": Get Message From Client {$fd}:{$param['data']}\n";
    }

    public function onClose($serv, $fd, $from_id) {
        echo "Client {$fd} close connection\n";
    }

    public function onTask($serv, $task_id, $from_id, $data) {
        $param = json_decode($data, true);
        $analzeData = $this->analyze($param['data']);
        $saveData['data'] = $analzeData;
        // 设备ID
        $saveData['identity_id'] = $analzeData[1] . $analzeData[3];
        // 上报时间
        $saveData['reporttime'] = ltrim($analzeData[2], 'a90000220000');
        // 命令解析
        $saveData['cmdinfo'] = $this->alanlyzeComd($analzeData[6], $analzeData[7]);

        $saveData['createtime'] = time();
        $saveData['createdatetime'] = date('Y-m-d H:i:s', $saveData['createtime']);
        $this->saveData($saveData);

        return "Task {$task_id} is compete!";
    }

    public function onFinish($serv, $task_id, $data) {
        echo "Result: {$data}\n";
    }

    public function getMongo() {
        if (empty($this->_mongo)) {
            $this->_mongo = new MongoClient("mongodb://" . MONGO_HOST);
        }

        return $this->_mongo;
    }

    public function getDB() {
        if (empty($this->_db)) {
            $this->_db = $this->getMongo()->selectDB('apk_service');
        }

        return $this->_db;
    }

    public function saveData(array $arr) {
        $collection = $this->getDB()->selectCollection('puhai_report_home');
        $collection->insert($arr);
    }

    public function analyze($data) {
//        $data = bin2hex($data); 
        $dataArr = array();
        $dataArr[] = substr($data, 0, 2);
        $dataArr[] = substr($data, 2, 2);
        $dataArr[] = substr($data, 4, 20);
        $dataArr[] = substr($data, 24, 6);
        $dataArr[] = substr($data, 30, 8);
        $dataArr[] = substr($data, 38, 2);
        $dataArr[] = substr($data, 40, 2);
        $dataArr[] = substr($data, 42, -6);
        $dataArr[] = substr($data, -6, 2);
        $dataArr[] = substr($data, -4);

        return $dataArr;
    }

    public function alanlyzeComd($cmd, $cdata) {
        $status_bit = $sub_status_bit = '';
        $cdata = str_split($cdata, 2);
        if (in_array($cmd, array('13', '14', '15', '18'))) {
            $status_bit = $cdata[0];
            $sub_status_bit = $cdata[2];
            echo $status_bit . "\n";
            return $this->analyzeBinInfo($status_bit, $sub_status_bit);
        }

        $len = count($cdata);
        $cmdDec = hexdec($cmd);
        if (($len == 22) && ($cmdDec >= hexdec('b4') && $cmdDec <= hexdec('cd'))) {
            $status_bit = $cdata[15];
            $sub_status_bit = $cdata[17];

            return $this->analyzeBinInfo($status_bit, $sub_status_bit);
        }

        return array();
    }

    public function analyzeBinInfo($statu_bit, $sub_status_bit) {
        $info = array();
        $statusBin = str_split(strrev(str_pad(base_convert($statu_bit, 16, 2), 8, '0', STR_PAD_LEFT)));
        $subStatusBin = str_split(strrev(str_pad(base_convert($sub_status_bit, 16, 2), 8, '0', STR_PAD_LEFT)));

        if (isset($this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['name'])) {
            $info['alarm'] = $this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['name'];
        }
        if (isset($this->analyzeData['cmd']['status_bit']['bit1'][$statusBin[1]]['name'])) {
            $info['breakdown'] = $this->analyzeData['cmd']['status_bit']['bit1'][$statusBin[1]]['name'];
        }

        if (isset($this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[0]])) {
            $info['alarm_ch4'] = $this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[0]];
        }
        if (isset($this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[2]])) {
            $info['alarm_co'] = $this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[2]];
        }
        if (isset($this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[4]])) {
            $info['alarm_emperature'] = $this->analyzeData['cmd']['status_bit']['bit3'][$statusBin[3]]['sub_status_bit']['bit0'][$subStatusBin[4]];
        }

        return $info;
    }

}

// 启动服务器
$server = new Server();

$server->alanlyzeComd('15', '180005a007528ee205f4');
