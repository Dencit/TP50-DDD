<?php
namespace domain\user\port\trans;

use domain\base\tran\BaseTran;

class UserTrans extends BaseTran
{

    public function transform($item)
    {
        //对每一行数据字段做输出转换和过滤

//v关联模型区域



//^关联模型区域

        return $item;
    }

}