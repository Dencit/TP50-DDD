<?php

namespace domain\base\controller;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use think\App;
use think\Request;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = Request::instance();

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    //批处理闭包
    protected function batchDone($batchData, \Closure $closure)
    {
        foreach ($batchData as $ind => $item) {
            if (is_string($item)) {
                Exception::http(BaseError::code('WRONG_BATCH_DATA'), BaseError::msg('WRONG_BATCH_DATA'));
            }
            $closure($item);
        }
    }

    //数组排除输入字段
    protected function arrayExcept(&$array, $rules)
    {
        if (is_array($rules)) {
            foreach ($rules as $ind => $val) {
                if (isset($array[$val])) {
                    unset($array[$val]);
                }
            }
        }
    }

    //数组限制输入字段
    protected function arrayOnly(&$array, $rules)
    {
        if (is_array($rules)) {
            $temp = [];
            foreach ($rules as $ind => $val) {
                if (isset($array[$val])) {
                    $temp[$val] = $array[$val];
                }
                $array = $temp;
            }
        }
    }

}
