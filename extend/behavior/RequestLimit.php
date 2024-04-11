<?php

namespace extend\behavior;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use think\Cache;

/**
 * 接口防止表单重复提交
 *
 * Class RequestLimit
 * @package Behavior
 */
class RequestLimit
{
    public function run(&$params)
    {
        //接口请求限制
        $this->requestAccess(1, 30);
    }

    /**
     * 接口请求限制
     * $time: 重复请求, 限制 ?秒 请求1次
     * $limit:重复请求, 限制 1分钟内 最多请求?次
     * @param int $time
     * @param int $limit
     * @return bool
     */
    function requestAccess($time = 3, $limit = 30)
    {
        //获取访问用户的IP
        $ip = md5(request()->ip());
        //获取访问的接口路径
        $path = request()->path();
        //将IP和访问的接口路径md5加密成一个字符串，这样子就代表同一个客户访问的接口。

        $prefix = config('cache.redis')['prefix'] ?? '';
        $redis  = Cache::store('redis')->handler();

        //将每个请求的IP地址、参数和路径拼接成同一个用户的一个完全相同的接口.
        $tokenStr = '';
        $token    = request()->header('token');
        if (!empty($token)) {
            $tokenStr = json_encode(["token" => $token]);
        }
        $paramStr = '';
        $param    = request()->param();
        if (!empty($param)) {
            $paramStr = json_encode($param);
        }
        //dd($path, $paramStr, $tokenStr);//

        $name = $prefix . 'request_time:' . md5($path . $paramStr . $tokenStr);

        //每个相同的数据多少时间内不能请求
        $cache = $redis->get($name);
        if ($cache == $ip) {
            Exception::app(BaseError::code('TOO_FAST_REQUESTS'), BaseError::msg('TOO_FAST_REQUESTS'));
            return false;
        } else {
            $redis->set($name, $ip, $time);
        }


        $UV = $prefix . 'request_limit:' . md5($ip . $path);
        //每个IP和接口每分钟不能超过的次数
        $cacheIpLimit = $redis->get($UV) ?: 0;
        if ($cacheIpLimit) {
            if ($cacheIpLimit > $limit) {
                Exception::app(BaseError::code('TOO_MANY_REQUESTS'), BaseError::msg('TOO_MANY_REQUESTS'));
                return false;
            } else {
                $redis->incr($UV, 1);
            }
        } else {
            $redis->set($UV, 1, 60);
        }

        return true;
    }

}