<?php

namespace domain\base\repository;

use extend\utils\QueryMatch;
use think\Db;

class BaseRepository
{
    //继承类覆盖的模型class
    protected $model = null;
    //当前模型引用
    protected static $query = null;
    //当前单例
    protected static $instance = null;
    //当前筛选字段集合
    protected static $fields = null;
    //当前单例类型
    protected static $instanceType; //0-纯动态,1-静态注入动态,2-纯静态

    public function __construct(array $input = null, $callStatic = 0)
    {
        //重新设置
        self::$fields       = null;
        self::$instanceType = $callStatic;
        //
        switch ($callStatic) {
            default: //引用 动态实例 - 包含继承函数
                if (!empty($input)) {
                    self::$query = (new $this->model($input)); // new modelClass($input)
                } else {
                    self::$query = (new $this->model()); // new modelClass()
                }
                break;
            case 1: //引用 源模型 动态实例 - 不包含继承函数
                self::$query = $this->model::useGlobalScope(true);
                if (!empty($input)) {
                    $fields = $input;
                    self::$query->field($fields); // modelClass::select(['*'])
                }
                break;
            case 2: //引用 源模型 静态类
                self::$query = $this->model; // modelClass::class
                break;
        }

    }

    //仓储单例 - 仓储模型 和 源模型 实例化, 不能调用间接实例化的函数.
    public static function newInstance(array $fields = null)
    {
        self::$instance = new static($fields, 0);
        return self::$instance;
    }

    //融合单例 - 仓储模型 和 源模型 融合, 共享函数(实例,静态,间接实例).
    public static function searchInstance(array $fields = null)
    {
        self::$instance = new static($fields, 1);
        return self::$instance;
    }

    //源模型静态引用 - 仓储模型 和 源模型, 只能调用静态函数.
    public static function sourceInstance(array $fields = null)
    {
        self::$instance = new static($fields, 2);
        return self::$instance;
    }


    //转接不存在的动态函数 到 源模型上
    public function __call($name, $arguments)
    {
        if (!empty($arguments[0]) && !empty($arguments[1]) && !empty($arguments[2])) {
            return self::$query->{$name}($arguments[0], $arguments[1], $arguments[2]);

        } else if (!empty($arguments[0]) && !empty($arguments[1])) {
            return self::$query->{$name}($arguments[0], $arguments[1]);

        } else if (!empty($arguments[0])) {
            return self::$query->{$name}($arguments[0]);

        } else {
            return self::$query->{$name}();

        }
    }

    //转接不存在的静态函数 到 源模型上
    public static function __callStatic($name, $arguments)
    {
        //源模型静态引用
        self::sourceInstance();
        if (!empty($arguments[0]) && !empty($arguments[1])) {
            return self::$query::{$name}($arguments[0], $arguments[1]);
        } else if (!empty($arguments[0])) {
            return self::$query::{$name}($arguments[0]);
        } else {
            return self::$query::{$name}();
        }
    }

    //控制全局查询范围
    public function useGlobalScope($scope = true)
    {
        self::$query = $this->model::useGlobalScope($scope);
        if (self::$instanceType == 1) {
            self::$query->field(self::$fields);
        }
        return self::$instance;
    }

    /**
     * 分页获取
     * @deprecated - 特殊查询参数会导致异常,逐渐废弃.
     * @param QueryMatch $QM
     * @return array
     */
    public function pageGet(QueryMatch $QM)
    {
        $query      = self::$query;
        $cloneQuery = clone self::$query;
        $collect    = [];

        $QM->pagination($per_page, $page, $pagination, $row);
        //dd($page, $per_page, $pagination, $row);//

        //原始 query
        $collectArr      = $query->limit($row, $per_page)->select();
        $collect['data'] = $collectArr;
        //克隆 query
        //$cloneWhere 必须在$cloneQuery->select()之前,才能拿where条件.
        $cloneWhere = $cloneQuery->getOptions('where');
        //$selectSql 中的 where条件只有占位符,需要替换.
        $selectSql = $cloneQuery->limit($row, $per_page)->fetchSql()->select();
        $totalSql  = self::subTotalQuery($selectSql, $cloneWhere);
        //dd($collectArr, $cloneWhere, $selectSql, $totalSql);//

        //打开翻页时,才有meta数据 且 计算总行数
        if ($pagination != 'false') {
            $meta['pagination']   = true;
            $meta['per_page']     = $per_page;
            $meta['current_page'] = $page;

            //最小化查表总计
            $tableCount = Db::query($totalSql);
            $tableCount = $tableCount[0]['total'] ?? 0;
            //dd($collectArr[0],$tableCount);//

            $meta['last_page'] = (int)ceil($tableCount / $per_page);
            $meta['total']     = $tableCount;

            $collect['meta'] = $meta;
        }

        //附加补充数据
        if (!empty($query->addMeta)) {
            if (!empty($collect['meta'])) {
                $collect['meta'] = array_merge($collect['meta'], $query->addMeta);
            } else {
                $collect['meta'] = $query->addMeta;
            }
        }

        return $collect;
    }

