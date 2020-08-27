<?php
namespace core;

use core\Config;
/**
 *
 */
class Core{
    /**
     * mode 用于区分 生产/开发 环境
     */
    public $mode;
    /**
     * 将路由信息通过 route 注入。
     */
    public $route;
    public $response;
    public $request;
    /**
     * config 用于注入要加载的配置文件。
     */
    public $config;

    function __construct( $mode ){
        $this->mode = $mode;
    }
    /**
     * execute为框架流。
     * @return string
     */
    function execute(){
        try {
            // 初始化配置文件。
            Config::init( $this->config, $this->mode );
            // 此处完全是为了防止某些人在config中定义了 system.mode
            Config::set( 'system.runtime.envMode', $this->mode );
            // 写入请求模式。
            Config::set( 'system.runtime.requestMethod', strtolower($_SERVER['REQUEST_METHOD']) );
            // 解析自定义路由。
            $this->route->registry( Config::get( 'system.route.bootstrap', [] ) );
            // 解析路由。
            $this->route->analysis();
            // 请求拦截
            $this->route->toIntercept();
            // 将权限转交到业务控制器。
            $controller = $this->route->getController();
            $action = $this->route->getAction();
            // $res  = $controller::$action();
            $res = (new $controller())->$action();
            // 响应拦截
            return $this->response->toFilter( $res );
        // 以下是异常情况处理。全部转交给响应拦截器的toError()处理
        }catch (\Exception $e) {
            return $this->response->toError( $e, 'Exception' );
        }
        catch( \Throwable $e ){
            return $this->response->toError( $e, 'Throwable' );
        }
        catch( \Error $e ){
            return $this->response->toError( $e, 'Error' );
        }
    }
    function __toString(){
        return (string)$this->execute();
    }
}
