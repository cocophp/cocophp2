<?php
namespace core;

use core\Db\Connect;
/**
 * 数据库类。下面所有语句均继承接口  core\Db\DbInterface
 * 包括core\Db文件夹下的 Mysql、Postgre 均继承接口  core\Db\DbInterface
 * 注意，这里的写法并不是为了实现单例，而是仅仅为了实现任意函数的静态调用后链式写法。
 * 如 Db::other()->other();
 */
class Db{
    static public $that;
    protected $table;
    protected $prefix;
    protected $prefixStr;

    protected $sqlTpl;
    protected $args;
    protected $error;

    protected $_transations = 0;
    /**
     * 根据配置文件加载sql服务。
     */
    private function __construct(){
        $this->prefix    = Config::get('system.Db.prefix');
        $this->prefixStr = Config::get('system.Db.prefixStr');
    }
    protected function singleton(){
        $class = get_called_class();
        if( empty( static::$that[$class] ) ){
            static::$that[$class] = new static;
        }
        return static::$that[$class];
    }
    function table( $table ){
        $that = static::singleton();
        $prefix = config::get( 'system.Db.prefix', '' );
        $that->table = $prefix . $table;
        return $that;
    }
    /**
     * command 一般用于执行sql模板，模板内数据需要通过 argv() 函数注入，
     */
    static public function command( $sql ){
        $that = static::singleton();
        $that->sqlTpl = $sql;
        return $that;
    }
    /**
     * 请谨慎使用此函数
     * 该函数与command最大的区别，就是不会解析sql模板，所以，任何sql攻击这里都不会防备。
     * 该函数会执行指定sql，如 Db::Mysql()->query( "...." );
     * 但是不会获取结果，想要获取结构，需要 toArray()等方法。
     */
    public function query( $sql ){
        return Connect::getInstance()->query( $sql );
    }
    /**
     * 调试用三个函数，
     */
    /**
     * 获取最后执行的sql语句。
     */
    public function getLastSql(){
        return Connect::$lastSql;
    }
    /**
     * 获取所有执行到的sql语句。
     */
    public function getMoreSql(){
        return Connect::$moreSql;
    }
    /**
     * 获取sql执行错误信息。
     */
    public function getErrors(){
        return Connect::getInstance()->getConn()->error;
    }
    /**
     * 事务三联,为了实现事务嵌套，这里使用了 SAVEPOINT（保存点)的形式。
     */
    public function begin(){
        $that = static::singleton();
        if( !$that->_transations ){
            $that->_transations++;
            return Connect::getInstance()->query( 'begin' );
        }
        return Connect::getInstance()->query(
            'SAVEPOINT transaction'.$that->_transations++
        );
    }
    public function rollback(){
        $that = static::singleton();
        $that->_transations--;
        if( !$that->_transations ){
            return Connect::getInstance()->query( 'rollback' );
        }
        return Connect::getInstance()->query(
            'rollback to savepoint transaction'.$that->_transations
        );
    }
    public function commit(){
        $that = static::singleton();
        $that->_transations--;
        if( !$that->_transations ){
            return Connect::getInstance()->query( 'commit' );
        }
        return Connect::getInstance()->query(
            'release savepoint transaction'.$that->_transations
        );
    }
    /**
     * 直接提供了事务级的函数。接受一个闭包函数
     */
    static public function transactions( $func, ...$args ){
        $that = static::singleton();
        try {
            $that->begin();
            $res = $func( ...$args );
            $that->commit();
            return $res;
        } catch (\Exception $e) {
            $that->rollback();
            throw $e;
        }
    }
    /**
     * 参数注入函数，一般配合command()共同使用，以过滤掉sql攻击。
     */
    public function argv( ...$args ){
        $that = static::singleton();
        array_walk_recursive( $args, function($a, $b)use($that){
            $that->args[ "?$b" ] = $that->__filterString( $a );
        } );
        return $that;
    }
    /**
     * whereIn() 函数动态注入参数并替换掉模板中的 $tpl ,
     * 如 Db::command( 'select * from a where id in()' )->whereIn( $ids );
     * 若存在多个in条件，可自己加入锚点，如
     * Select * from a where id in(id) and name in(username)
     * ->whereIn( $ids, 'in(id)' )->whereIn( $names, 'in(username)' )->toArray()
     */
    public function whereIn( $args, $tpl = "in()" ){
        $temp = [];
        $that = static::singleton();
        foreach ($args as $value) {
            if( !empty( $value ) ){
                $temp[] = $that->__filterString( $value );
            }
        }
        if( empty( $temp ) ){
            return $that;
        }
        $that->args[ $tpl ] = "IN(" . implode( ',', $temp ) . ")";
        return $that;
    }
    /**
     * limit的哥哥，分页函数。可直接指定如 ->page( 2, 20 ) 来获取第二页数据(每页20条)。
     */
    public function page( $page_on, $page_size ){
        $page_size = (int)$page_size;
        $page_on   = (int)$page_on > 0 ? ($page_on-1)*$page_size : 0;

        $that = static::singleton();
        return $that->limit( $page_on, $page_size );
    }
    /**
     * 依旧提供了limit函数，是因为某些时刻，我们需要的不只是分页拿数据。
     */
    public function limit( $page_on, $page_size=false ){
        $that = static::singleton();

        $page_on   = (int)$page_on;
        $tmp = "";
        if( $page_size === false ){
            $tmp = " LIMIT {$page_on}";
        } else {
            $page_size = (int)$page_size;
            $tmp = " LIMIT {$page_on},{$page_size}";
        }
        $that->args[ 'limit()' ] = $tmp;
        return $that;
    }
    /**
     * insert三联。其实insertMore只是读起来更顺畅一点罢了。
     */
    /**
     * insert === insertMore
     */
    public function insert( ...$args ){
        $that = static::singleton();
        return $that->insertMore( ...$args );
    }
    /**
     * insert === insertMore
     */
    public function insertMore( ...$argv ){
        $data  = array();
        $datas = array();
        $field = array();
        $hasOcc= true;
        $index = 0;
        $that = static::singleton();
        if( empty($argv) ){
            return $that;
        }
        foreach ( $argv as $key => $value ) {
            if( empty($value) ){
                continue;
            }
            $data  = array();
            $index = 0;
            foreach ($value as $f => $d) {
                $data[] = $that->__filterString( $d );
                if( $hasOcc ){
                    $field[ "`$f`" ] = $index++;
                    continue;
                }
                if( !isset( $field["`$f`"] ) ){
                    throw new \Exception( "The table field($f) is Must be provided", 0 );
                }
                if( $field["`$f`"] != $index++ ){
                    throw new \Exception( "The table field($f) is misplaced", 0 );
                }
            }
            $hasOcc = false;
            $datas[] = '(' . implode( ',', $data ) . ')';
        }
        if( empty( $datas ) ){
            return $that;
        }
        $that->sqlTpl = "INSERT INTO `{$that->table}`(" . implode(',', array_keys($field)) . ") VALUES" . implode(',', $datas);
        $that->args = [];
        return $that;
    }
    /**
     * insertOne强制只写入第一条数据。
     */
    public function insertOne( ...$argv ){
        $datas = array();
        $field = array();
        $that  = static::singleton();
        $that->sqlTpl = "INSERT INTO `{$that->table}`";
        foreach ( $argv as $value ) {
            foreach ( $value as $f => $d ) {
                $datas[] = $that->__filterString( $d );
                $field[] = "`$f`";
            }
            break;
        }
        $that->sqlTpl .= '(' . implode(',', $field) . ') VALUES(' . implode(",", $datas ) .')';
        $that->args = [];
        return $that;
    }
    /**
     * 更新。这里的更新where可以是sql模板也可以是数组。
     * 此处的where若传入数组，则每一项都是and关系。
     * 若复杂where的业务情况，where请插入模板，然后通过argv注入参数，如：
     * Db::table( 'a' )->update( $update, 'where id=? or name=?')->argv()
     * Db::table( 'a' )->update( $update, 'where (id=? or name=?) and is_del=?')->argv()
     */
    public function update( $field, $where = '' ){
        $that  = static::singleton();
        $that->sqlTpl = "UPDATE `{$that->table}` SET ";
        $that->args = [];
        $temp = [];
        foreach ( $field as $f => $d ) {
            $temp[] = "`$f`=" . $that->__filterString( $d );
        }
        $that->sqlTpl .= implode( ',', $temp );
        if( empty( $where ) ){
            return $that;
        }
        if( is_string( $where ) ){
            $that->sqlTpl .= " WHERE " . $where;
            return $that;
        }
        $temp = [];
        $that->sqlTpl .= " WHERE ";
        foreach ( $where as $f => $d ) {
            $temp[] = "`$f`=" . $that->__filterString( $d );
        }
        $that->sqlTpl .= implode( ' AND ', $temp );
        return $that;
    }
    /**
     * 此处的updateMore,会根据指定field更新对应数据，如：
     * $data = [ ['id'=>1,'name'=>'zhangsan'], ['id'=>2,'name'=>'lisi'] ];
     * Db::table('a')->updateMore( 'id', $data )
     * 会更新id==1的name为zhangsan，id==2的name为lisi
     * 注意此处会生成一条sql，所以本身是事务性的，成功则全成功，单条失败则全失败。
     */
    public function updateMore( $filed, ...$argv ){
        $upField = array();
        $inField = array();
        $hasOcc  = true;
        $that    = static::singleton();
        if( empty( $argv ) ){
            return $that;
        }
        foreach ($argv as $key => $data) {
            foreach ($data as $column => $value) {
                if( $column == $filed ) {
                    $inField[] = $that->__filterString($value);
                    continue;
                }
                if( $hasOcc ){
                    $upField[ $column ] = "`$column` = CASE `$filed`";
                }
                if( !isset( $upField[ $column ] ) ) {
                    throw new \Exception( $column . " 在下标 " . $key . " 中不存在，请仔细核对原数组", 0 );
                }
                if( !isset( $data[ $filed ] ) ){
                    throw new \Exception( "更新数组中必须提供条件字段", 0 );
                }
                $upField[ $column ] .= " WHEN " . $that->__filterString($data[ $filed ]) . ' THEN ' . $that->__filterString($value);
            }
            $hasOcc = false;
        }
        if( empty( $upField ) ){
            return $that;
        }
        $that->args = [];
        $that->sqlTpl = "UPDATE `{$that->table}` SET " .  implode( ' END, ', $upField )
            .  " END WHERE `$filed` IN(" . implode( ',', $inField ) . ")";
        return $that;
    }
    /**
     * 删除必须指定where条件。规则同update函数的where。
     */
    public function delete( $where = '' ){
        if( empty( $where ) ){
            throw new \Exception("Delete must give a where", 0);
        }
        $that    = static::singleton();
        $that->sqlTpl = "DELETE FROM `{$that->table}` WHERE ";

        if( is_string( $where ) ){
            $that->sqlTpl .= $where;
            return $that;
        }
        $temp = [];
        foreach ( $where as $f => $d ) {
            $temp[] = "`$f`=" . $that->__filterString( $d );
        }
        $that->sqlTpl .= implode( ' AND ', $temp );
        return $that;
    }
    /**
     * 生成最终的sql语句。
     * TODO: 此处有个小bug，类似于formatData的sql语句，会有问题，如：
     * Select FROM_UNIXTIME(o.create_time,%Y-%m-%d) as `time` ... 的sql语句会报错，
     * 只能通过以下两种形式：
     * Db::command("Select FROM_UNIXTIME(o.create_time,?)")->argv('%Y-%m-%d')
     * Db::command("Select FROM_UNIXTIME(o.create_time,'%%Y-%%m-%%d')")
     * 也就是原模板中带有%会有问题。 只能通过转移或argv注入。
     */
    public function toSql(){
        $that = static::singleton();
        if( !empty( $that->args ) ){
            $that->sqlTpl = str_replace(
                    array_keys($that->args),
                    array_values($that->args),
                    $that->sqlTpl
            );
            // $that->sqlTpl = sprintf( $that->sqlTpl, ...$that->args );
            $that->args = [];
        }
        $that->sqlTpl = str_replace( $that->prefixStr, $that->prefix, $that->sqlTpl );
        return $that->sqlTpl;
    }
    /**
     * 除事务的三个函数，调试的三个函数之外，上面的所有函数都是注入sql模板和参数，并不执行。
     * 下面
     */
    /**
     * 执行。若sql报错，理论上不返回任何数据。后面版本中同样如此。
     */
    public function toExec(){
        $that = static::singleton();
        return $that->toBool();
    }
    /**
     * 执行sql，并返回sql执行状态。
     */
    public function toBool(){
        $that = static::singleton();
        $sql  = $that->toSql();
        if( empty( $sql ) ){
            return true;
        }
        $res = Connect::getInstance()->query( $sql );
        if( $res === false ){
            $that->error = Connect::getInstance()->getConn()->error;
            return false;
        }
        $that->sqlTpl = '';
        return true;
    }
    /**
     * select会返回数组，其他均返回 firstID和rowCount。
     * 理论上,一次insertMore的id连续自增的.返回的firstID和rowCount可以确定全部插入数据ID.
     * 在单主写入的情况下是这样,若双主互备份,或者设置过自增id步长度,那id就不再是连续的,
     * 后面情况可以采用分布式id方式在脚本类直接生成全部来解决.
     *
     * 若您希望查询器做点事情，可传入相关函数。返回值会是最终结果。
     */
    public function toArray( $mode = 'all', $func = null, ...$args ){
        //拿到sql类型。
        $sqlMode = '';
        $that = static::singleton();
        for ($i=0; $i < strlen($that->sqlTpl); $i++) {
            if( in_array( $that->sqlTpl[$i], [" ","　","\n","\r","\t"] ) ){
                if( $sqlMode != '' ){
                    break;
                }
                continue;
            }
            $sqlMode = $sqlMode . $that->sqlTpl[$i];
        }
        $sqlMode = strtolower( $sqlMode );
        $res = Connect::getInstance()->query( $that->toSql() );
        if( $res === false ){
            $that->error = Connect::getInstance()->getConn()->error;
            if( $sqlMode == 'select' ){
                return [];
            }
            return [
                'rowCount'=> 0,
                'firstID' => 0,
            ];
        }

        $r = array();
        if( $sqlMode == 'select' ){
            foreach ( $res as $value) {
                if( $func ){
                    $value = $func( $value, ...$args );
                }
                if( $mode == 'one' ){
                    return $value;
                }
                $r[] = $value;
            }
            return $r;
        }
        return [
            'rowCount'=> Connect::getInstance()->getConn()->affected_rows,
            'firstID' => Connect::getInstance()->getConn()->insert_id,
        ];
    }
    /**
     * 将查询结果转换为json字符串。
     * 参数同toArray()
     */
    public function toJson( $mode = 'all', $func = null, ...$args ){
        $that = static::singleton();
        return json_encode( $that->toArray( $mode, $func, ...$args ) );
    }
    /**
     * 参数过滤器。此处只防sql截断攻击，不防sql注入和存储型xss攻击。
     * 您可以根据自身业务调整此函数。
     */
    protected function __filterString( $str ){
        return "\"" . addslashes( $str ) . "\"";
    }
}
// Db::$that = new Db();