    /**
     * 分页获取 - 继承框架翻页的稳定版本.
     * @param QueryMatch $QM
     * @return array
     * @throws \ReflectionException
     */
    public function pageData(QueryMatch $QM)
    {
        $query   = self::$query;
        $collect = [];

        //因为框架 \think\db\Query::options 是保护方法,所以初始化时,设置成公共方法,好处理翻页 total_count.
        $reflection    = new \ReflectionClass(self::$query); //获取反射类
        $optionsMethod = $reflection->getMethod("options"); //获取私有方法
        $optionsMethod->setAccessible(true); //修改访问级别为可访问

        //缓存查询条件 - 避免被 select方法 清空
        $options = $query->getOptions();

        /**
         * 调用 框架私有方法 @see \think\db\Query::options 时,
         * 参考 框架翻页方法 @see \think\db\Query::paginate
         */
        //调用私有方法 - 置入"缓存查询条件" - 获取列表数据
        $QM->pagination($per_page, $page, $pagination, $row);
        $collectArr = $optionsMethod->invoke($query, $options)->limit($row, $per_page)->select();
        //调用私有方法 - 置入"缓存查询条件" - 合成 select sql
        $selectSql = $optionsMethod->invoke($query, $options)->buildSql();
        //dd(collection($collectArr)->toArray(), $selectSql);//

        //翻页列表数据结构
        $collect['data'] = $collectArr;
        //打开翻页时,才有meta数据 且 计算总行数
        if ($pagination != 'false') {
            $meta['pagination']   = true;
            $meta['per_page']     = $per_page;
            $meta['current_page'] = $page;
            //拼接总计sql
            $database   = self::$query->getConfig()['database'];
            $totalQuery = preg_replace("/(^.*)(\s+FROM\s+){1}(.*)(\s+LIMIT\s+\d+,\d+)(.*$)/is", '$1$2`' . $database . '`.$3 $5', $selectSql);
            $totalSql   = "SELECT count(1) AS total FROM " . $totalQuery . " AS SUB";
            //最小化查表总计
            $tableCount = Db::query($totalSql);
            $tableCount = $tableCount[0]['total'] ?? 0;
            //dd($collectArr[0],$tableCount);//
            $meta['last_page'] = (int)ceil($tableCount / $per_page);
            $meta['total']     = $tableCount;
            $collect['meta']   = $meta;
        }
        //附加补充数据
        if (!empty($query->addMeta)) {
            if (!empty($collect['meta'])) {
                $collect['meta'] = array_merge($collect['meta'], $query->addMeta);
            } else {
                $collect['meta'] = $query->addMeta;
            }
        }
        return $collect;
    }

    /**
     * 分页获取 - 不合计总数的稳定版本.
     * @param QueryMatch $QM
     * @return array
     */
    public function pageGetNoTotal(QueryMatch $QM)
    {
        $query   = self::$query;
        $collect = [];
        $QM->pagination($per_page, $page, $pagination, $row);
        //dd($page, $per_page, $pagination, $row);//
        //原始 query
        $collectArr      = $query->limit($row, $per_page)->select();
        $collect['data'] = $collectArr;
        //打开翻页时,才有meta数据 且 计算总行数
        if ($pagination != 'false') {
            $meta['pagination']   = true;
            $meta['per_page']     = $per_page;
            $meta['current_page'] = $page;
            $collect['meta']      = $meta;
        }
        //附加补充数据
        if (!empty($query->addMeta)) {
            if (!empty($collect['meta'])) {
                $collect['meta'] = array_merge($collect['meta'], $query->addMeta);
            } else {
                $collect['meta'] = $query->addMeta;
            }
        }
        return $collect;
    }


    //目标数据不为空
    public static function NoEmpty($data, $key)
    {
        if (isset($data[$key]) && $data[$key] != '') {
            return true;
        }
        return false;
    }

    /**
     * 查询Sql改成合计sql
     * @param $selectSql - 克隆query, where条件只有占位符,需要替换.
     * @param null $cloneWhere - 用来替换的 where条件, 是 $selectSql->getOptions('where') 的内容.
     * @return null|string|string[]
     */
    public static function subTotalQuery($selectSql, $cloneWhere = null)
    {
        //dd($cloneWhere, $selectSql);//
        //$selectSql 中的 where条件只有占位符,需要替换.
        $selectSql = self::parseWhereOption($cloneWhere, $selectSql);
        //拼接总计sql
        $database   = self::$query->getConfig()['database'];
        $totalQuery = preg_replace("/(^.*)(\s+FROM\s+){1}(.*)(\s+LIMIT\s+\d+,\d+)(.*$)/is", '$1$2`' . $database . '`.$3 $5', $selectSql);
        $totalQuery = "SELECT count(1) AS total FROM (" . $totalQuery . ") AS SUB";
        return $totalQuery;
    }

