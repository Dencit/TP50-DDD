<?php

namespace domain\demo\port\request;

use domain\base\request\BaseRequest;
use think\exception\ValidateException;

/**
 * notes: 应用层-输入验证类
 * desc: 只在此类 统一校验输入数据.
 * 内置规则: https://www.kancloud.cn/manual/thinkphp5/129356
 * 场景验证(BaseRequest已经复刻了tp6的场景验证): https://www.kancloud.cn/manual/thinkphp6_0/1037627
 */
class SampleRequest extends BaseRequest
{

    //验证规则
    protected $rule = [
        //@validate
        "id"     => "integer|gt:0",
        "name"   => "chsDash|length:0,255",
        "type"   => "integer|in:0,1",
        "status" => "integer|in:0,1",
        //@validate
    ];

    //修改 验证项错误 返回描述
    protected $message = [
//        "id.require" => "id 不能为空",
//        "name.length" => "name 字符长度在0-255之间",
//        "id.gt" => "id 必须大于0",
//        "type.in"   => "类型 在0-1之间",
//        "status.in" => "状态 在0-1之间",
    ];

    //edit 验证场景 定义方法
    //例子: $this->only(['name','age']) ->append('name', 'min:5') ->remove('age', 'between') ->append('age', 'require|max:100');
    public function sceneSave()
    {
        return $this->append('name', 'require');
    }

    public function sceneUpdate()
    {
        //return $this->append('id', 'require');
    }

    public function sceneRead()
    {
        //return $this->append('id', 'require');
    }

    public function sceneDelete()
    {
        //return $this->append('id', 'require');
    }

}