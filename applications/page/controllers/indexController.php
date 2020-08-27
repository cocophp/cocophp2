<?php
namespace applications\page\controllers;

use core\Config;
use lib\Curls;

class indexController{
    function indexAction(){
        return "Hello, CocoPHP!!" . @Config::get( 'system.context' )[0];
    }
}
