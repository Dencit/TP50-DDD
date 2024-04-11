<?php

namespace extend\elastic;

/**
 * notes: ES文档-模型基础类
 * Class EsModel
 * @package Extend\Elastic
 * @author 陈鸿扬 | @date 2022/12/26 9:55
 */
class EsModel
{
    //单例
    private static $instance;

    //ES-table
    protected $esTable = null;

    //索引表版本号
    protected static $version = 0;

    //执行模型是否自动维护时间戳
    protected $timestamps = false;
    const CREATED_AT = null;
    const UPDATED_AT = null;

    //预载入模型
    protected static $methodGroups = [];

    //ES查询单例
    protected $esQuery = null;

    public function __construct($version = 0)
    {
        //连接ES查询
        $esOrm = (new EsOrm()); //必须重复初始化 - 防继承子类单例变量污染
        //不再区分 index/type 一律同名.
        $table = $this->esTable;

        //索引版本号
        if (!empty($version)) {
            $table .= '-' . $version;
        }
        $this->esQuery = $esOrm->table($table);
    }

    //获取当前实例
    public function getInstance()
    {
        return $this;
    }

    //获取操作实例
    public function getQuery()
    {
        return $this->esQuery;
    }

    //获取数据库名
    public function getTable()
    {
        return $this->esTable;
    }

    //获取数据库字段
    public function getFieldKeys()
    {
        $fieldKeys = [];
        if (!empty($this->casts)) {
            //casts
            $fieldKeys = array_keys($this->casts);
        }
        return $fieldKeys;
    }

    //重复初始化
    public static function init($version = 0)
    {
        self::$instance = new static($version);
        return self::$instance;
    }

    //不重复初始化 - 单例
    public static function instance($version = 0)
    {
        if (!self::$instance instanceof static) {
            self::$instance = new static($version);
        }
        return self::$instance;
    }

    //魔术方法过滤器
    public function callFilter($name, &$arguments)
    {
        //提交数据预处理
        if (!empty($arguments[0])) {
            switch ($name) {
                case 'add':
                case 'save':
                    //无论新增更新,ES都会擦除整行数据 - update_time不起作用.
                    $this->dataItemFilter('create', $arguments[0]);
                    break;
                case 'addAll':
                case 'saveAll':
                    //无论新增更新,ES都会擦除整行数据 - update_time不起作用.
                    $this->dataListFilter('create', $arguments[0]);
                    break;
            }
        }
        //dd($arguments[0]);//
    }

    //静态初始化 - 每个新继承子类 调用自己不存在的方法, 都会重置ES单例.
//    public static function __callStatic($name, $arguments)
//    {
//        self::init();
//        $argCount  = count($arguments);
//        switch ($argCount){
//            default:
//                $query = $this->esQuery->{$name}();
//                break;
//            case 1:
//                $query = $this->esQuery->{$name}($arguments[0]);
//                break;
//            case 2:
//                $query = $this->esQuery->{$name}($arguments[0],$arguments[1]);
//                break;
//            case 3:
//                $query = $this->esQuery->{$name}($arguments[0], $arguments[1], $arguments[2]);
//                break;
//            case 4:
//                $query = $this->esQuery->{$name}($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
//                break;
//        }
//        return $query;
//    }

    //动态初始化 - 每个新继承子类,连贯操作,调用自己不存在的方法,会转到顶层方法.
    public function __call($name, $arguments)
    {
        //初始化
        self::instance();
        //魔术方法过滤器
        self::callFilter($name, $arguments);
        //
        $argCount = count($arguments);
        switch ($argCount) {
            default:
                $query = $this->esQuery->{$name}();
                break;
            case 1:
                $query = $this->esQuery->{$name}($arguments[0]);
                break;
            case 2:
                $query = $this->esQuery->{$name}($arguments[0], $arguments[1]);
                break;
            case 3:
                $query = $this->esQuery->{$name}($arguments[0], $arguments[1], $arguments[2]);
                break;
            case 4:
                $query = $this->esQuery->{$name}($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                break;
        }
        return $query;
    }

    //ES原始结构
    public function toSource()
    {
        $result = $this->esQuery->toSource();
        return $result;
    }

    //ES调整结构
    public function toArray($info = false)
    {
        $result = $this->esQuery->toArray($info);
        if (!empty($result)) {
            if (isset($result['data'])) {
                $this->withListFilter($result['data']);
            } else {
                $this->withItemFilter($result);
            }
        }
        return $result;
    }

    //记录预载入模型条件
    public function with(array $modelNames)
    {
        array_walk($modelNames, function ($currName, $index) {
            $currMethod   = 'with_' . strtolower($currName);
            $methodExists = method_exists(self::$instance, $currMethod);
            if (!isset(self::$methodGroups[$currMethod]) && $methodExists) {
                $model     = self::$instance->{$currMethod}();
                $modelType = $model->model_type;
                if (!empty($modelType)) {
                    self::$methodGroups[$modelType][$currName] = $model;
                }
            }
        });
    }

    //一对一关联 - 预载入模型
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $stdStruct              = (new $related)->getQuery();
        $stdStruct->model_type  = 'belongsTo';
        $stdStruct->related     = $related;
        $stdStruct->foreign_key = $foreignKey;
        $stdStruct->owner_key   = $ownerKey;
        $stdStruct->relation    = $relation;
        return $stdStruct;
    }

