<?php
/**
 * 自定义路由
 * role用于定义一些常用规则匹配，
 * bootstrap 用于定义路由重写机制。
 * 若全站采用某种统一风格，可尝试定义通用规则。
 * 最下方推荐了几组常用restful风格请求。
 * 注意 value 中的 $1 $2 $3... $9， 一个key中可以使用九种 rule，
 * value按顺序对应为 $1...$9
 */
use \request\Request;

return [
    'system' => [
        'route' => [
            'bootstrap' => [
                '/' => [ '/page/index/index' ], // 访问applications/page/Controller/indexController.php->indexAction();
                '/abc' => [ '/page/index/index', [Request::class] ], // 同上，不过请求会先经过Request拦截。
                '/page/index/index' => '',      // 让 /page/index/index 路由无法访问。
            ],
            'rule' => [
                // 请求格式
                'all@'    => '(get|post|put|delete)@',       // 所有请求模式
                'get@'    => '(get)@',                       // get
                'post@'   => '(post)@',                      // post
                'put@'    => '(put)@',                       // put
                'delete@' => '(delete)@',                    // delete
                // 一些常用助记符
                ':int'    => '([0-9]+)',                     // 纯数字
                ':char'   => '([a-zA-Z]+)',                  // 纯字母
                ':string' => '([a-zA-Z0-9_]+)',              // 字母+数字+_组合
                ':all'    => '(.*)',                         // 匹配所有
            ]
        ]
    ]
];
/**
 * 几种常用的api风格规则
 * 1 get post put delete's method restful 风格
 *   'post@/:all'        => '/$2/insert',         // 新增
 *   'put@/:all/:int'    => '/$2/update?id=$3',   // 修改
 *   'delete@/:all/:int' => '/$2/del?id=$3',      // 删除
 *   'get@/:all/:int'    => '/$2/details?id=$3',  // 获取详情
 *   'get@/:all'         => '/$2/list',           // 获取列表
 *
 *
 *
 * 2 一种最简单的，不完全的 restful 风格：
 *  'all@/:all/:string'       => '/$2/$1$3'  将 /user/abc 映射为 /user/(method)abc
 *
 *
 *
 * 3 带有版本控制的 url 尤其是当 app 更新迭代时，要考虑新老版本共存的问题。
 *   方案有两种，一种是在header中设置，一种是直接在url上体现。
 *   header中设置，需要您自己在api中自行判断。或者修改Route类以支持。
 *   url上体现比较简单，一般也分为两种: /v1/user or /user?version=1
 *   对于 /v1/user 这种形式，给出的一种方案是直接映射到 applications\v1\controller/userController
 *
 *
 * 4 url 美化
 *   可以直接在规则中定义，
 *   如  '/admin' => '/super/index/index'
 *   加载 super/controller/indexController->indexAction();
 *   暂不支持 group，但是可通过 '/admin/:all' => '/super/$1' 的形式将所有 admin 映射到 super路径。
 *
 *
 */
