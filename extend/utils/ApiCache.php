<?php

namespace extend\utils;

use think\Cache;

class ApiCache
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
     * notes: 生成哈希KEY - 通过类和函数
     * @param $classAndMethod - 类和函数命名空间, 如: SampleController::class.'@sampleIndex'
     * @return string
     * @author 陈鸿扬 | @date 2022/4/28 11:49
     */
    public static function makeHKeyByClassMethod($classAndMethod)
    {
        $hKey = config("redis.prefix") . 'api_cache:' . implode("_", (explode('\\', $classAndMethod)));
        return $hKey;
    }

    /**
     * notes: 生成 QUERY KEY/ HEADER KEY - 通过GET请求参数
     * @param array $requestQuery
     * @param array $requestHeader
     * @param array $filter
     * @return string
     * @author 陈鸿扬 | @date 2022/4/28 14:13
     */
    public static function makeQueryKeyByRequest(array $requestQuery, array $requestHeader = [], array $filter = [])
    {
        //排除缓存控制参数
        if (isset($requestQuery['_time'])) {
            unset($requestQuery['_time']);
        }

        //生成header参数key
        $query = '';
        if (!empty($requestHeader)) {
            $temp = [];
            if (!empty($filter)) {
                array_walk($filter, function ($key) use ($requestHeader, &$temp) {
                    if (isset($requestHeader[$key])) {
                        $temp[$key] = $requestHeader[$key];
                    }
                });
                $requestHeader = $temp;
            }
            ksort($requestHeader);
            $query .= '/&' . http_build_query($requestHeader);
        }

        if (!empty($requestQuery)) {
            //生成get参数key
            ksort($requestQuery);
            $query .= '/&' . http_build_query($requestQuery);
        }

        //防止空值
        if (empty($query)) {
            $query = '-';
        }

        return $query;
    }

    /**
     * notes: 缓存集合存储
     * @param $hKey - 哈希键 - 相当数据集合名称
     * @param string $queryKey - 子键-get请求参数-字典序升序排列文本
     * @param \Closure $closure - 闭包返回数据 - 必须是数组,不要对象
     * @param int $expire
     * @return mixed
     * @author 陈鸿扬 | @date 2022/4/28 13:00
     */
    public function collect($hKey, $queryKey, \Closure $closure, $expire = 300)
    {
        //缓存开关
        $time = request()->get('_time', 0);

        //缓存
        if ($expire > 0 && $time == 0) {
            //api查询缓存
            $data = $this->getDataByMineKey($hKey, $queryKey);
            if (!$data) {
                $result = $closure();
                //api设置缓存
                $data = $this->setDataByMineKey($hKey, $queryKey, json_encode($result, JSON_UNESCAPED_UNICODE), $expire);
            }
            $result = json_decode($data, true);
            //dd(1,$expire,$time,$result);//
        } //实时+更新缓存
        else if ($expire > 0 && $time == 1) {
            $result = $closure();
            //api设置缓存
            $this->setDataByMineKey($hKey, $queryKey, json_encode($result, JSON_UNESCAPED_UNICODE), $expire);
        } //不缓存
        else if ($expire <= 0) {
            $result = $closure();
        } else {
            $result = $closure();
        }

        return $result;
    }

    //缓存集合筛选
    //(new ApiCache)->getCollect($hKey, '&user_id=1')
    public function getCollect($hKey, $queryKey = null, $wildcard = '.*')
    {
        $data       = [];
        $tempMapArr = [];
        $allData    = $this->Redis->hgetall($hKey);
        if (!empty($allData)) {
            array_walk($allData, function ($value, $keyName) use ($hKey, $queryKey, &$tempMapArr, $wildcard) {
                preg_match("/(" . $wildcard . $queryKey . $wildcard . ")/", $keyName, $match);
                if (isset($match[0])) {
                    $tempMapArr[$keyName] = $value;
                    //$tempMapArr[$keyName] = json_decode($value,true);
                }
            });
            $allData = $tempMapArr;
            ksort($allData);
        }
        $data["data"] = $allData;
        $data["meta"] = [
            "total" => count($allData)
        ];
        return $data;
    }

    //缓存集合清理
    //(new ApiCache)->dropCollect($hKey,'&user_id=1')
    public function dropCollect($hKey, $queryKey = null, $wildcard = '.*')
    {
        //#清除整个集合
        if (empty($queryKey)) {
            return $this->Redis->expire($hKey, -1);
        }

        //#清除局部数据
        //获取所有 子KEY
        $hKeysArr = $this->Redis->hKeys($hKey);
        if (!empty($hKeysArr)) {
            $tempKeysArr = [];
            array_walk($hKeysArr, function ($keyName) use ($hKey, $queryKey, &$tempKeysArr, $wildcard) {
                preg_match("/(" . $wildcard . $queryKey . $wildcard . ")/", $keyName, $match);
                if (isset($match[0])) {
                    $tempKeysArr[] = $keyName;
                }
            });
            if (!empty($tempKeysArr)) {
                $this->Redis->hDel($hKey, $tempKeysArr);
                $this->updateDbInfo($hKey);
                $hKeysArr = $tempKeysArr;
            }
        }
        return $hKeysArr;
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