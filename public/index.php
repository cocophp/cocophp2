<?php

// 注册自动加载机 autoload.
spl_autoload_register( function ( $class ){
    // namespace和文件路径匹配。所以直接加载就可以了。
    $file = '../' . str_replace( '\\' , '/', $class ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

use core\Core;
use core\Route;
// use request\Request;
use response\Response;
/**
 * new Core 's argv 用于区分当前环境，请严格区分生产和开发环境
 * 业务中您可以通过 Config::get('system.mode') 得到。
 */
$core = new Core( 'test' );
/**
 * 自定义路由规则请在config中指定。
 * Route类指定了默认硬路由规则。您可以自定义Route类
 */
$core->route    = new Route( $_SERVER['REQUEST_URI'] );
// 请求拦截器，不在需要。
// $core->request  = new Request();
// 返回拦截器。
$core->response = new Response();

// 要加载的配置文件。若项目需要，自定义此处。
$core->config = [
    'system',
    'rsa',
    'database',
    'route',
    'applications'
];
/**
 * $core->__toString() 会自动转化要输出内容。
 * 此处会执行 $core->execute() 函数。
 */
echo $core;
