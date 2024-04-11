<?php
namespace domain\base\request;

use think\exception\ValidateException;
use think\Validate;

/*
 * https://www.kancloud.cn/manual/thinkphp5/129356
 */
class BaseRequest extends Validate
{

    public function __construct(array $rules = [], $message = [], $field = []){
        if(!empty($rules)){ $rules = $this->rule; $message = $this->rule; $field = $this->field;}
        parent::__construct($rules, $message, $field);
    }

    /*
     * 追加某个字段的验证规则
     */
    protected function append($field, $rule = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $rule) {
                $this->append($key, $rule);
            }
        } else {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }
            $this->append[$field] = $rule;
        }

        return $this;
    }

    /*
     * 追加数据验证的场景
     */
    protected function makeScene($scene = '')
    {
        $this->{'scene'.ucwords($scene)}();
        foreach ($this->rule as $k=>&$v){
            if(isset($this->append[$k])){
                $str = implode('|',$this->append[$k]);
                if ($v == '') {
                    $v .= $str;
                } else {
                    $v .= '|' . $str;
                }
            }
        }
    }

    //json 验证规则
    protected function jsonCheck( $value, $rule = '', $data = '', $field = '' ){
        $data=json_decode($value,true);
        if( empty($data) ){
            return $field . ' 必须是非空的Json格式';
        }
        return true;
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