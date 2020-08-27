<?php
namespace applications\api\params;

use core\Match;
/**
 *
 */
class testParams{
    /**
     * 这只是个例子。
     */
    static function tttt( $p ){
        $rule = new match();
        $rule->match( 'id' )
            ->info( '请提供id，且必须是正整数' )
            ->rule( 'int', 'values > 0' )
            ->required();
        // 拒绝接收，自己给default。凭空捏造，
        $rule->match( 'create_time' )->refuse();
        $rule->default( time() );

        // 验证之前，先用函数修改格式。你给了10， 就返回你11.
        // 前端要是给了时间字符串，你也可以转换为时间戳。return strtotime($t);
        // 自由发挥吧。
        $rule->match( 'ttt' )->matchBefore( function($t){
            return $t + 1;
        }, 'this' );
        $rule->default( 0 );

        // 验证长度。而且rule可以接收匿名函数
        $rule->match( 'title' )->rule( 'length between 1 255' );
        $rule->rule( function($t){
            if( $t == "title" ){
                return true;
            }
            return false;
        }, 'this' );
        $rule->required()->info( "title 的值必须是title");

        // 别名, 验证通过后，sss会变成 bbb。
        $rule->match( 'sss' )->alias( 'bbb' );

        // contrary可以对结果取反。下面写法，判断数据不在数组 [0,1] 中。
        $rule->match( 'phone' )->rule( "in_array", 'this', [0,1] )->contrary()->required()->info('aaaa');
        // 由于 empty isset 不是函数，所以只能用匿名函数写：
        // $rule->rule( function($t){return empty($t)}, 'this' );

        // 验证，并且返回正确的数据。
        // 失败了会内部抛异常，response拦截器会拦截。
        return $rule->proving( $p );
    }

    static function insert( $p ){
        $pms = new Match();

        $pms->match( 'title' )
            ->info( '标题不能为空,长度不能超过255' )
            ->rule( 'length between 1 255' )
            ->required();

        $pms->match( 'price' )
            ->rule( 'int' )
            ->default( 23 )
            ->info( '价格必须是数字' );

        $pms->match( 'photo' )
            ->default( 'UploadFiles/course/20170525/1495684939875551.jpg' );

        $pms->match( 'intro' )->default( '' );
        // 下面这些，传进来也被过来掉，只拿默认值
        $pms->match('class_hour')->refuse()->default(1);
        $pms->match('people')->refuse()->default("people");
        $pms->match('teach_goals')->refuse()->default('dddd');
        $pms->match('opentime')->refuse()->default( time() );
        $pms->match('addtime')->refuse()->default( time() );

        return $pms->proving( $p );
    }

}
