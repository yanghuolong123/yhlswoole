<?php

namespace App;

use Swoole;

class Http extends Swoole\Protocol\WebServer {

    protected $swoole_server;
    protected $keepalive = true;
    protected $buffer_header = array();
    protected $conn = array();

    const TIMER = 30000;
    const HTTP_EOF = "\r\n\r\n";
    const HTTP_HEAD_MAXLEN = 8192; //http头最大长度不得超过2k
    const ST_FINISH = 1; //完成，进入处理流程
    const ST_WAIT = 2; //等待数据
    const ST_ERROR = 3; //错误，丢弃此包

    function __construct($config = array()) {
        parent::__construct($config);
        $mimes = require(LIBPATH . '/data/mimes.php');
        $this->mime_types = array_flip($mimes);
        $this->config = $config;
        $this->parser = new Swoole\Http\Parser;
    }

    public function onStart($serv, $worker_id = 0) {
//        Swoole\Error::$echo_html = true;
        $this->swoole_server = $serv;
        Swoole::$php->server = $this;
        $this->log("[#{$worker_id}]. running. on {$this->server->host}:{$this->server->port}");
        register_shutdown_function(array($this, 'onError'));
    }

    public function onShutdown($serv) {
        $this->log("shutdown");
    }

    public function onClose($serv, $client_id, $from_id) {
        $this->log("Event: client[#$client_id@$from_id] close");
        $this->cleanBuffer($client_id);
        unset($this->conn[$client_id]);
    }

    public function onConnect($serv, $client_id, $from_id) {
        $this->log("Event: client[#$client_id@$from_id] connect");
        $this->conn[$client_id] = $client_id;
        //$serv->addtimer(self::TIMER);
    }

    public function onReceive($serv, $client_id, $from_id, $data) {
        //检测request data完整性
        $ret = $this->checkData($client_id, $data);
        switch ($ret) {
            //错误的请求
            case self::ST_ERROR;
                $this->server->close($client_id);
                return;
            //请求不完整，继续等待
            case self::ST_WAIT:
                return;
            default:
                break;
        }

        //完整的请求
        //开始处理
        $request = $this->requests[$client_id];
        $info = $serv->connection_info($client_id);
        $request->remote_ip = $info['remote_ip'];
        $_SERVER['SWOOLE_CONNECTION_INFO'] = $info;

        $this->parseRequest($request);
        $request->fd = $client_id;
        $this->currentRequest = $request;

        //处理请求，产生response对象
        $response = $this->onRequest($request);
        if ($response and $response instanceof Swoole\Response) { 
            $header = '';
            foreach ($request->head as $key=>$head) {
                $header .= $key.':'.$head."\r\n";
            }
            $msg = "\n=============\n";
            $msg .= '#request->head: ' . "\n" . $header . "\n\n";
            $msg .= '#request->body: ' . "\n" . $request->body . "\n\n";
            $msg .= "\n****************\n";
            $this->log($msg);
            $this->response($request, $response);
            
//            $serv->task(serialize([
//                'request' => $request,
//                'response' => $response,
//            ]));
        }
    }

    public function onTask($serv, $task_id, $from_id, $data) {
        $data = unserialize($data);
        $this->response($data['request'], $data['response']);

        return "#task_id:$task_id #from_id:$from_id";
    }

    public function onFinish($serv, $task_id, $data) {
        echo "complete $data\n";
    }

    public function onTimer($serv, $interval) {
        switch ($interval) {
            case self::TIMER:
                echo "Timer: #$interval begin!\n";
                if (empty($this->conn)) {
                    return;
                }
                $response = new Swoole\Response;
                $response->setHeader('Date', date("D, d M Y H:i:s T"));
                $response->setHeader('Server', 'YanghuolongServer');
                $response->body = "body: Timer response to you!\n";
                $out = $response->getHeader() . $response->body . "\n";
                foreach ($this->conn as $conn) {
//                    //$this->response($request, $response);
                    $this->server->send($conn, $out);
                }
                break;
            default :
                break;
        }
    }

    /**
     * 捕获错误
     */
    function onError() {
        $error = error_get_last();
        if (!isset($error['type']))
            return;
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $errorMsg = "{$error['message']} ({$error['file']}:{$error['line']})";
        $message = Swoole\Error::info(" Application Error", $errorMsg);
        if (empty($this->currentResponse)) {
            $this->currentResponse = new Swoole\Response();
        }
        $this->currentResponse->setHttpStatus(500);
        $this->currentResponse->body = $message;
        $this->response($this->currentRequest, $this->currentResponse);
    }

