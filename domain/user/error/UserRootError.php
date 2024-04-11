<?php

namespace domain\user\error;

use domain\base\error\BaseError;

/**
 * notes: 根模块-总错误码
 * desc: 错误码区间,根据模块下的 doc.md 定义来设置. 注意 按数据单元做好注释, 每个单元错误码预留20位数间隔.
 */
class UserRootError extends BaseError
{
    protected static $data = [
        //默认
        "ID_NOT_FOUND"      => ['code' => 201000, 'msg' => '用户ID 不存在'],
        "ID_NOT_UNIQUE"     => ['code' => 201001, 'msg' => '用户ID 已存在'],
        //自定义
        //# user表 数据单元 使用
        "MOBILE_NOT_FOUND"  => ['code' => 201002, 'msg' => '手机号 不存在'],
        "MOBILE_NOT_UNIQUE" => ['code' => 201003, 'msg' => '手机号 已存在'],
        "PASS_WORD_WRONG"   => ['code' => 201004, 'msg' => '密码错误']

        //# ...
    ];

    static function code($type)
    {
        return self::$data[$type]['code'];
    }

    static function msg($type)
    {
        return self::$data[$type]['msg'];
    }

}

