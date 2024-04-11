<?php

namespace domain\demo\port\trans;

use domain\base\tran\BaseTran;

/**
 * notes: 应用层-输出转化类
 * 说明: 当输出前需要对数据做遍历处理时, 在 ApiResponse 执行时 调用此转化器逻辑, 对每一列数据 做统一处理.
 */
class EsSampleTrans extends BaseTran
{
    //Model输出对象-转化器
    public function transform($item)
    {
        //对每一行数据字段做输出转换和过滤


        return $item;
    }

}