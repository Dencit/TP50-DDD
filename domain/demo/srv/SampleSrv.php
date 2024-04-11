<?php

namespace domain\demo\srv;

use domain\base\srv\BaseSrv;

/**
 * notes: 领域层-业务类
 * desc: 当不同 应用端/模块 的 应用层-业务类,对同一个表数据(或第三方API)进行操作, 该表的操作代码分散在多个应用端中且冗余, 就需要抽象到这一层.
 * 领域层-业务类 允许 被 跨应用端/模块 调用, 而 各应用层-业务 则保持隔离, 避免应用层业务耦合.
 * 调用原则: 向下调用[仓储类,第三方服务-SDK]
 */
class SampleSrv extends BaseSrv
{

    //插入和更新时,检查关键数据.
    public static function checkData(&$requestInput){

    }

//V# 指令函数区域

//{@block_cmd}
    /*
     * 命令行 - 模板
     */
    public function sampleCmd($param = null)
    {

        //dd($param);//
        return true;
    }
//{@block_cmd/}

//A# 指令函数区域

}