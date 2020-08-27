<?php
namespace core;

use core\Config;
/**
 *
 */
class Route{
    private $url;
    private $rule;
    private $pres;
    private $controller;
    private $action;
    private $method;
    private $intercept;

    function __construct( $url ){
        $this->url  = parse_url( $url )['path'];
        $this->intercept = [];
        Config::set( 'system.runtime.requestRaw',  $url );
    }
    /**
     * 解析自定义路由。暂不支持匿名函数的形式。
     */
    public function registry( $url ){
        // 防止匹配不到的时候，那不到这个参数。
        Config::set( 'system.runtime.requestUrl', $this->url );

        $t = Config::get( 'system.route.rule', [] );
        $this->rule = array_keys( $t );
        $this->pres = array_values( $t );
        $this->method= Config::get( 'system.runtime.requestMethod' );

        foreach ( $url as $key => $value ) {
            $u = $this->url;
            // 分析是否需要带上请求模式。
            if( preg_match( '/^(get|post|delete|put|all)@/', $key ) ){
                $u = $this->method.'@'.$this->url;
            }
            // 转换 $key 为正则并匹配
            $preg = $this->toPreg( $key );
            // var_dump( $preg );
            if( !preg_match( $preg, $u ) ){
                continue;
            }
            if( is_string($value) ){
                $value = [ $value ];
            }
            // var_dump( preg_replace( $preg, $value, $u ) );
            // 若解析目标是string 则认为是路由转换。
            if( is_string( $value[0] ) ){
                $this->url = preg_replace( $preg, $value[0], $u );
                Config::set( 'system.runtime.requestPreg', $key );
                Config::set( 'system.runtime.requestUrl',  $this->url );
                if( !isset( $value[1] ) ){
                    return;
                }
                if( is_string($value[1]) ){
                    $value[1] = [ $value[1] ];
                }
                $this->intercept = $value[1];
                return;
            }
        }
    }
    /**
     * url解析为对应的控制器及方法。
     */
    public function analysis(){
        // 由于引入了自定义route，这里就变得简单明了，
        // 也就是直接修改配置项中的 '/' 指定域名主页
        if( empty($this->url) ){
            throw new \Exception("The request has no API defined", 0);
        }
        $t = explode( '?', $this->url );
        // 有解析到的参数。
        if( isset( $t[1] ) ){
            $this->decrypt_args( $t[1] );
        }
        $this->decrypt_url( $t[0] );
    }
    /**
     * 解析url中的参数，请注意，这里会直接覆盖掉 $_GET 参数。
     */
    function decrypt_args( $s ){
        $key = '';
        $val = '';
        $len = strlen($s);
        $res = [];
        for ($i=0; $i < $len; $i++) {
            if( $s[$i] == '=' ){
                $i++;
                for(; $i<$len; $i++ ){
                    if( $s[$i] == '&' ){
                        $_GET[ $key ] = $val;
                        $key = '';
                        $val = '';
                        $i++;
                        break;
                    }
                    $val = $val . $s[$i];
                }
                $_GET[ $key ] = $val;
                if( $i >= $len ){
                    break;
                }
            }
            $key = $key . $s[$i];
        }
    }
    /**
     * 解析url
     * 硬编码做了一些规定： /m/c/a 这种路由，最后一个肯定是action，倒数第二个是controller.
     * 为了简化系统(或防止url发生迷惑行为)，若找不到，则并不会去获取default，而是抛出异常。
     * 您可以通过继承本类并重写该方法以支持。
     */
    function decrypt_url( $u ){
        $actionSuffix     = Config::get( 'system.style.actionSuffix' );
        $controllerSuffix = Config::get( 'system.style.controllerSuffix' );

        $t = explode( '/', $u );
        // 过滤空，如 /a/b////c 将会解析为 /a/b/c
        foreach ( $t as $key => $value) {
            if( empty($value) ){
                unset( $t[$key] );
            }
        }
        // 得到 action
        $count = count( $t );
        if( $count == 0 ){
            throw new \Exception( "Must setting default action", 0 );
        }
        $this->action = $t[ $count ] . $actionSuffix;
        // 得到 Controller
        unset( $t[ $count-- ] );
        if( count($t) == 0 ){
            throw new \Exception( "Must setting default controller", 0 );
        }

        $this->controller = '\\controllers\\' . $t[ $count ];
        unset( $t[ $count ] );
        $this->controller = 'applications\\' . implode('\\',$t) . $this->controller . $controllerSuffix;
        // 写入到config中以便业务中会用到。
        Config::set( 'system.runtime.controller', $this->controller );
        Config::set( 'system.runtime.action', $this->action );
    }
    function getController(){
        return $this->controller;
    }
    function getAction(){
        return $this->action;
    }
    function toIntercept(){
        $temp = null;
        foreach ($this->intercept as $key => $object) {
            $temp = new $object();
            $temp->toFilter();
        }
    }
    /**
     * 转换为正则。
     */
    function toPreg( $url ){
        $url = str_replace( '/', '\/', $url );
        $url = str_replace( $this->rule, $this->pres, $url );
        return "/^$url$/";
    }
}
