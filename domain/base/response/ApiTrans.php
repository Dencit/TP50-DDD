<?php

namespace domain\base\response;

class ApiTrans
{
    private static $result;

    public function __construct()
    {
        self::$result=[];
        self::$result['code']=0;
        self::$result['message']='success';
        self::$result['data']=[];
    }

    //统一输出结构
    public static function response(&$data, $statusCode = 200, $code = 0, $message = 'success')
    {
        $respData['code'] = $code;
        $respData['message'] = $message;

        if (isset($data['data'])) {
            //列表场景
            $respData['data']['data'] = $data['data'];
            if (isset($data['meta'])) {
                $respData['data']['meta'] = $data['meta'];
                //移除原始数据中的meta
                unset($respData['data']['data']['meta']);
            }
        }else{
            //单行场景
            $respData['data'] = $data;
            if (isset($data['meta'])) {
                $respData['meta'] = $data['meta'];
                //移除原始数据中的meta
                unset($respData['data']['meta']);
            }
        }
        return response($respData, $statusCode,['Content-Type'=>'application/application/json; charset=utf-8'],'json');
    }

//v数组输出区域

    /**
     * notes: 输出单行数组
     * @param array $item - 待输出数据
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transformArray()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:55
     */
    public static function item(array $item = [], $transformClassName = null, $methodName = 'transformArray')
    {
        $data = [];
        if ($transformClassName) {
            $transform = (new $transformClassName)->{'getIncludeArr'}();
            $newArr = $transform->{$methodName}($item);
            //转换器可直接返回 null, 列表会跳过空数据.
            if (!empty($newArr)) {
                $data = $newArr;
            }
        } else {
            $data = $item;
        }
        self::$result['data'] = $data;

        return self::$result['data'];
    }

    /**
     * notes: es输出单行数组 - 未调试
     * @param array $data - 待输出数据
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transformArray()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:55
     */
    public static function esItem(array $data = [], $transformClassName = null, $methodName = 'transformArray')
    {
        if (isset($data['data']) && !empty($transformClassName)) {
            $data = json_decode(json_encode($data['data']));
        }
        self::$result['data'] = $data;

        return self::$result['data'];
    }

    /**
     * notes: 输出多行数组
     * @param array $list - 待输出数据
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transformArray()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:55
     */
    public static function itemList(array $list = [], $transformClassName = null, $methodName = 'transformArray')
    {
        $newCollect = [];
        if ($transformClassName) {
            $transform = (new $transformClassName)->{'getIncludeArr'}();
            foreach ($list as $index => $item) {
                $newArr = $transform->{$methodName}($item);
                //转换器可直接返回 null, 列表会跳过空数据.
                if (!empty($newArr)) {
                    $newCollect[] = $newArr;
                }
            }
        } else {
            $newCollect = $list;
        }
        self::$result['data'] = $newCollect;

        return self::$result['data'];
    }

    /**
     * notes: 输出多行数组-带翻页
     * @param array $data - 待输出数据
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transformArray()
     * @return array
     * @author 陈鸿扬 | @date 2022/4/12 16:37
     */
    public static function itemPageList($data = [], $transformClassName = null, $methodName = 'transformArray')
    {
        //数据对象 转 数组
        if (gettype($data) == 'array') {
            $newCollect = [];
            if ($transformClassName) {
                $transform = (new $transformClassName)->{'getIncludeArr'}();
                foreach ($data['data'] as $k => $value) {
                    $newArr = $transform->{$methodName}($value);
                    //转换器可直接返回 null, 列表会跳过空数据.
                    if (!empty($newArr)) {
                        $newCollect[] = $newArr;
                    }
                }
            }
            $data['data'] = $newCollect;
        }

        self::$result['data'] = $data;

        return self::$result['data'];
    }

    /**
     * notes: es输出多行数组 - 未调试
     * @param array $data - 待输出数据
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transformArray()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:55
     */
    public static function esItemList(array $data = [], $transformClassName = null, $methodName = 'transformArray')
    {
        $dataTamp = [];
        if (isset($data['data']) && !empty($transformClassName)) {
            $dataTamp = json_decode(json_encode($data['data']));
            if (count($dataTamp) > 0) {
                $transform = (new $transformClassName)->{'getIncludeArr'}();
                array_walk($dataTamp, function (&$item) use ($transformClassName, $methodName, $dataTamp, $transform) {
                    $item = $transform->{$methodName}($dataTamp);
                });
            }
            $data = $dataTamp;
        }
        self::$result['data'] = $data;

        return self::$result['data'];
    }

//^数组输出区域


//v模型对象输出区域

    /**
     * notes: 输出单行对象
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function read($data = [], $transformClassName = null, $methodName = 'transform')
    {
        //对象转数组
        if( gettype($data) == 'object' ){
            $data = $data->toArray();
        }

        if ($transformClassName) {
            $transform = (new $transformClassName)->{'getIncludeArr'}();
            $data = $transform->{$methodName}($data);
            if(!empty($data)) {
                array_walk($data, function (&$item, $index) use (&$data) {
                    if ($item === null) {
                        unset($data["$index"]);
                    }
                });
            }
        }
        self::$result['data'] = $data;

        return self::$result['data'];
    }

    /**
     * notes: 输出多行对象
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function index($data = [], $transformClassName = null, $methodName = 'transform')
    {
        if (isset($data['data'])) {
            //有翻页数据
            array_walk($data['data'],function(&$item){
                $item = $item->toArray();
            });
        } else {
            //无翻页数据
            if( method_exists($data,'toArray') ) {
                $tempData = $data->toArray();
            }else{
                $tempData = json_decode(json_encode($data),true);
            }
            unset($data);
            $data['data'] = $tempData;
        }

        $newCollect = [];
        if ($transformClassName) {
            $transform = (new $transformClassName)->{'getIncludeArr'}();
            foreach ($data['data'] as $k => $value) {
                $newArr = $transform->{$methodName}($value);
                //转换器可直接返回 null, 列表会跳过空数据.
                if (!empty($newArr)) {
                    array_walk($newArr, function (&$item, $index) use (&$newArr) {
                        if ($item === null) {
                            unset($newArr["$index"]);
                        }
                    });
                    $newCollect[] = $newArr;
                }
            }
            $data['data'] = $newCollect;
        }

        self::$result['data'] = $data;
        return self::$result['data'];
    }

    /**
     * notes: 新增数据结果返回
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function save($data = [], $transformClassName = null, $methodName = 'transform')
    {
        return self::read($data, $transformClassName, $methodName);
    }

    /**
     * notes: 批量新增数据结果返回
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function batchSave($data = [], $transformClassName = null, $methodName = 'transform')
    {
        return self::index($data, $transformClassName, $methodName);
    }

    /**
     * notes: 批量更新数据结果返回
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function batchUpdate($data = [], $transformClassName = null, $methodName = 'transform')
    {
        return self::index($data, $transformClassName, $methodName);
    }

    /**
     * notes: 更新数据结果返回
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function update($data = [], $transformClassName = null, $methodName = 'transform')
    {
        return self::read($data, $transformClassName, $methodName);
    }

    /**
     * notes: 输出数据结果返回
     * @param array $data - 待输出数据对象 - 模型结果集对象
     * @param null $transformClassName - 转化器类名称
     * @param string $methodName - 转化器类方法 - 默认调用 transform()
     * @return array
     * @author 陈鸿扬 | @date 2022/3/30 14:59
     */
    public static function delete($data = [], $transformClassName = null, $methodName = 'transform')
    {
        return self::read($data, $transformClassName, $methodName);
    }

//^模型对象输出区域

}