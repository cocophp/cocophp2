<?php
namespace response;

use response\Std;
use core\Config;
/**
 * 响应拦截器
 */
class Response{
    /**
     * 用于处理正常情况下的数据返回。
     */
    function toFilter( $res ){
        if( is_array( $res ) or is_object( $res ) ){
            return json_encode( $res );
        }
        if( is_numeric( $res ) ){
            return (string)$res;
        }
        return $res;
    }
    /**
     * 用于处理异常情况下的数据返回。看您心情自定义。
     */
    function toError( $e, $type ){
        if( Config::get('system.runtime.envMode') == 'test' ){
            return $this->test( $e, $type );
        }
        return $this->prod( $e, $type );
    }
    function prod( $e, $type ){
        $code = $e->getCode();
        if( $code ){
            return json_encode([
                'status' => false,
                'message'=> $e->getMessage(),
            ]);
        }
        return json_encode([
            'status' => false,
            'message'=> '系统繁忙，请稍后重试',
            'debug'  => $e->getMessage(),
        ]);
    }
    /**
     * 用于处理异常情况下的错误返回。
     */
    function test( $e, $type ){
        $res = '';
        $envMode = Config::get( 'system.runtime.envMode' );
        if( $envMode == 'console' ){
            $res = "$t: {$e->getMessage()}\n";
            foreach ($e->getTrace() as $key => $value) {
                $res .= "In file {$value['file']} line {$value['line']} : " .
                        "{$value['class']} {$value['type']} {$value['function']}()\n";
            }
            return $res;
        }

        if( $envMode == "prod" ){
            return "System internal error";
        }
        $res = "<h2>$type: {$e->getMessage()}</h2>\n" .
               "<p> In file {$e->getFile()} line {$e->getLine()} : \n";
        foreach ($e->getTrace() as $key => $value) {
            $res .= "<p> In file {$value['file']} line {$value['line']} : " .
                    "{$value['class']} {$value['type']} {$value['function']}()</p>\n";
        }
        return $res . "<style>.a:{text-indent:20px;}</style>";
    }
}
