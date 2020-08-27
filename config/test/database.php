<?php
/**
 * 数据库等相关配置信息。
 */
return [
    'system' =>[
        'Db' => [
            'type' => 'Mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'user' => 'root',
            'pswd' => '',
            'database' => '',
            'charset'  => 'utf8',
            // 数据库统一前缀。也可以在model中调用 $this->prefix( 'string' )
            'prefix'   => 'lr_',
            // 前缀代替式， 如 %s_user, 将会替换 %s为 'prefix'
            'prefixStr'=> '$pre_',
        ],
        'Redis'=>[
            'host' => '127.0.0.1',
            'port' => '6379',
            'auth' => '',
            'type' => 'redis',
            'db'   => '1',
        ],
    ]
];