    /**
     * 发送响应
     * @param $request Swoole\Request
     * @param $response Swoole\Response
     * @return bool
     */
    function response(Swoole\Request $request, Swoole\Response $response) {
        if (!isset($response->head['Date'])) {
            $response->head['Date'] = date("D, d M Y H:i:s T");
        }
        if (!isset($response->head['Connection'])) {
            //keepalive
            if ($this->keepalive and ( isset($request->head['Connection']) and strtolower($request->head['Connection']) == 'keep-alive')) {
                $response->head['KeepAlive'] = 'on';
                $response->head['Connection'] = 'keep-alive';
            } else {
                $response->head['KeepAlive'] = 'off';
                $response->head['Connection'] = 'close';
            }
        }
        //过期命中
        if ($this->expire and $response->http_status == 304) {
            $out = $response->getHeader();
            return $this->server->send($request->fd, $out);
        }
        //压缩
        if ($this->gzip) {
            $response->head['Content-Encoding'] = 'deflate';
            $response->body = gzdeflate($response->body, $this->config['server']['gzip_level']);
        }
        $response->setHeader('Server', 'YanghuolongServer');
        $out = $response->getHeader() . $response->body; 
        $ret = $this->server->send($request->fd, $out);
        $this->afterResponse($request, $response);
        return $ret;
    }

    function afterResponse(Swoole\Request $request, Swoole\Response $response) {
        if (!$this->keepalive or $response->head['Connection'] == 'close') {
            $this->server->close($request->fd);
        }
        $request->unsetGlobal();
        //清空request缓存区
        unset($this->requests[$request->fd]);
        unset($request);
        unset($response);
    }

    function cleanBuffer($fd) {
        unset($this->requests[$fd], $this->buffer_header[$fd]);
    }

    function checkData($client_id, $http_data) {
        if (isset($this->buffer_header[$client_id])) {
            $http_data = $this->buffer_header[$client_id] . $http_data;
        }
        //检测头
        $request = $this->checkHeader($client_id, $http_data);
        //错误的http头
        if ($request === false) {
            $this->buffer_header[$client_id] = $http_data;
            //超过最大HTTP头限制了
            if (strlen($http_data) > self::HTTP_HEAD_MAXLEN) {
                $this->log("http header is too long.");
                return self::ST_ERROR;
            } else {
                $this->log("wait request data. fd={$client_id}");
                return self::ST_WAIT;
            }
        }
        //POST请求需要检测body是否完整
        if ($request->meta['method'] == 'POST') {
            return $this->checkPost($request);
        }
        //GET请求直接进入处理流程
        else {
            return self::ST_FINISH;
        }
    }

    function checkHeader($client_id, $http_data) {
        //新的连接
        if (!isset($this->requests[$client_id])) {
            if (!empty($this->buffer_header[$client_id])) {
                $http_data = $this->buffer_header[$client_id] . $http_data;
            }
            //HTTP结束符
            $ret = strpos($http_data, self::HTTP_EOF);
            //没有找到EOF，继续等待数据
            if ($ret === false) {
                return false;
            } else {
                $this->buffer_header[$client_id] = '';
                $request = new Swoole\Request;
                //GET没有body
                list($header, $request->body) = explode(self::HTTP_EOF, $http_data, 2);
                $request->head = $this->parser->parseHeader($header);
                //使用head[0]保存额外的信息
                $request->meta = $request->head[0];
                unset($request->head[0]);
                //保存请求
                $this->requests[$client_id] = $request;
                //解析失败
                if ($request->head == false) {
                    $this->log("parseHeader failed. header=" . $header);
                    return false;
                }

                // body长度检查
                if (isset($request->head['Content-Length'])) {
                    $this->buffer_header[$client_id] = substr($request->body, (int) $request->head['Content-Length']);
                    $request->body = substr($request->body, 0, (int) $request->head['Content-Length']);                    
                }
            }
        }
        //POST请求需要合并数据
        else {
            $request = $this->requests[$client_id];
            $request->body .= $http_data;
        }
        return $request;
    }

    /**
     * 解析请求
     * @param $request Swoole\Request
     * @return null
     */
    function parseRequest($request) {
        $url_info = parse_url($request->meta['uri']);
        $request->time = time();
        if (isset($url_info['path']))
            $request->meta['path'] = $url_info['path'];
        if (isset($url_info['fragment']))
            $request->meta['fragment'] = $url_info['fragment'];
        if (isset($url_info['query'])) {
            parse_str($url_info['query'], $request->get);
        }
        //POST请求,有http body
        if ($request->meta['method'] === 'POST') {
            $this->parser->parseBody($request);
        }
        //解析Cookies
        if (!empty($request->head['Cookie'])) {
            $this->parser->parseCookie($request);
        }
    }

    /**
     * 处理请求
     * @param $request
     * @return Swoole\Response
     */
    function onRequest(Swoole\Request $request) {
        $response = new Swoole\Response;
        $this->currentResponse = $response;
//        \Swoole::$php->request = $request;
//        \Swoole::$php->response = $response;
//        //请求路径
//        if ($request->meta['path'][strlen($request->meta['path']) - 1] == '/') {
//            $request->meta['path'] .= $this->config['request']['default_page'];
//        }
//        if ($this->doStaticRequest($request, $response)) {
//            //pass
//        }
//        /* 动态脚本 */ elseif (isset($this->dynamic_ext[$request->ext_name]) or empty($ext_name)) {
//            $this->processDynamic($request, $response);
//        } else {
//            $this->httpError(404, $response, "Http Not Found({($request->meta['path']})");
//        }
        $arr = array(
            '1111111111111111111',
            '22222222222222222',
            '3333333333',
            '44444444444444444444444444444',
            '5555',
        );
        $response->body = "body: recevice your data!".$arr[array_rand($arr)]."\n";

        return $response;
    }

}
