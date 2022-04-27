<?php

require_once __DIR__.'/../worker/TextWorker.php';

/**
 * Server，用于管理workers.
 */
Class Server
{
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
        foreach ($appSettings['workers'] as $name => $config) {
            $worker = self::GetWorker($name, $config, $servCfg);
            var_dump($worker);
            if (empty($worker)) {
                continue;
            }
            $worker->Start();
        }
    }

    /**
     * 停止服务.
     */
    public static function Stop()
    {
    }
}

///////////////////// start server /////////////////
// $settings = include __DIR__.'/config/main.php';
// Server::Start($settings);
