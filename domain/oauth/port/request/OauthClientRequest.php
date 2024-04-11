<?php

namespace domain\oauth\port\request;

use domain\base\request\BaseRequest;
use think\exception\ValidateException;

/*
 * https://www.kancloud.cn/manual/thinkphp5/129356
 */
class OauthClientRequest extends BaseRequest
{
    //验证规则
    protected $rule = [
        //@validate
		"id"=>"integer|gt:0|length:0,20",
		"scope_id"=>"alphaDash|length:0,255",
		"client"=>"alphaDash|length:0,255",
		"client_id"=>"alphaDash|length:0,255",
		"client_secret"=>"alphaDash|length:0,255",
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
		
		'scope_id.chs'=>'授权范围-标记 包含非法字符-只能是/汉字',
		'scope_id.chsAlphaNum'=>'授权范围-标记 包含非法字符-只能是/汉字/字母/数字',
		'scope_id.chsDash'=>'授权范围-标记 包含非法字符',
		'scope_id.alpha'=>'授权范围-标记 包含非法字符-只能是/字母',
		'scope_id.alphaNum'=>'授权范围-标记 包含非法字符-只能是/字母/数字',
		'scope_id.alphaDash'=>'授权范围-标记 包含非法字符',
		'scope_id.length'=>'授权范围-标记 最大长度是 255',
		
		'client.chs'=>'授权客户端-描述 包含非法字符-只能是/汉字',
		'client.chsAlphaNum'=>'授权客户端-描述 包含非法字符-只能是/汉字/字母/数字',
		'client.chsDash'=>'授权客户端-描述 包含非法字符',
		'client.alpha'=>'授权客户端-描述 包含非法字符-只能是/字母',
		'client.alphaNum'=>'授权客户端-描述 包含非法字符-只能是/字母/数字',
		'client.alphaDash'=>'授权客户端-描述 包含非法字符',
		'client.length'=>'授权客户端-描述 最大长度是 255',
		
		'client_id.chs'=>'授权客户端-标记 包含非法字符-只能是/汉字',
		'client_id.chsAlphaNum'=>'授权客户端-标记 包含非法字符-只能是/汉字/字母/数字',
		'client_id.chsDash'=>'授权客户端-标记 包含非法字符',
		'client_id.alpha'=>'授权客户端-标记 包含非法字符-只能是/字母',
		'client_id.alphaNum'=>'授权客户端-标记 包含非法字符-只能是/字母/数字',
		'client_id.alphaDash'=>'授权客户端-标记 包含非法字符',
		'client_id.length'=>'授权客户端-标记 最大长度是 255',
		
		'client_secret.chs'=>'授权客户端-密匙 包含非法字符-只能是/汉字',
		'client_secret.chsAlphaNum'=>'授权客户端-密匙 包含非法字符-只能是/汉字/字母/数字',
		'client_secret.chsDash'=>'授权客户端-密匙 包含非法字符',
		'client_secret.alpha'=>'授权客户端-密匙 包含非法字符-只能是/字母',
		'client_secret.alphaNum'=>'授权客户端-密匙 包含非法字符-只能是/字母/数字',
		'client_secret.alphaDash'=>'授权客户端-密匙 包含非法字符',
		'client_secret.length'=>'授权客户端-密匙 最大长度是 255',
		
		'create_time.date'=>'创建时间 日期时间格式有误',
		'create_time.dateFormat'=>'创建时间 自定义日期格式有误',
		
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
        //return $this->append('name', 'require');
    }
    public function sceneUpdate(){
        //return $this->append('id', 'require');
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