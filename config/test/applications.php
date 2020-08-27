<?php
/**
 * 应用中需要用到的配置信息。
 * 可以通过 Config::get( "applications." ) 得到所有项目，
 * 或通过 Config::get( "applications.user.expire" ) 获取具体值
 */
return [
    'applications' => [
        'user' => [
            'expire' => 2 *60 *60,
        ],
    ],
];
