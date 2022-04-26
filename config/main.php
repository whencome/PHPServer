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
            'persistent_connection' => false,                           // [选填]是否是长连接                      不配置默认是短链接（短连接每次请求后服务器主动断开）
            'max_requests'          => 1000,                            // [选填]进程接收多少请求后退出              不配置默认是0，不退出
            'worker_class'          => 'TextWorker',
            'handler'               => '/var/www/myservice/Handler',
            'bootstrap'             => '/var/www/myservice/init.php',  // 进程启动时会载入这个文件，里面可以做一些autoload等初始化工作
            'project_name'          => 'ZKService',
            'clients'               => [
                'myclient' => '123456',
            ],
        ),
    ),

    'ENV'          => 'dev', // dev or production
    'worker_user'  => '', //运行worker的用户,正式环境应该用低权限用户运行worker进程

    // 数据签名用私匙
    'rpc_secret_key'    => '123456',
    
    // 日志追踪 trace_log 日志目录
    'trace_log_path'    => '/home/logs/monitor',
    // 异常监控 exception_log 日志目录
    'exception_log_path'=> '/home/logs/monitor',
    // 是否开启日志追踪监控
    'trace_log_on'      => true,
    // 是否开启异常监控
    'exception_log_on'  => true,
    // 日志追踪采样，10代表 采样率1/10, 100代表采样率1/100
    'trace_log_sample'  => 10,
    // 配额文件目录，用于配额限制
    'quota_file_dir'    => '/dev/shm/phpserver-quota',
);
