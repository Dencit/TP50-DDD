<?php

namespace extend\utils;

use think\Cache;

class ABTest
{
    protected $Redis;

    public function __construct($table_num = 0)
    {
        $this->Redis = Cache::store('redis')->handler();

        if ($table_num == 0) {
            //框架设置的库
            $table_num = config('cache.select');
            return $this->Redis->select($table_num);
        } else {
            //另外选一个库
            return $this->Redis->select($table_num);
        }
    }

    /**
     * AB交替
     * @param $key - 缓存key
     * @param int $primaryId - 用户粒度的AB交替
     * @param $expireType - AB缓存集-重置周期
     * @param  $num - AB缓存集-重置周期-倍数
     * @return mixed
     */
    public function rotateMode($key, $primaryId = 0, $expireType = 'day', $num = 1)
    {
        //计算现在距离当天最后1秒,有多少秒.
        $format  = "Y-m-d";
        $addTime = "+ " . $num . " day";
        switch ($expireType) {
            case "day" :
                $format  = "Y-m-d";
                $addTime = "+ " . $num . " day";
                break;
            case "week" :
                $format  = "Y-m-d";
                $addTime = "+ " . $num . " week";
                break;
            case "month" :
                $format  = "Y-m";
                $addTime = "+ " . $num . " month";
                break;
            case "year" :
                $format  = "Y";
                $addTime = "+ " . $num . " year";
                break;
        }
        $endDate = date($format, strtotime($addTime));
        $now     = time();
        $end     = strtotime($endDate);
        $expire  = $end - $now;
        //dd($endDate, $expire);//

        $cacheKey = config("redis.prefix") . 'ab_test:' . $key . ':rotate_mode';
        $data     = $this->getDataByMineKey($cacheKey, $primaryId);
        if (!$data) {
            $data = $this->setDataByMineKey($cacheKey, $primaryId, 'A', $expire);
        } else if ($data && $data == "B") {
            $data = $this->setDataByMineKey($cacheKey, $primaryId, 'A', $expire);
        } else if ($data && $data == "A") {
            $data = $this->setDataByMineKey($cacheKey, $primaryId, 'B', $expire);
        }
        return $data;
    }

    /**
     * 奇偶数AB交替
     * @param $key - 缓存key
     * @param $parityNum - 奇偶数id依据
     * @param int $primaryId - 用户粒度的AB交替
     * @param $expireType - AB缓存集-重置周期
     * @param  $num - AB缓存集-重置周期-倍数
     * @return mixed
     */
    public function parityNumMode($key, $parityNum, $primaryId = 0, $expireType = 'day', $num = 1)
    {
        //区分奇偶数id
        $sign = "A"; //奇数
        If ($parityNum % 2 == 0) {
            $sign = "B"; //偶数
        }
        //计算现在距离当天最后1秒,有多少秒.
        $format  = "Y-m-d";
        $addTime = "+ " . $num . " day";
        switch ($expireType) {
            case "day" :
                $format  = "Y-m-d";
                $addTime = "+ " . $num . " day";
                break;
            case "week" :
                $format  = "Y-m-d";
                $addTime = "+ " . $num . " week";
                break;
            case "month" :
                $format  = "Y-m";
                $addTime = "+ " . $num . " month";
                break;
            case "year" :
                $format  = "Y";
                $addTime = "+ " . $num . " year";
                break;
        }
        $endDate = date($format, strtotime($addTime));
        $now     = time();
        $end     = strtotime($endDate);
        $expire  = $end - $now;
        //dd($endDate, $expire);//

        $cacheKey = config("redis.prefix") . 'ab_test:' . $key . ':parity_num_mode';
        $data     = $this->getDataByMineKey($cacheKey, $primaryId);
        if (!$data) {
            $data = $this->setDataByMineKey($cacheKey, $primaryId, $sign, $expire);
        } else {
            $data = $this->setDataByMineKey($cacheKey, $primaryId, $sign, $expire);
        }
        return $data;
    }

    //获取数据
    public function getDataByMineKey($hKey, $queryKey)
    {
        return $this->Redis->hGet($hKey, $queryKey);
    }

    //保存数据
    public function setDataByMineKey($hKey, $queryKey, $value, $expire = 300)
    {
        //设置db集合全局信息
        $this->setDbInfo($hKey, $expire);
        //子数据添加
        $this->Redis->hSet($hKey, $queryKey, $value);
        //更新db集合全局信息
        $this->updateDbInfo($hKey);
        //
        return $this->Redis->hGet($hKey, $queryKey);
    }

    //设置db集合全局信息
    protected function setDbInfo($hKey, $expire = 300)
    {
        $hKeysArr = $this->Redis->hKeys($hKey);
        if (empty($hKeysArr)) {
            //哈希键不存在时,需要先设置过期时间, 作用于所有子键.
            $this->Redis->hSet($hKey, 'db_total', 0);
            $this->Redis->hSet($hKey, 'db_expire', $expire);
            $this->Redis->hSet($hKey, 'db_create_time', date('Y-m-d H:i:s', time()));
            $this->Redis->hSet($hKey, 'db_update_time', date('Y-m-d H:i:s', time()));
            $this->Redis->expire($hKey, $expire);
        }
    }

    //更新db集合全局信息
    protected function updateDbInfo($hKey)
    {
        $hKeysArr = $this->Redis->hKeys($hKey);
        if (!empty($hKeysArr)) {
            //哈希键存在时,子数据添加.
            $this->Redis->hMset($hKey, ['db_total' => count($hKeysArr) - 4]);
            $this->Redis->hMset($hKey, ['db_update_time' => date('Y-m-d H:i:s', time())]);
        }
    }

}