<?php

include_once __DIR__.'/../protocols/Text.php';
include_once 'RpcException.php';

class TextWorker
{
    // worker配置
    protected $config = [];
    // 全局服务器配置
    protected $serverConfig = [];
    protected $serv = null;

    public function __construct(array $config, array $serverConfig)
    {
        $this->config = $config;
        $this->serverConfig = $serverConfig;
        $port = $config['port'];
        $proto = $config['protocol'];
        $this->serv = new \Swoole\Server('0.0.0.0', $port, SWOOLE_PROCESS, $proto == 'tcp' ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_UDP);
    }

    // init swoole server
    protected function init()
    {
        // set serv params
        $initParams = [
            'worker_num' => $this->config['child_count'],
            'max_request' => $this->config['max_requests'],
            'tcp_user_timeout' => $this->config['recv_timeout'], // ??
            // 以守护进程方式执行，这样就不会阻塞其他服务
            'daemonize' => 1,
        ];
        $this->serv->set($initParams);
        // register event callbacks
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        $this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
    }

    public function Start()
    {
        $this->init();
        $this->serv->start();
    }

    //////////////// event callbacks /////////////////////
    // Note: all callback functions must be public
    public function onWorkerStart()
    {
        $bootstrapFile = $this->config['bootstrap'];
        if (!file_exists($bootstrapFile)) {
            throw new \Exception('start worker failed: bootstrap not set');
        }
        include_once $bootstrapFile;
    }

    public function onConnect($serv, $fd)
    {
        echo "Client: Connect.\n";
    }

    public function onReceive($serv, $fd, $reactor_id, $data)
    {
        echo 'RCV: ', $data, PHP_EOL;
        $this->handle($serv, $fd, $data);
    }

    public function onClose($server, $fd)
    {
        echo "Client: Close.\n";
    }

    /////////////////////////// request handle //////////////////////////
    /**
     * 处理客户端请求.
     */
    protected function handle($serv, $fd, $packet)
    {
        // 解析数据包
        $data = $this->parse($packet);
        // 处理请求
        // 先不做版本要求
        // $version = $data['version'];
        $ctx = $this->handleRequest($data);
        // 编码响应结果
        $ctx = json_encode($ctx);
        $resp = Text::encode($ctx);
        // 向客户端发送结果
        $serv->send($fd, $resp);
    }

    /**
     * 请求报文解析.
     */
    protected function parse($packet)
    {
        // 请求数据验证
        $r = Text::verify($packet);
        if ($r != 0) {
            throw new \Exception('packet error: packet data check failed');
        }
        // 读取数据
        $packetData = Text::decode($packet);
        $command = $packetData['command'];
        $request = json_decode($packetData['data'], true);
        // 验证请求是否合法
        if ($command != 'RPC') {
            throw new \Exception('invalid request: command not supported');
        }
        $serverSecret = $this->serverConfig['rpc_secret_key'];
        $sign = md5($request['data'] . '&' . $serverSecret);
        if ($sign != $request['signature']) {
            throw new Exception('invalid request: request not authenticated');
        }
        // 验证客户端请求
        $data = json_decode($request['data'], true);
        $user = $data['user'];
        if (empty($this->config['clients']) || !isset($this->config['clients'][$user])) {
            throw new Exception('invalid request: user not authenticated');
        }
        $userSecret = $this->config['clients'][$user];
        $pwd = md5(sprintf("%s:%s", $user, $userSecret));
        if ($pwd != $data['password']) {
            throw new Exception('invalid request: user not authenticated');
        }
        // 返回请求数据
        return $data;
    }

    /**
     * 处理请求.
     */
    protected function handleRequest($data)
    {
        $className = $data['class'];
        $methodName = $data['method'];
        $params = $data['params'];
        $prefix = 'RpcClient_';
        if (strpos($data['class'], $prefix) === 0) {
            $className = substr($className, strlen($prefix));
        }
        $class = '\\Handler\\'.$className;

        $ctx = null;
        try
        {
            // 请求开始时执行的函数，on_request_start一般在bootstrap初始化
            if(function_exists('on_request_start')) {
                \on_request_start();
            }
            if (!class_exists($class)) {
                throw new \Exception("class {$class} not exist");
            }
            $callback = array(new $class, $methodName);
            if(is_callable($callback)) {
                $ctx = call_user_func_array($callback, $params);
            } else {
                throw new \Exception("method {$class}::{$methodName} not exist");
            }
        } catch (RpcException $ex) {
            $ctx = isset($ctx) && is_array($ctx) ? $ctx : array();
            if ($ex->hasErrors()) {
                $ctx['errors'] = $ex->getErrors();
            } else {
                $ctx['error'] = array(
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                );
            }
        } catch (Exception $ex) {
            $ctx = array(
                'exception' => array(
                    'class' => get_class($ex),
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'traceAsString' => $ex->getTraceAsString(),
                )
            );
        }
        
        // 请求结束时执行的函数
        if(function_exists('on_request_finish'))
        {
            // 这里一般是关闭数据库链接等操作
            \on_request_finish();
        }
        
        return $ctx;
    }

}
