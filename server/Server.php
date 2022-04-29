<?php

require_once __DIR__.'/../worker/TextWorker.php';

/**
 * Server，用于管理workers.
 */
Class Server
{
    public static $server = null;

    public static function Instance()
    {
        if (!empty(self::$server)) {
            return self::$server;
        }
        self::$server = new \Swoole\Server('0.0.0.0', 8888, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        return self::$server;
    }

    public static function GetWorker(string $name, array $workerCfg, array $servCfg)
    {
        $workerType = $workerCfg['worker_class'];
        switch ($workerType) {
            case 'TextWorker':
                return new TextWorker($workerCfg, $servCfg);
            default:
                return null;
        }
    }

    /**
     * 获取应用设置.
     * 
     * @return array
     */
    public static function AppSettings()
    {
        $appSettings = require __DIR__.'/../config/main.php';
        return $appSettings;
    }

    /**
     * 启动服务.
     */
    public static function Start()
    {
        $appSettings = self::AppSettings();
        if (empty($appSettings['workers'])) {
            echo 'empty workers', PHP_EOL;
            return;
        }
        $servCfg = [
            'rpc_secret_key' => $appSettings['rpc_secret_key'],
            // 添加其它配置
        ];

        $server = self::Instance();

        foreach ($appSettings['workers'] as $name => $config) {
            $worker = self::GetWorker($name, $config, $servCfg);
            if (empty($worker)) {
                return;
            }
            // $worker->Start();
            $process = $server->addListener('0.0.0.0', $config['port'], SWOOLE_SOCK_TCP);
            $process->set([]);
            $process->on('connect',[$worker,'onConnect']);
            $process->on('receive',[$worker,'onReceive']);
        }
        $server->on('Connect', 'Server::onConnect');
        $server->on('Receive', 'Server::onReceive');
        $server->on('Close', 'Server::onClose');
        $server->on('WorkerStart', 'Server::onWorkerStart');
        $server->Start();
    }

    /**
     * 停止服务.
     */
    public static function Stop()
    {
    }


    public static function onConnect($serv, $fd)
    {
        echo "Client: Connect.\n";
    }

    public static function onReceive($serv, $fd, $reactor_id, $data)
    {
        echo 'RCV: ', $data, PHP_EOL;
    }

    public static function onWorkerStart()
    {
        echo "Worker start.\n";
    }

    public static function onClose($server, $fd)
    {
        echo "Client: Close.\n";
    }
}

///////////////////// start server /////////////////
// $settings = include __DIR__.'/config/main.php';
// Server::Start($settings);
