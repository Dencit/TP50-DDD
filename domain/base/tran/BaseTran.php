<?php

namespace domain\base\tran;

/**
 * notes: 转化器基础 - Response 返回数据时 调用的转化结构
 * Class BaseTransform
 * @package app\demo\transform
 */
class BaseTran
{
    //缓存requestQuery
    protected $requestQuery = [];

    //url ?_include = user,admin 获取到的 自定义关联模型名 集合
    protected $includeArr = [];

    //获取 自定义关联模型名 集合
    public function getIncludeArr()
    {
        //缓存requestQuery
        $this->requestQuery = request()->get();

        $includes = request()->get('_include');
        $this->includeArr = explode(',', $includes);
        return $this;
    }
    //设置 自定义关联模型名 集合
    public function setIncludeArr($includes)
    {
        $includeArr = explode(',', $includes);
        $this->includeArr = array_merge($this->includeArr,$includeArr);
        return $this;
    }

    //
    public function _include(& $itemResult, String $includeSign, string $transformClassName, string $methodName = 'transform')
    {
        //关联对象存在才处理
        if (isset($itemResult["$includeSign"])) {
            $temp = $itemResult["$includeSign"];
            $keys = array_keys($temp);
            $firstKey = $keys[0] ?? false;
            //一对一
            if (gettype($firstKey) == 'string') {
                $transform = $this->includeBelongsTo($itemResult, $includeSign, $transformClassName, $methodName);
            }
            //一对多
            if (gettype($firstKey) == 'integer') {
                $transform = $this->includeHasMany($itemResult, $includeSign, $transformClassName, $methodName);
            }
            if( !empty($transform) ){
                return $transform;
            }
            return null;
        }
    }

    /**
     * notes: 关联模型 belongsTo 的转化器
     * @param $itemResult - 主表模型返回对象 - 包含关联模型对象
     * @param $includeSign - 截取关联模型对象 - 自定义关联模型名称
     * @param $transformClassName - 转化器 类路径
     * @param string $methodName - 转化器 类方法
     * @return bool
     * @author 陈鸿扬 | @date 2022/3/30 11:46
     */
    public function includeBelongsTo(& $itemResult, String $includeSign, string $transformClassName, string $methodName = 'transform')
    {
        if (in_array($includeSign, $this->includeArr) && !empty($itemResult)) {
            //关联到才执行
            $item = $itemResult["$includeSign"];
            if (!empty($item)) {
                $transform = (new $transformClassName)->{$methodName}($item);
                return $transform;
            }
        }

        return null;
    }

    /**
     * notes: 关联模型 hasMany 的转化器
     * @param $itemResult - 主表模型返回对象 - 包含关联模型对象
     * @param $includeSign - 截取关联模型对象 - 自定义关联模型名称
     * @param $transformClassName - 转化器 类路径
     * @param string $methodName - 转化器 类方法
     * @return array|bool
     * @author 陈鸿扬 | @date 2022/3/30 11:51
     */
    public function includeHasMany(& $itemResult, String $includeSign, string $transformClassName, string $methodName = 'transform')
    {
        if (in_array($includeSign, $this->includeArr) && !empty($itemResult)) {
            $objects = [];
            $itemList = $itemResult["$includeSign"];
            foreach ($itemList as $item) {
                //关联到才执行
                if (!empty($item)) {
                    $transform = (new $transformClassName)->{$methodName}($item);
                    $objects[] = $transform;
                }
            }
            if (empty($objects)) {
                return false;
            }
            return $objects;
        }

        return null;
    }

    //data结构与result对比,只拼装result有的字段.
    public function objectFilter($resultObj, $data)
    {
        $resultArr = $resultObj->toArray();
        return $this->arrayFilter($resultArr, $data);
    }

    /**
     * notes: 关联副表数据 插入到指定字段后面
     * @param $data - 行数据
     * @param $pos - 指定字段 - 得到上标位置
     * @param $addArr - 插入数据集合 [$objName=>$obj]
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 11:57
     */
    public function dataAfterPush(&$data, $pos, $addArr)
    {
        if (is_string($pos)) {
            $kayIndexArr = array_flip(array_keys($data));
            $pos = $kayIndexArr[$pos] + 1;
        }
        $startArr = array_slice($data, 0, $pos);
        $startArr = array_merge($startArr, $addArr);
        $endArr = array_slice($data, $pos);
        $data = array_merge($startArr, $endArr);
        return $data;
    }

    public function arrayFilter($resultArr, $data)
    {
        $keys = array_keys($resultArr);
        foreach ($data as $k => $v) {
            if (!in_array($k, $keys)) {
                unset($data[$k]);
            }
        }
        return $data;
    }


    //单行数据转化
    public static function workItem(&$itemData,$methodName='transform'){
        $trans = new static();
        $trans->getIncludeArr();
        $currData = $trans->{$methodName}($itemData);
        return $currData;
    }

    //列表数据转化
    public static function workList(&$listData,$methodName='transform'){
        $currData = [];
        $trans = new static();
        $trans->getIncludeArr();
        foreach ($listData as $index => $item ){
            $currData[] = $trans->{$methodName}($item);
        }
        return $currData;
    }

    //带翻页列表数据转化
    public static function workPageList(&$pageListData, $methodName = 'transform')
    {
        $currData = [];
        $trans = new static();
        $trans->getIncludeArr();
        foreach ($pageListData['data'] as $index => $item) {
            $currData[] = $trans->{$methodName}($item);
        }
        $pageListData['data'] = $currData;
        return $pageListData;
    }


//v扩展函数区

    //获取 header VERSION - APP_1.0.0_ID_13
    protected function getVersion($header)
    {
        $verData = [
            'body'   => 'NONE_0.0.0',
            'client' => 'none', 'version' => '0.0.0',
            'id'     => 0,
        ];
        if (isset($header['version'])) {
            $version = explode('_', $header['version']);
            if (isset($version[0]) && isset($version[1])) {
                $verData['body'] = ($version[0]) . '_' . ($version[1]);
            }
            if (isset($version[0])) {
                $verData['client'] = $version[0];
            }
            if (isset($version[1])) {
                $verData['version'] = $version[1];
            }
            if (isset($version[2]) && isset($version[3])) {
                $key = strtolower($version[2]);
                $verData[$key] = $version[3];
            }
        }
        return $verData;
    }

    /*
     * notes: 计算两点地理坐标之间的距离 - 已经调较到和ES计算结果相等
     * @param $longitude1 起点经度
     * @param $latitude1 起点纬度
     * @param $longitude2 终点经度
     * @param $latitude2 终点纬度
     * @param Int $unit 单位 1:米 2:公里
     * @param Int $decimal 精度 保留小数位数
     * @return float
     */
    protected function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
    {
        $EARTH_RADIUS = 6371.0069757804; //地球半径系数
        $PI = 3.14159265358979323846;
        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;
        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;
        if ($unit == 2) {
            $distance = $distance / 1000;
        }
        return round($distance, $decimal);
    }

//^扩展函数区

}