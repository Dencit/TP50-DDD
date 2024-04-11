<?php

namespace domain\admin\error;

use domain\base\error\BaseError;

/**
 * notes: 根模块-总错误码
 * desc: 错误码区间,根据模块下的 doc.md 定义来设置. 注意 按数据单元做好注释, 每个单元错误码预留20位数间隔.
 */
class AdminRootError extends BaseError
{
    protected static $data = [
        //默认
        "ID_NOT_FOUND"         => ['code' => 200000, 'msg' => 'ID 不存在'],
        "ID_NOT_UNIQUE"        => ['code' => 200001, 'msg' => 'ID 已存在'],
        "BATCH_IDS_NOT_FOUND"  => ['code' => 200002, 'msg' => '批量数据中 有ID不存在'],
        "BATCH_IDS_NOT_UNIQUE" => ['code' => 200003, 'msg' => '批量数据中 有ID已存在'],
        //自定义
        //# admin表 数据单元 使用
        "MOBILE_NOT_FOUND"     => ['code' => 202003, 'msg' => '手机号 不存在'],
        "MOBILE_NOT_UNIQUE"    => ['code' => 202004, 'msg' => '手机号 已存在'],
        "PASS_WORD_WRONG"      => ['code' => 202005, 'msg' => '密码错误']

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