    //一对多关联 - 预载入模型
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $stdStruct              = (new $related)->getQuery();
        $stdStruct->model_type  = 'hasMany';
        $stdStruct->related     = $related;
        $stdStruct->foreign_key = $foreignKey;
        $stdStruct->owner_key   = $localKey;
        return $stdStruct;
    }


    //关联预载入单行数据处理
    public function withItemFilter(array &$data)
    {
        if (!empty(self::$methodGroups['belongsTo'])) {
            $this->withItemWalk($data, 'belongsTo');
        }
        if (!empty(self::$methodGroups['hasMany'])) {
            $this->withItemWalk($data, 'hasMany');
        }
    }

    //关联预载入单行数据处理-连接载入数据
    protected function withItemWalk(array &$itemData, string $modelType)
    {
        array_walk(self::$methodGroups[$modelType], function ($model, $key) use (&$itemData, $modelType) {

            $ownerKey   = $model->owner_key;
            $foreignKey = $model->foreign_key;
            $ownerId    = $itemData[$ownerKey];

            switch ($modelType) {
                case 'belongsTo':
                    //预载模型数据-取列id
                    $foreignResult = $model->where($foreignKey, $ownerId)->first()->toArray(true);
                    if (!empty($foreignResult)) {
                        $itemData[$key] = $foreignResult;
                    }
                    break;
                case 'hasMany':
                    //预载模型数据-取列id
                    $foreignResult = $model->where($foreignKey, $ownerId)->select()->toArray(true);
                    if (!empty($foreignResult)) {
                        array_walk($foreignResult['data'], function ($item, $index) use (&$itemData, $ownerId, $foreignKey, $key) {
                            //查找插入位置
                            if ($item[$foreignKey] == $ownerId) {
                                $itemData[$key][] = $item;
                            }
                        });
                    }
                    break;
            }

        });
    }

    //关联预载入多行数据处理
    public function withListFilter(array &$data)
    {
        if (!empty(self::$methodGroups['belongsTo'])) {
            $this->withListWalk($data, 'belongsTo');
        }
        if (!empty(self::$methodGroups['hasMany'])) {
            $this->withListWalk($data, 'hasMany');
        }
    }

    //关联预载入多行数据处理-连接载入数据
    protected function withListWalk(array &$listData, string $modelType)
    {
        array_walk(self::$methodGroups[$modelType], function ($model, $key) use (&$listData, $modelType) {

            $ownerKey   = $model->owner_key;
            $foreignKey = $model->foreign_key;
            $ownerIds   = array_column($listData, $ownerKey);

            switch ($modelType) {
                case 'belongsTo':
                    //预载模型数据-取列id
                    $foreignResult = $model->whereIn($foreignKey, $ownerIds)->select()->toArray(true);
                    if (!empty($foreignResult['data'])) {
                        $foreignData = $foreignResult['data'];
                        $foreignIds  = array_column($foreignData, $foreignKey);
                        //处理主表
                        array_walk($listData, function (&$item, $index) use ($modelType, $foreignData, $ownerKey, $foreignIds, $key) {
                            //查找截取位置
                            $foreignIndex = array_search($item[$ownerKey] ?? 0, $foreignIds);
                            //插入主表
                            if ($foreignIndex !== false) {
                                $item[$key] = $foreignData[$foreignIndex];
                            } else {
                                //填充null,代表执行过.
                                $item[$key] = null;
                            }
                        });
                    }
                    break;
                case 'hasMany':
                    //预载模型数据-取列id
                    $foreignResult = $model->whereIn($foreignKey, $ownerIds)->select()->toArray(true);
                    if (!empty($foreignResult['data'])) {
                        $foreignData = $foreignResult['data'];
                        $foreignIds  = array_column($foreignData, $foreignKey);
                        //处理主表
                        array_walk($listData, function (&$item, $index) use ($modelType, $foreignData, $ownerKey, $foreignIds, $key) {
                            $itemValue = $item[$ownerKey] ?? 0;
                            //处理载入表 - //截取所有值相等的位置
                            array_walk($foreignIds, function ($value, $foreignIndex) use (&$item, $itemValue, $foreignData, $key) {
                                if ($value == $itemValue) {
                                    $item[$key][] = $foreignData[$foreignIndex];
                                }
                            });
                            //填充null,代表执行过.
                            if (!isset($item[$key])) {
                                $item[$key] = null;
                            }
                        });
                    }
                    break;
            }

        });

    }

    //单行数据处理
    public function dataItemFilter(string $action, &$item)
    {
        if (!empty($item) && !empty($this->casts)) {
            //模型字段过滤
            $casts     = $this->casts;
            $diffCasts = array_intersect_key($casts, $item);
            //当前时间
            $dateTime = date("Y-m-d H:i:s", time());

            //自动插入操作时间
            if ($action == 'create' && $this->timestamps &&
                !empty(static::CREATED_AT) && !empty(static::UPDATED_AT)) {
                $item[static::CREATED_AT] = $dateTime;
                $item[static::UPDATED_AT] = $dateTime;
            }
            if ($action == 'update' && $this->timestamps && !empty(static::UPDATED_AT)) {
                $item[static::UPDATED_AT] = $dateTime;
            }

            //模型字段过滤
            foreach ($item as $key => &$value) {

                //清除NULL值
                if ($value === null) {
                    unset($item[$key]);
                }

                //空值过滤
                if (isset($diffCasts[$key])) {
                    $type = $diffCasts[$key];
                    if (empty($value)) {
                        $this->emptyValueFilter($value, $type);
                    } else {
                        //处理文本浮点数0
                        preg_match('/^0\.\d+$/', $value, $m);
                        if (isset($m[0])) {
                            $this->emptyValueFilter($value, $type);
                        }
                        //处理文本日期 0000-00-00 00:00:00
                        preg_match('/^(\d{4}-\d{2}-\d{2}).*$/', $value, $m);
                        if (isset($m[0])) {
                            $value = date('Y-m-d H:i:s', strtotime($value));
                        }
                    }
                }

            }
        }
    }

    //多行数据处理
    public function dataListFilter(string $action, &$data)
    {
        if (!empty($data) && !empty($this->casts)) {
            //模型字段过滤
            $casts     = $this->casts;
            $diffCasts = array_intersect_key($casts, $data[0]);
            //当前时间
            $dateTime = date("Y-m-d H:i:s", time());
            //
            array_walk($data, function (&$item) use ($action, $diffCasts, $dateTime) {

                //自动插入操作时间
                if ($action == 'create' && $this->timestamps &&
                    !empty(static::CREATED_AT) && !empty(static::UPDATED_AT)) {
                    $item[static::CREATED_AT] = $dateTime;
                    $item[static::UPDATED_AT] = $dateTime;
                }
                if ($action == 'update' && $this->timestamps && !empty(static::UPDATED_AT)) {
                    $item[static::UPDATED_AT] = $dateTime;
                }

                //模型字段过滤
                foreach ($item as $key => &$value) {

                    //清除NULL值
                    if ($value === null) {
                        unset($item[$key]);
                    }

                    //空值过滤
                    if (isset($diffCasts[$key])) {
                        $type = $diffCasts[$key];
                        if (empty($value)) {
                            $this->emptyValueFilter($value, $type);
                        } else {
                            //处理文本浮点数0
                            preg_match('/^0\.\d+$/', $value, $m);
                            if (isset($m[0])) {
                                $this->emptyValueFilter($value, $type);
                            }
                            //处理文本日期 0000-00-00 00:00:00
                            preg_match('/^(\d{4}-\d{2}-\d{2}).*$/', $value, $m);
                            if (isset($m[0])) {
                                $value = date('Y-m-d H:i:s', strtotime($value));
                            }
                        }
                    }

                }

            });
        }
    }

    //多行数据查询
    public function getDataFilter(string $action, &$data)
    {
        if (!empty($data) && !empty($this->casts)) {
            //模型字段过滤
            $casts     = $this->casts;
            $diffCasts = array_intersect_key($casts, $data[0]);
            //
            array_walk($data, function (&$item) use ($action, $diffCasts) {

                //模型字段过滤
                foreach ($item as $key => &$value) {

                    //空值过滤
                    if (isset($diffCasts[$key])) {
                        $type = $diffCasts[$key];
                        if (empty($value)) {
                            $this->emptyValueFilter($value, $type);
                        } else {
                            //处理文本浮点数0
                            preg_match('/^0\.\d+$/', $value, $m);
                            if (isset($m[0])) {
                                $this->emptyValueFilter($value, $type);
                            }
                            //处理文本日期 0000-00-00 00:00:00
                            preg_match('/^(\d{4}-\d{2}-\d{2}).*$/', $value, $m);
                            if (isset($m[0])) {
                                $value = date('Y-m-d H:i:s', strtotime($value));
                            }
                        }
                    }

                }

            });
        }
    }

    //空值过滤
    protected function emptyValueFilter(&$value, $type)
    {
        switch ($type) {
            case 'date' :
                $value = NULL;
                break;
            case 'text' :
                $value = (string)$value;
                break;
            case 'integer' :
                $value = (int)$value;
                break;
            case 'float' :
                $value = (float)$value;
                break;
        }
    }

}
