<?php
namespace core\Db;

use core\Config;
/**
 * 连接器。
 */
class Connect{
    static public $lastSql = "";    // 存储sql信息，用于调试
    static public $moreSql = [];    // 存储sql信息，用于调试
    static private $that   = NULL;
    static private $conn   = NULL;
    static private $config = [];
    static private $debug  = false;
    /**
     * 禁止 new Connect();
     */
    protected function __construct(){
        Connect::$debug  = Config::get( 'system.runtime.console' );
        Connect::$config = Config::get( 'system.Db' );
        Connect::ping();
    }
    /**
     *
     */
    static public function getInstance(){
        // Connect::ping();
        if( Connect::$that == NULL ){
            Connect::$that = new Connect();
        }
        return Connect::$that;
    }
    public function query( $sql ){
        Connect::$lastSql = $sql;
        if( Connect::$debug ){
            Connect::$moreSql[] = $sql;
        } else {
            Connect::ping();
        }
        return static::$conn->query( $sql );
    }
    public function getConn(){
        return Connect::$conn;
    }
    // TODO: 脚本内的延时写的有点问题额。。回头要优化
    static private function ping( $n=0 ){
        if( $n >=3 ){
            return false;
        }
        $conf = connect::$config;
        if( connect::$conn == NULL ){
            connect::$conn = new \mysqli(
                $conf[ 'host' ],
                $conf[ 'user' ],
                $conf[ 'pswd' ],
                $conf[ 'database' ]
            );
        }
        if( !static::$conn->query("SET NAMES {$conf[ 'charset' ]}") ){
            static::$conn = NULL;
            static::ping( $n+1 );
        }
    }
}
