<?php

namespace domain\admin\port\request;

use domain\base\request\BaseRequest;
use think\exception\ValidateException;

class AdminRequest extends BaseRequest
{
    //验证规则
    protected $rule = [
        //@validate
		"id"=>"integer|gt:0|length:0,20",
		"name"=>"chsDash|length:0,255",
		"avatar"=>"alphaDash|length:0,255",
		"sex"=>"integer|in:0,1,2",
		"mobile"=>"alphaDash|length:0,30",
		"pass_word"=>"alphaDash|length:0,255",
		"client_driver"=>"chsDash",
		"lat"=>"float",
		"lng"=>"float",
		"role"=>"alphaDash|length:0,255",
		"status"=>"integer|in:1,2",
		"on_line_time"=>"date",
		"off_line_time"=>"date",
		"create_time"=>"date",
		"update_time"=>"date",
		"delete_time"=>"date",
		//@validate
    ];
    //修改 验证项错误 返回描述
    protected $message  =   [
        //@message
		'id.integer'=>'主键ID 必须是整数',
		'id.gt'=>'主键ID 必须大于0',
		'id.max'=>'主键ID 超出最大值',
		'id.min'=>'主键ID 超出最小值',
		'id.in'=>'主键ID 数值超出许可范围',
		'id.length'=>'主键ID 最大长度是 20',
		
		'name.chs'=>'管理员名称 包含非法字符-只能是/汉字',
		'name.chsAlphaNum'=>'管理员名称 包含非法字符-只能是/汉字/字母/数字',
		'name.chsDash'=>'管理员名称 包含非法字符',
		'name.alpha'=>'管理员名称 包含非法字符-只能是/字母',
		'name.alphaNum'=>'管理员名称 包含非法字符-只能是/字母/数字',
		'name.alphaDash'=>'管理员名称 包含非法字符',
		'name.length'=>'管理员名称 最大长度是 255',
		
		'avatar.chs'=>'管理员头像 包含非法字符-只能是/汉字',
		'avatar.chsAlphaNum'=>'管理员头像 包含非法字符-只能是/汉字/字母/数字',
		'avatar.chsDash'=>'管理员头像 包含非法字符',
		'avatar.alpha'=>'管理员头像 包含非法字符-只能是/字母',
		'avatar.alphaNum'=>'管理员头像 包含非法字符-只能是/字母/数字',
		'avatar.alphaDash'=>'管理员头像 包含非法字符',
		'avatar.length'=>'管理员头像 最大长度是 255',
		
		'sex.integer'=>'性别 必须是整数',
		'sex.gt'=>'性别 必须大于0',
		'sex.max'=>'性别 超出最大值',
		'sex.min'=>'性别 超出最小值',
		'sex.in'=>'性别 数值超出许可范围',
		'sex.length'=>'性别 最大长度是 3',
		
		'mobile.chs'=>'绑定手机 包含非法字符-只能是/汉字',
		'mobile.chsAlphaNum'=>'绑定手机 包含非法字符-只能是/汉字/字母/数字',
		'mobile.chsDash'=>'绑定手机 包含非法字符',
		'mobile.alpha'=>'绑定手机 包含非法字符-只能是/字母',
		'mobile.alphaNum'=>'绑定手机 包含非法字符-只能是/字母/数字',
		'mobile.alphaDash'=>'绑定手机 包含非法字符',
		'mobile.length'=>'绑定手机 最大长度是 30',
		
		'pass_word.chs'=>'密码 包含非法字符-只能是/汉字',
		'pass_word.chsAlphaNum'=>'密码 包含非法字符-只能是/汉字/字母/数字',
		'pass_word.chsDash'=>'密码 包含非法字符',
		'pass_word.alpha'=>'密码 包含非法字符-只能是/字母',
		'pass_word.alphaNum'=>'密码 包含非法字符-只能是/字母/数字',
		'pass_word.alphaDash'=>'密码 包含非法字符',
		'pass_word.require'=>'密码 不能为空',
		'pass_word.length'=>'密码 最大长度是 255',
		
		'client_driver.chs'=>'客户端信息 包含非法字符-只能是/汉字',
		'client_driver.chsAlphaNum'=>'客户端信息 包含非法字符-只能是/汉字/字母/数字',
		'client_driver.chsDash'=>'客户端信息 包含非法字符',
		'client_driver.length'=>'客户端信息 超出最大长度 是65536',
		
		'lat.number'=>'坐标 必须是数字或小数',
		'lat.gt'=>'坐标 必须大于0',
		'lat.max'=>'坐标 超出最大值',
		'lat.min'=>'坐标 低于最小值',
		'lat.in'=>'坐标 数值超出许可范围',
		'lat.length'=>'坐标 最大长度是 10',
		
		'lng.number'=>'坐标 必须是数字或小数',
		'lng.gt'=>'坐标 必须大于0',
		'lng.max'=>'坐标 超出最大值',
		'lng.min'=>'坐标 低于最小值',
		'lng.in'=>'坐标 数值超出许可范围',
		'lng.length'=>'坐标 最大长度是 10',
		
		'role.chs'=>'管理员角色 包含非法字符-只能是/汉字',
		'role.chsAlphaNum'=>'管理员角色 包含非法字符-只能是/汉字/字母/数字',
		'role.chsDash'=>'管理员角色 包含非法字符',
		'role.alpha'=>'管理员角色 包含非法字符-只能是/字母',
		'role.alphaNum'=>'管理员角色 包含非法字符-只能是/字母/数字',
		'role.alphaDash'=>'管理员角色 包含非法字符',
		'role.length'=>'管理员角色 最大长度是 255',
		
		'status.integer'=>'状态 必须是整数',
		'status.gt'=>'状态 必须大于0',
		'status.max'=>'状态 超出最大值',
		'status.min'=>'状态 超出最小值',
		'status.in'=>'状态 数值超出许可范围',
		'status.length'=>'状态 最大长度是 3',
		
		'on_line_time.date'=>'登录时间 日期时间格式有误',
		'on_line_time.dateFormat'=>'登录时间 自定义日期格式有误',
		'on_line_time.require'=>'登录时间 不能为空',
		
		'off_line_time.date'=>'登出时间 日期时间格式有误',
		'off_line_time.dateFormat'=>'登出时间 自定义日期格式有误',
		'off_line_time.require'=>'登出时间 不能为空',
		
		'create_time.date'=>'创建时间|注册时间 日期时间格式有误',
		'create_time.dateFormat'=>'创建时间|注册时间 自定义日期格式有误',
		
		'update_time.date'=>'更新时间 日期时间格式有误',
		'update_time.dateFormat'=>'更新时间 自定义日期格式有误',
		'update_time.require'=>'更新时间 不能为空',
		
		'delete_time.date'=>'删除时间 日期时间格式有误',
		'delete_time.dateFormat'=>'删除时间 自定义日期格式有误',
		'delete_time.require'=>'删除时间 不能为空',
		
		//@message
    ];

    //edit 验证场景 定义方法
    //例子: $this->only(['name','age']) ->append('name', 'min:5') ->remove('age', 'between') ->append('age', 'require|max:100');
    public function sceneCreate(){
		return $this
			->append('name', 'require')
			->append('mobile', 'require')
			->append('pass_word', 'require')
			;
    }
    public function sceneUpdate(){
        //return $this->append('id', 'require');
    }
    public function sceneLogin(){
        return $this
			->append('mobile', 'require')
			->append('pass_word', 'require')
			;
    }
    public function sceneDetail(){
        //return $this->append('id', 'require');
    }
    public function sceneDelete(){
        //return $this->append('id', 'require');
    }

    //验证默认
    public function checkValidate($requestInput){
        if (!$this->check( $requestInput )) {
            throw new ValidateException(  $this->getError() );
        }
    }

    //验证场景
    public function checkSceneValidate($scene,$requestInput){
		$this->makeScene($scene);
        if (!$this->scene($scene)->check( $requestInput )) {
            throw new ValidateException(  $this->getError() );
        }
    }



}