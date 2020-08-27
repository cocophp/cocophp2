<?php
/**
 * 系统用到的一些配置信息。
 */
return [
    'system' =>[
        // 定义 log 文件路径
        'logPath' => '../logs/',
        // 网站主域名。
        'domain'  => 'http://localhost/',
        // 资源域名，若有cdn可配置此项. 否则请配置为 domain 相同值
        'cdn' => 'http://localhost/',
        // 属于自己的风格定义。
        'style' => [
            'controllerSuffix' => 'Controller', // 指定控制器后缀。
            'actionSuffix'     => 'Action',     // 指定方法后缀。
        ],
        // 下面数组中的key，只有在运行时才能得到。其中绝大部分是框架加载的。
        'runtime' => [
            'envMode'      => '', // 当前环境模式。用于区分 开发、生产 环境
            'console'      => false, // 用于区分当前是不是控制台模式。
            // 原始请求url，包含GET参数。这里注释但运行时肯定可以拿到。
            // 而且千万不要打开注释。系统的一个小bug。
            // 'requestRaw'   => '',
            'requestUrl'   => '', // 请求url路径，不包含GET参数。
            'requestPreg'  => '', // 请求自定义url的正则表示，也就是route.php中的key。
            'controller'   => '', // 请求映射到的控制器名称。
            'action'       => '', // 请求映射到的方法名称。
            'requestMethod'=> '', // get, post, put, delete
        ],
        // 请求上下文。一般请求拦截器会向里面注入一些内容。
        'context' => [

        ]
    ],
];
