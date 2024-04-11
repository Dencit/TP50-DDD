<?php

namespace domain\oauth\port\trans;

use domain\base\tran\BaseTran;
use domain\user\port\trans\UserTrans;

class OauthTokenTrans extends BaseTran
{

    public function transform($item)
    {
        //对每一行数据字段做输出转换和过滤

//v关联模型区域

        //{@hidden
//        if (isset($item['user']) && in_array('user', $this->includeArr) ) {
//            //调用副表同名转化器 - 转换关联副表字段
//            $packData = $this->_include($item, 'user', UserTrans::class, 'transform');
//            if ($packData) {
//                $item['user'] = $packData;
//            }
//        }
        //@hidden}

//^关联模型区域

        return $item;
    }

}