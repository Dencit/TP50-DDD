<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use Symfony\Component\VarDumper\VarDumper;
use extend\log\backTrace;

//兼容-打印函数
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            VarDumper::dump($v);
        }
        exit(1);
    }
}
//兼容-获取项目根目录
if (!function_exists('root_path')){
    // 获取应用根目录
    function root_path()
    {
        return ROOT_PATH;
    }
}
//兼容-获取应用根目录
if (!function_exists('base_path')){
    // 获取应用基础目录
    function base_path($path = '')
    {
        return APP_PATH . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}
//兼容-获取应用根目录
if (!function_exists('app_path')) {
    // 获取当前应用目录
    function app_path($path = '')
    {
        return APP_PATH . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('bt')) {
    function bt($filter = '', $debug = false)
    {
        $finalArr = backTrace::run($filter, $debug);
        header('content-type:application/json');
        exit(json_encode($finalArr));
    }
}