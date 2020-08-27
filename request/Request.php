<?php
namespace request;

use core\Config;
/**
 * 请求拦截器
 */
class Request{
    private $filter;
    private $error;
    function __construct(){
        $this->error;
        $this->filter = [];
        // nfewwnlrzcinbfch
    }
    /**
     *
     */
    function toFilter(){
        Config::set( 'system.context', [' request filter changed system.context.'] );
    }
}
