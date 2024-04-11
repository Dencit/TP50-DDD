<?php

namespace domain\user\model;

use domain\base\model\BaseModel;
use traits\model\SoftDelete;

/**
 * notes: 领域层-模型类
 * 说明: 负责基础层的工作,字段过滤(模型黑白名单),用户权限(模型策略),触发器(模型事件),等一系列传统DBA负责的工作.
 */
class UserModel extends BaseModel
{
    // 设置当前模型的数据库连接
    //protected $connection = 'database'; //当前库不用配置,配了事务失效.

    // 模型名（默认为当前不含后缀的模型类名）
    protected $name = 'users';

    // 主键名（默认为id）
    protected $pk = 'id';
    //自动写入创建和更新的时间戳字段（默认关闭）
    protected $autoWriteTimestamp = true;          // true/false/datetime
    protected $createTime = 'create_time'; // false/{string}/create_time
    protected $updateTime = 'update_time'; // false/{string}/update_time
    //启用软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time'; // false/{string}/delete_time
    protected $defaultSoftDelete = null;          //默认值: 0-未删除, 1-已删除

    // 设置json类型字段
    protected $json = ['info'];

    //字段设置类型自动转换 - https://www.kancloud.cn/manual/thinkphp5/138669
    protected $type = [
        //@types
        "id"            => "bigint",
        "nick_name"     => "varchar",
        "avatar"        => "varchar",
        "sex"           => "tinyint",
        "mobile"        => "varchar",
        "pass_word"     => "varchar",
        "client_driver" => "varchar",
        "client_type"   => "tinyint",
        "lat"           => "numeric",
        "lng"           => "numeric",
        "role"          => "varchar",
        "status"        => "tinyint",
        "on_line_time"  => "datetime",
        "off_line_time" => "datetime",
        "create_time"   => "datetime",
        "update_time"   => "datetime",
        "delete_time"   => "datetime",
        //@types
    ];

    //更新排除
    protected $readonly = [
        //@guarded
        "id",
        "create_time",
        //@guarded
    ];

    //输出过滤
    public static $hiddenRuler = [
        'delete_time',
    ];

    //定义全局的查询范围 - 只有 delete_time 字段,其它字段不要写这里.
    protected function base(&$query)
    {
        $query->where(['delete_time' => $this->defaultSoftDelete]);
    }

    //int时间戳字段 转换
    public function getCreateTimeAttr($value)
    {
        if (is_int($value)) {
            $value = date('Y-m-d H:i:s', $value);
        }
        return $value;
    }

    //int时间戳字段 转换
    public function getUpdateTimeAttr($value)
    {
        if (is_int($value)) {
            $value = date('Y-m-d H:i:s', $value);
        }
        return $value;
    }

    //模型事件 - https://www.kancloud.cn/manual/thinkphp5/135195
    public static function init()
    {
        //新增前
        self::event('before_insert', function ($model) {
            self::tableFieldFilter($model);//提交数据过滤
            //die('before_insert');//
        });
        //新增后
        self::event('after_insert', function ($model) {
            //die('after_insert');//
        });
        //更新前
        self::event('before_update', function ($model) {
            self::tableFieldFilter($model);//提交数据过滤
            //die('before_update');//
        });
        //更新后
        self::event('after_update', function ($model) {
            //die('after_update');//
        });
        //写入前
        self::event('before_write', function ($model) {
            self::tableFieldFilter($model);//提交数据过滤
            //die('before_write');//
        });
        //写入后
        self::event('after_write', function ($model) {
            //die('after_write');//
        });
        //删除前
        self::event('before_delete', function ($model) {
            //die('before_delete');//
        });
        //删除后
        self::event('after_delete', function ($model) {
            //die('after_delete');//
        });
        //恢复前
        self::event('before_restore', function ($model) {
            //die('before_restore');//
        });
        //恢复后
        self::event('after_restore', function ($model) {
            //die('after_restore');//
        });
    }

    /*
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'id', 'userid')->field('userid,sex');
    }
    */


}