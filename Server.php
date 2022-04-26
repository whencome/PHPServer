<?php

require_once __DIR__.'/worker/TextWorker.php';

Class Server
{
    public static function GetWorker(string $name, array $workerCfg, array $servCfg)
    {
        $workerType = $workerCfg['worker_class'];
        switch ($workerType) {
            case 'ZKTextWorker':
                return new TextWorker($workerCfg, $servCfg);
            default:
                return null;
        }
    }

    public static function Start($appSettings)
    {
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
            if (empty($worker)) {
                continue;
            }
            $worker->Start();
        }
    }
}

///////////////////// start server /////////////////
$settings = include __DIR__.'/config/main.php';
Server::Start($settings);
