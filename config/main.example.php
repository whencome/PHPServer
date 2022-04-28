<?php

return array(

    'workers' => array(
        # zk service
        'ZKService' => array(                                          // 注意：键名固定为服务名
            'protocol'              => 'tcp',                           // 固定tcp
            'port'                  => 5001,                            // 每组服务一个端口
            'child_count'           => 2,                              // 启动多少个进程提供服务
            'recv_timeout'          => 10000,                           // [选填]从客户端接收数据的超时时间          不配置默认1000毫秒
            'process_timeout'       => 30000,                           // [选填]业务逻辑处理超时时间               不配置默认30000毫秒
            'send_timeout'          => 1000,                            // [选填]发送数据到客户端超时时间            不配置默认1000毫秒
            'max_requests'          => 1000,                            // [选填]进程接收多少请求后退出              不配置默认是0，不退出
            'worker_class'          => 'TextWorker',
            'handler'               => '/var/www/myservice/Handler',
            'bootstrap'             => '/var/www/myservice/init.php',  // 进程启动时会载入这个文件，里面可以做一些autoload等初始化工作
            'project_name'          => 'MyService',
            'clients'               => [
                // client name => secret key
                'myclient' => '123456',
            ],
        ),
    ),

    'ENV'          => 'dev', // dev or production

    // 数据签名用私匙
    'rpc_secret_key'    => '123456',
    
    // 日志追踪 trace_log 日志目录
    'trace_log_path'    => '/home/logs/phpserver/trace',
    // 异常监控 exception_log 日志目录
    'exception_log_path'=> '/home/logs/phpserver/exception',
    // 是否开启日志追踪监控
    'trace_log_on'      => true,
    // 是否开启异常监控
    'exception_log_on'  => true,
);