    /**
     * 分析where条件,替换占位符
     * @param $cloneWhere
     * @param $selectSql
     * @return mixed
     */
    protected static function parseWhereOption($cloneWhere, &$selectSql)
    {
        if (!empty($cloneWhere['AND'])) {
            //[Key:多层Array]结构
            array_walk($cloneWhere['AND'], function ($item, $fieldKey) use (&$selectSql) {
                if (isset($item[0]) && is_string($item[0])) {
                    //多where情况下 - 值是 object 对象, 不用处理.
                    if (in_array($item[0], ['in', 'not in', 'IN', 'NOT IN']) && gettype($item[1]) != "object") {
                        $itemValueArr = explode(",", $item[1]);
                        $pattern      = ":where_AND_" . $fieldKey;
                        $fullPattern  = "";
                        $fullValue    = "";
                        array_walk($itemValueArr,
                            function ($value, $secondIndex) use ($pattern, &$fullPattern, &$fullValue, &$selectSql) {
                                $fullPattern .= $pattern . "_in_" . ($secondIndex + 1) . ",";
                                $value       = "'" . $value . "'";
                                $fullValue   .= $value . ",";
                            });
                        $fullPattern = trim($fullPattern, ",");
                        $fullValue   = trim($fullValue, ",");
                        $selectSql   = str_replace($fullPattern, $fullValue, $selectSql);
                        //dd($fullPattern, $fullValue, $selectSql);//
                    } else if (in_array($item[0], ['between']) && gettype($item[1]) != "object") {
                        if (gettype($item[1]) == "array") {
                            $fullPattern = "";
                            $fullValue   = "";
                            array_walk($item[1], function ($value, $index) use ($fieldKey, &$fullPattern, &$fullValue) {
                                $currPattern = ":where_AND_" . $fieldKey . "_between_" . ($index + 1);
                                $currValue   = "'" . $value . "'";
                                $fullPattern .= $currPattern . " AND ";
                                $fullValue   .= $currValue . " AND ";
                            });
                            $fullPattern = trim($fullPattern, "AND ");
                            $fullValue   = trim($fullValue, "AND ");
                            //dd($fullPattern, $fullValue);//
                            $selectSql = str_replace($fullPattern, $fullValue, $selectSql);
                            //dd($fullPattern, $fullValue, $selectSql);//
                        }
                    } else if (gettype($item[1]) == "object") {
                        //值是 object 对象, 不用处理.
                    } else {
                        $fullPattern = ":where_AND_" . $fieldKey . " "; //加空格,避免替换到相似占位符
                        $value       = $item[1];
                        $value       = "'" . $value . "'";
                        $fullValue   = $value;
                        $selectSql   = str_replace($fullPattern, $fullValue, $selectSql);
                        //dd($fullPattern, $fullValue, $selectSql);//
                    }

                } else if (isset($item[0][0]) && is_string($item[0][0])) {
                    //多子where情况下 - 值是 object 对象, 不用处理.
                    array_walk($item, function ($whereArr, $firstIndex) use ($fieldKey, &$selectSql) {
                        if (isset($whereArr[0]) && in_array($whereArr[0], ["in", "not in", 'IN', 'NOT IN']) && gettype($whereArr[1]) != "object") {
                            $itemValueArr = explode(",", $whereArr[1]);
                            $pattern      = ":where_" . $fieldKey;
                            $fullPattern  = "";
                            $fullValue    = "";
                            array_walk($itemValueArr,
                                function ($value, $secondIndex) use ($pattern, $firstIndex, &$fullPattern, &$fullValue, &$selectSql) {
                                    $fullPattern .= $pattern . "_" . $firstIndex . "_in_" . ($secondIndex + 1) . ",";
                                    $value       = "'" . $value . "'";
                                    $fullValue   .= $value . ",";
                                });
                            $fullPattern = trim($fullPattern, ",");
                            $fullValue   = trim($fullValue, ",");
                            $selectSql   = str_replace($fullPattern, $fullValue, $selectSql);
                            //dd($fullPattern, $fullValue, $selectSql);//
                        } else if (gettype($whereArr[1]) == "object") {
                            //值是 object 对象, 不用处理.
                        } else {
                            $pattern     = ":where_" . $fieldKey;
                            $fullPattern = $pattern . "_" . $firstIndex;
                            $value       = $whereArr[1];
                            $value       = "'" . $value . "'";
                            $fullValue   = $value;
                            $selectSql   = str_replace($fullPattern, $fullValue, $selectSql);
                            //dd($fullPattern, $fullValue, $selectSql);//
                        }
                    });

                } else if ($item === null) {
                    //pass
                } else {
                    //[key:value]结构
                    $fullPattern = ":where_AND_" . $fieldKey;
                    $value       = $item;
                    $value       = "'" . $value . "'";
                    $fullValue   = $value;
                    $selectSql   = str_replace($fullPattern, $fullValue, $selectSql);
                    //dd($fullPattern, $fullValue, $selectSql);//
                }

            });
        }
        return $selectSql;
    }

}