<?php

namespace extend\elastic;

use Elasticsearch\ClientBuilder;
use think\Config;

/*
 * https://www.cnblogs.com/jiqing9006/p/9274289.html
 */

class EsOrm
{
    private static $instance; //单例
    private static $esClient; //es单例

    private $indexDbName; //当前库名
    private $indexTableName; //当前表名

    private $params; //待拼接参数集合

    private $query; //筛选参数集合
    private $bools; //查询条件总览
    private $boolChildStart = 0; //闭包流程-层级id递增值,0-为无闭包流程
    private $boolParents = null; //子查询父条件集合
    private $boolChilds = null; //子查询条件集合

    private $sort; //排序参数集合

    private $fields = []; //索引结构key - 原始结构
    private $fieldTemple = []; //索引结构数据模板 - 原始结构
    private $source = []; //筛选索引字段 - 不带as关键字
    private $sourceAlias = []; //筛选索引字段 - 将as关键字,转成map键值关系

    private $aggsRecursion = false;  //递归聚合开关 - 打开时 toArray()转换聚合逻辑将改变
    private $aggs;                   //平行聚合参数集合
    private $aggsKeyArr = [];        //平行聚合 - 相关key记录,用于toArray()转换数据
    private $aggsPack;               //递归聚合参数集合
    private $aggsPackKeyArr = [];    //递归聚合 - 相关key记录,用于toArray()转换数据

    private $find = false; //获取单行数据 - 供toArray()函数 做相应转换
    private $first = false; //提取列表中的单行数据 - 供toArray()函数 做相应转换
    private $select = false; //提取列表数据 - 供toArray()函数 做相应转换
    private $selectStats = false; //度量计算列表数据 - 供toArray()函数 做相应转换
    private $statsType = ''; //度量计算类型名 - 供toArray()函数 做相应转换

    private $from = 0; //翻页起始点
    private $size = 100; //翻页结束点

    private $result = null; //查询结果

    public function __construct()
    {
        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();
        //数据表常量 - 重置
        $this->indexDbName    = null;
        $this->indexTableName = null;
    }

    //检查返回错误信息
    protected function checkError($result)
    {
        if (isset($result['errors']) && $result['errors'] == true) {
            $msg = $this->errorMsgFilter($result['items']);
            //异常日志转换
            throw new \Exception($msg, 400);
        }
    }

    //异常日志转换
    protected function errorMsgFilter($items)
    {
        $msg = ['type' => '', 'reason' => '', 'caused_by' => []];
        //异常日志太大,只拿第一条错误.
        $errorItem = [];
        foreach ($items as $index => $item) {
            if (isset($item['index']['error'])) {
                $errorItem = $item['index']['error'];
                break;
            }
            continue;
        }
        $msg = array_merge($msg, $errorItem);
        $msg = json_encode($msg);
        return $msg;
    }

    //重置DSL内部常量
    protected function resetDslConstant()
    {
        unset($this->params['body']);

        $this->query = null;
        $this->bools = null;

        $this->source      = [];
        $this->sourceAlias = [];

        $this->sort = null;

        $this->aggsRecursion  = false;
        $this->aggs           = null;
        $this->aggsKeyArr     = [];
        $this->aggsPack       = null;
        $this->aggsPackKeyArr = [];

        $this->find        = false;
        $this->first       = false;
        $this->select      = false;
        $this->selectStats = false;
        $this->statsType   = '';

        $this->from = 0;
        $this->size = 100;
    }

    /**
     * notes: 重复初始化
     * @return EsOrm
     * @author 陈鸿扬 | @date 2022/6/8 12:37
     */
    public static function init()
    {
        self::$instance = new static;
        return self::$instance;
    }

    /**
     * notes: 不重复初始化 - 单例
     * @return EsOrm
     * @author 陈鸿扬 | @date 2021/12/30 14:42
     */
    public static function instance()
    {
        if (!self::$instance instanceof static) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    //刷新返回缓存
    public function fresh()
    {
        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();
        //待拼接参数集合 - 重置
        $this->params = null;
        //返回缓存 - 重置
        $this->result = null;
    }

    /**
     * notes: es单例初始化
     * @return \Elasticsearch\Client
     * @author 陈鸿扬 | @date 2021/12/30 14:42
     */
    public static function esClient()
    {
        if (!self::$esClient instanceof static) {
            $hosts          = [Config::get('ELASTICE_SEARCH.HOST')];
            $client         = ClientBuilder::create()->setHosts($hosts)->build();
            self::$esClient = $client;
        }
        return self::$esClient;
    }

    /**
     * notes: 设置表
     * @param $indexDbName //索引名（相当于mysql的数据库）
     * @param $indexTableName //类型名（相当于mysql的表）
     * @return $this
     * @author 陈鸿扬 | @date 2021/3/29 11:54
     */
    public function table($indexDbName, $indexTableName = null)
    {
        $this->params['index'] = $this->indexDbName = $indexDbName;
        if (!empty($indexTableName)) {
            $this->params['type']    = $this->indexTableName = $indexTableName;
            $this->params['routing'] = $this->indexTableName = "/" . $indexDbName . "/" . $indexTableName;
        } else {
            $this->params['routing'] = $this->indexTableName = "/" . $indexDbName;
        }
        return $this;
    }

    /**
     * notes: 获取索引结构
     * @param array $fieldTemple - 返回 带默认值结构
     * @return array - 返回 key结构
     * @author 陈鸿扬 | @date 2022/12/6 14:54
     */
    public function attributes(&$fieldTemple = [])
    {
        $indexDbName     = $this->indexDbName;
        $params['index'] = $indexDbName;
        $client          = $this->esClient();
        $result          = $client->indices()->getMapping($params);

        $fields = [];
        if (!empty($result)) {
            //筛选字段集合
            $sourceFields = $this->source ?? null;
            //字段结构
            $properties = $result["$indexDbName"]["mappings"]["$indexDbName"]["properties"];
            array_walk($properties, function (&$item, $key) use (&$fieldTemple, $sourceFields) {
                $value = '';

                //不处理 子文档字段
                if (!isset($item["properties"])) {

                    switch ($item["type"]) {
                        case "long":
                        case "integer":
                            $value = 0;
                            break;
                        case "text":
                            $value = "";
                            break;
                        case "date":
                            $value = null;
                            break;
                    }

                    //只筛选字段-不佚名处理
                    if (!empty($sourceFields)) {
                        if (in_array($key, $sourceFields)) {
                            $fieldTemple[$key] = $value;
                        }
                    } else {
                        //默认情况
                        $fieldTemple[$key] = $value;
                    }
                }

            });
            $fields = array_keys($fieldTemple ?? []);
        }
        $this->fieldTemple = $fieldTemple ?? [];
        $this->fields      = $fields;

        return $fields;
    }

    /**
     * notes: 单行新增 By Id
     * @param $id
     * @param $data
     * @return array
     * @author 陈鸿扬 | @date 2021/12/30 14:42
     */
    public function saveById($id, $data)
    {
        $this->params['id']   = $id;
        $this->params['body'] = $data;
        $client               = $this->esClient();
        $result               = $client->index($this->params);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /*
     * notes: 单行新增
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     * @param $data
     * @param string $idField
     * @return array
     */
    public function save($data, $idField = 'id')
    {
        //重新设置参数
        $currParam = [
            "index"   => $this->params['index'],
            "type"    => $this->params['type'],
            "routing" => $this->params['routing'],
            'body'    => []
        ];
        //设置数据
        $id                  = $data["$idField"];
        $currParam['body'][] = ['create' => ['_id' => $id]];
        $newData             = [];
        foreach ($data as $key => $value) {
            if (gettype($value) == 'integer') {
                $value += $id;
            }
            if (gettype($value) == 'string') {
                $value .= $id;
            }
            $newData[$key] = $value;
        }
        $currParam['body'][] = $newData;
        $client              = $this->esClient();
        $result              = $client->bulk($currParam);

        //检查返回错误信息
        $this->checkError($result);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /*
     * notes: 批量数据更新or新增
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     * @param $listData
     * @param string $idField
     * @param string $upIdField
     * @return array
     */
    public function saveAll($listData, $idField = 'id', $upIdField = null, \Closure $closure = null)
    {
        //通过文档_id获取旧数据
        if ($upIdField) {
            if ($upIdField == 'id') {
                $upIdField = '_id';
            };
            $docIds    = array_column($listData, $upIdField);
            $currQuery = $this->order($upIdField, 'asc')->whereIn($upIdField, $docIds)->page(1, count($listData));
            if ($closure) {
                $closure($currQuery);
            }
            $oldData  = $currQuery->select()->toArray()['data'];
            $listData = $this->ArrayMergeLv2($oldData, $listData, $upIdField);
        }

        //重新设置参数
        $currParam = [
            "index"   => $this->params['index'],
            "type"    => $this->params['type'] ?? '_doc',
            "routing" => $this->params['routing'],
            'body'    => []
        ];
        //设置数据
        foreach ($listData as $ind => $data) {
            $id                  = $data["$idField"];
            $currParam['body'][] = ['index' => ['_id' => $id]];
            $newData             = [];
            foreach ($data as $key => $value) {
                $newData[$key] = $value;
            }
            $currParam['body'][] = $newData;
        }

        $client = $this->esClient();
        $result = $client->bulk($currParam);

        //dd($result);//

        //检查返回错误信息
        $this->checkError($result);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /*
     * notes: 批量数据新增
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     * @param $listData
     * @param string $idField
     * @return array
     */
    public function addAll($listData, $idField = 'id')
    {
        //重新设置参数
        $currParam = [
            "index"   => $this->params['index'],
            "type"    => $this->params['type'],
            "routing" => $this->params['routing'],
            'body'    => []
        ];
        //设置数据
        foreach ($listData as $ind => $data) {
            $id                  = $data["$idField"];
            $currParam['body'][] = ['index' => ['_id' => $id]];
            $newData             = [];
            foreach ($data as $key => $value) {
                $newData[$key] = $value;
            }
            $currParam['body'][] = $newData;
        }

        $client = $this->esClient();
        $result = $client->bulk($currParam);

        //dd($result);//

        //检查返回错误信息
        $this->checkError($result);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /*
     * notes: 单行更新
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     * @param $id
     * @param $data
     * @return array
     */
    public function update($id, $data)
    {
        $this->params['id']          = $id;
        $this->params['body']['doc'] = $data;
        $client                      = $this->esClient();
        $result                      = $client->update($this->params);

        //检查返回错误信息
        $this->checkError($result);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /*
     * notes: 批量更新
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     * @param $listData
     * @param string $idField
     * @return array
     */
    public function updateAll($listData, $idField = 'id')
    {
        foreach ($listData as $ind => $data) {
            $id                     = $data["$idField"];
            $this->params['body'][] = ['update' => ['_id' => $id]];
            //unset($data["$idField"]);
            $this->params['body'][] = ['doc' => $data];
        }

        $client = $this->esClient();
        $result = $client->bulk($this->params);

        //检查返回错误信息
        $this->checkError($result);

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    //删除 - 继承bools筛选条件
    public function delete($idField = 'id', \Closure $closure = null)
    {
        //如果之前有 find id 的记录 - 改成bools筛选格式
        if (isset($this->params["$idField"]) && empty($this->bools)) {
            $id = $this->params["$idField"];
            unset($this->params["$idField"]);
            $this->match($idField, $id, 'must');
        } elseif (isset($this->params["$idField"]) && !empty($this->bools)) {
            //清除 find id 的记录 - 保留bools筛选条件
            unset($this->params["$idField"]);
        }
        $this->toDSL($closure);
        //dd($this->params);//

        $client = $this->esClient();
        $result = $client->deleteByQuery($this->params);

        //检查返回错误信息
        $this->checkError($result);

        if (isset($result['deleted']) && $result['deleted'] != 0) {
            $this->result = $result;
        }

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    //根据指定字段删除 - 不继承bools筛选条件
    public function deleteById($id, $idField = 'id', \Closure $closure = null)
    {
        //不继承bools筛选条件
        $this->bools = null;

        //如果之前有 find id 的记录 - 改成bools筛选格式
        if (isset($this->params["$idField"])) {
            $id = $this->params["$idField"];
            unset($this->params["$idField"]);
            $this->match($idField, $id, 'must');
        } else {
            $this->match($idField, $id, 'must');
        }
        $this->toDSL($closure);
        //dd($this->params);//

        $client = $this->esClient();
        $result = $client->deleteByQuery($this->params);

        //检查返回错误信息
        $this->checkError($result);

        if (isset($result['deleted']) && $result['deleted'] != 0) {
            $this->result = $result;
        }

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /**
     * notes: 筛选索引字段
     * @param $params
     * @return $this
     * @author 陈鸿扬 | @date 2022/6/15 12:14
     */
    public function fields($params)
    {
        $fieldArr = [];
        $type     = gettype($params);
        if ($type == "string" && $params != "*") {
            $fieldArr = explode(",", $params);
        }
        if ($type == "array" && !empty($params) && !in_array("*", $params)) {
            $fieldArr = $params;
        }

        //字段佚名处理
        $this->setFieldAlias($fieldArr);

        $this->source = array_merge($this->source, $fieldArr);

        return $this;
    }

    /**
     * notes: 字段佚名处理
     * @param array $fieldArr
     * @return array
     * @author 陈鸿扬 | @date 2022/8/2 18:57
     */
    protected function setFieldAlias(array &$fieldArr)
    {
        $newFieldArr = [];
        //识别字段中的 AS 关键字, 并提取关系数组
        array_walk($fieldArr, function ($keyStr, $index) use (&$newFieldArr) {
            preg_match("/(^\w+|\w+\d+)\s+(as|AS)\s+(.*$)/", $keyStr, $m);
            if (isset($m[1]) && isset($m[3])) {
                $trueKey = $m[1];
                $newKey  = $m[3];
                //将as关键字,转成map键值关系, 保留给 ->toArray() 处理.
                $this->sourceAlias[$trueKey] = $newKey;
                //原始字段集合
                $newFieldArr[$index] = $trueKey;
            } else {
                //没有as关键字,则正常赋值.
                $newFieldArr[$index] = $keyStr;
            }
        });
        //dd($newFieldArr,$this->sourceAlias);//
        $fieldArr = $newFieldArr;
        return $newFieldArr;
    }

    /**
     * notes: 筛选总览
     * @param $key
     * @param null $valueA
     * @param null $valueB
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:41
     */
    public function where($key, $valueA = null, $valueB = null)
    {
        $keyType = gettype($key);
        switch ($keyType) {
            case 'string':
                if (!empty($valueA) && $valueB === null) {
                    //预防$valueB传值为null, $valueA的符号变成新值.
                    $operator = ['=', '<', ">", '<=', ">=", "<>", "%", "!", 'like', 'in', 'not in'];
                    if (!in_array($valueA, $operator)) {
                        $this->whereKv($key, $valueA, 'must');
                    }
                }
                if (!empty($valueA) && $valueB !== null) {
                    $this->whereKcV($key, $valueA, $valueB, 'must');
                }
                break;
            case 'object':
                $this->whereClosure($key, 'must');
                break;
        }

        return $this;
    }

    /**
     * notes: 或筛选总览
     * @param $key
     * @param null $valueA
     * @param null $valueB
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:40
     */
    public function orWhere($key, $valueA = null, $valueB = null)
    {
        if (gettype($key) !== 'array') {
            if (!empty($valueA) && $valueB === null) {
                $this->whereKv($key, $valueA, 'should');
            }
            if (!empty($valueA) && $valueB !== null) {
                $this->whereKcV($key, $valueA, $valueB, 'should');
            }
        }
        return $this;
    }

    /**
     * notes: key value 筛选
     * @param $key
     * @param $value
     * @param string $bool - 联合条件: must,must_not,should,term
     * @author 陈鸿扬 | @date 2021/12/30 12:04
     */
    protected function whereKv($key, $value, $bool = 'must')
    {
        //精确匹配
        $this->match($key, $value, $bool);
        //精确索引id
        //$this->term($key,$value);
    }

    /**
     * notes: key compare value筛选
     * @param $key
     * @param $compare
     * @param $value
     * @param string $bool - 联合条件: must,must_not,should,term
     * @author 陈鸿扬 | @date 2021/12/30 12:55
     */
    protected function whereKcV($key, $compare, $value, $bool = 'must')
    {
        switch ($compare) {
            case '=':
                $this->match($key, $value, $bool);
                break;

            case '>=':
                $this->range($key, 'gte', $value, $bool);
                break;
            case '<=':
                $this->range($key, 'lte', $value, $bool);
                break;
            case '>':
                $this->range($key, 'gt', $value, $bool);
                break;
            case '<':
                $this->range($key, 'lt', $value, $bool);
                break;

            case 'like':
                $value = str_replace('%%', '*', $value);
                $this->wildcard($key, $value, $bool);
                break;
            case 'in':
                $this->whereIn($key, $value);
                break;
            case 'not in':
                $this->whereNotIn($key, $value);
                break;
        }
    }

    /**
     * notes: 闭包where - 异步引用子级
     * @param \Closure $closure
     * @param string $bool
     * @return $this
     * @author 陈鸿扬 | @date 2022/7/8 19:58
     */
    protected function whereClosure(\Closure $closure, $bool = 'must')
    {
        //开始当前闭包流程
        $this->boolChildSwitch($bool, true);

        if ($this->boolChilds === null) {
            if ($this->bools === null) {
                //初始化父级bool
                $this->bools = [['bool' => [$bool => []]]];
            }
            if ($this->boolChilds === null) {
                //引用上级,用于闭包结束,回退上级.
                $this->boolParents = &$this->bools;
            }
            //引用下级
            $this->boolChilds = &$this->bools[0]['bool'][$bool];
            //dd($this->boolParents,$this->boolChilds,$this->bools);//
        } else {
            //引用上级,用于闭包结束,回退上级.
            $this->boolParents = &$this->boolChilds;
            //引用下级
            $boolIndexC                    = $this->currBoolIndex($this->boolChilds);
            $this->boolChilds[$boolIndexC] = ['bool' => [$bool => []]];
            $this->boolChilds              = &$this->boolChilds[$boolIndexC]['bool'][$bool];
            //dd($this->boolParents,$this->boolChilds,$this->bools);//
        }

        //执行自身对应筛选函数
        $closure($this);

        //结束当前闭包流程
        $this->boolChildSwitch($bool, false);

        return $this;
    }

    /**
     * notes: 获取集合内bool参数的上标
     * @param $bools
     * @return int|string
     * @author 陈鸿扬 | @date 2022/7/8 19:58
     */
    protected static function currBoolIndex($bools)
    {
        $boolIndex = 0;
        if ($bools !== null) {
            foreach ($bools as $ind => $item) {
                if (isset($item['bool'])) {
                    $boolIndex = $ind;
                    break;
                } else {
                    $boolIndex++;
                }
            }
        }
        return $boolIndex;
    }

    /**
     * notes: 闭包流程控制
     * @param string $bool
     * @param bool $start
     * @author 陈鸿扬 | @date 2022/7/8 19:59
     */
    protected function boolChildSwitch($bool = 'must', $start = false)
    {
        if ($start) {
            //开始当前闭包流程 - 层级id递增
            $this->boolChildStart++;
        } else {
            //结束当前闭包流程 - 层级id递增 - 直到0结束所有闭包流程
            $this->boolChildStart--;
            //回到上级节点
            $this->boolChilds = &$this->boolParents;
            //dd($this->boolParents,$this->boolChilds,$this->bools);//
        }
    }

    /**
     * notes: 设置嵌套筛选条件 到当前 内存引用锚点.
     * @param null $match
     * @param string $bool
     * @author 陈鸿扬 | @date 2022/7/8 19:59
     */
    protected function boolWorker($match = null, $bool = 'must')
    {
        if ($this->boolChilds !== null) {
            //设置嵌套筛选条件 到当前 内存引用锚点.
            $boolIndex = $this->currBoolIndex($this->boolChilds);

            $this->boolChilds[$boolIndex]['bool'][$bool][] = ['match' => $match];

            //dd($this->boolParents,$this->boolChilds,$this->bools);//
        }
    }


    /**
     * notes: 过滤筛选总览
     * @param $key
     * @param $value
     * @param string $bool
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:39
     */
    public function whereIn($key, $value, $bool = 'should')
    {
        //兼容数组的处理 - 转分隔字符串
        if (gettype($value) != 'array') {
            $valueArr = explode(',', $value);
        } else {
            $valueArr = $value;
        }

        if (isset($valueArr[0])) {
            $this->whereInKv($key, $valueArr, $bool);
        }
        return $this;
    }

    /**
     * notes: 取反过滤筛选总览
     * @param $key
     * @param $value
     * @param string $bool
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:40
     */
    public function whereNotIn($key, $value, $bool = 'must_not')
    {
        //兼容数组的处理 - 转分隔字符串
        if (gettype($value) != 'array') {
            $valueArr = explode(',', $value);
        } else {
            $valueArr = $value;
        }

        $this->whereInKv($key, $valueArr, $bool);
        return $this;
    }

    /**
     * notes: 或过滤筛选总览
     * @param $key
     * @param $value
     * @param string $bool
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:40
     */
    public function orWhereIn($key, $value, $bool = 'should')
    {
        //兼容数组的处理 - 转分隔字符串
        if (gettype($value) != 'array') {
            $valueArr = explode(',', $value);
        } else {
            $valueArr = $value;
        }

        $this->whereInKv($key, $valueArr, $bool);
        return $this;
    }

    /**
     * notes: key value 过滤筛选 - 包含字符串用match,纯整数用terms
     * @param $key
     * @param $valueArr
     * @param string $bool - 联合条件: must,must_not,should,term
     * @author 陈鸿扬 | @date 2021/12/30 12:56
     */
    protected function whereInKv($key, $valueArr, $bool = 'must')
    {
        //纯整数集
        $intArr = [];
        //包含字符串值处理
        array_walk($valueArr, function ($val) use ($key, $bool, &$intArr) {
            if ($val == $this->intMatch($val)) {
                $intArr[] = $val;
            } else {
                $this->match($key, $val, $bool);
            }
        });
        //纯整数值处理
        if (!empty($intArr)) {
            $this->terms($key, $intArr, $bool);
        }
    }

    //匹配字符串中数字
    protected function intMatch($string)
    {
        preg_match("/\\d+/i", $string, $m);
        if (isset($m[0])) {
            return $m[0];
        }
        return '';
    }

    //翻页
    public function page($page = 1, $perpage = 20)
    {
        //0输入兼容
        $page    = $page <= 1 ? 1 : $page;
        $perpage = $perpage <= 0 ? 1 : $perpage;
        //步进翻页转换
        $start = ($page <= 1) ? 0 : $page - 1;
        $start = $start * $perpage;
        $end   = $perpage;
        //执行
        $this->limit($start, $end);
        return $this;
    }

    //成组 - 嵌套的 - 度量聚合&桶聚合
    public function groupBy($keyArr)
    {
        if (!empty($keyArr)) {

            //group by 适用 递归聚合
            $this->aggsRecursion  = true; //递归聚合开关 - 打开时 toArray()转换聚合逻辑将改变
            $this->aggsPackKeyArr = $keyArr; //递归聚合 相关key记录,用于toArray()转换数据
            //递归聚合
            $this->aggsComputeRecursion($keyArr);

        }

        return $this;
    }

    //distinct单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function distinct($key = '_id', \Closure $closure = null)
    {
        //聚合计算
        $this->aggsCompute($key, 'cardinality');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //distinct单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function cardinality($key = '_id', \Closure $closure = null)
    {
        //聚合计算
        $this->aggsCompute($key, 'cardinality');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //count单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function count($key = '_id', \Closure $closure = null)
    {
        //聚合计算
        $this->aggsCompute($key, 'count');

        //dsl 修改
        $this->statsType   = 'count'; //度量计算类型名 - 供toArray()函数 做相应转换
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //sum单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function sum($key = '_id', \Closure $closure = null)
    {
        $this->aggsCompute($key, 'sum');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //min单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function min($key = '_id', \Closure $closure = null)
    {
        $this->aggsCompute($key, 'min');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //max单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function max($key = '_id', \Closure $closure = null)
    {
        $this->aggsCompute($key, 'max');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //avg单独合计 - 非嵌套的 - 度量聚合&桶聚合
    public function avg($key = '_id', \Closure $closure = null)
    {
        $this->aggsCompute($key, 'avg');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    //stats全部合计 - 非嵌套的 - 度量聚合&桶聚合
    public function stats($key = '_id', \Closure $closure = null)
    {
        $this->aggsCompute($key, 'stats');

        //dsl 修改
        $this->selectStats = true; //度量计算列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    /**
     * notes: 排序总览
     * @param $key
     * @param $value
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:37
     */
    public function order($key, $value)
    {
        $this->sort($key, $value);
        return $this;
    }

    /**
     * notes: 排序Raw总览
     * @param \Closure|null $closure
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 14:37
     */
    public function orderRaw(\Closure $closure = null)
    {
        $this->sortRaw($closure);
        return $this;
    }

    //结构MAP - 精确匹配
    public function match($key, $value, $bool = 'must')
    {
        $match = ["$key" => $value];

        //闭包流程id大于0时, 启用嵌套筛选.
        if ($this->boolChildStart > 0) {
            //设置嵌套筛选条件 到当前 内存引用锚点.
            $this->boolWorker($match, $bool);
        } else {
            //直接设置嵌套筛选条件 到 根锚点
            $this->bools[0]['bool'][$bool][] = ['match' => $match];
        }

        return $this;
    }

    //结构MAP - 范围查询
    public function range($key, $compare, $value, $bool = 'must')
    {
        if (!empty($compare)) {
            $range = [
                "range" => [
                    "$key" => ["$compare" => $value],
                ],
            ];

            //闭包流程id大于0时, 启用嵌套筛选.
            if ($this->boolChildStart > 0) {
                //设置嵌套筛选条件 到当前 内存引用锚点.
                $this->boolWorker($range, $bool);
            } else {
                //直接设置嵌套筛选条件 到 根锚点
                $this->bools[0]['bool'][$bool][] = $range;
            }
        }
        return $this;
    }

    //结构MAP - 模糊查询
    public function wildcard($key, $value, $bool = 'must')
    {
        $wildcard = ["wildcard" => [
            "$key" . '.keyword' => $value,
        ]];

        //闭包流程id大于0时, 启用嵌套筛选.
        if ($this->boolChildStart > 0) {
            //设置嵌套筛选条件 到当前 内存引用锚点.
            $this->boolWorker($wildcard, $bool);
        } else {
            //直接设置嵌套筛选条件 到 根锚点
            $this->bools[0]['bool'][$bool][] = $wildcard;
        }

        return $this;
    }

    //结构MAP - in 筛选 - 支持多个条件
    protected function terms($key, $valueArr, $bool = 'must')
    {
        $terms = [
            'terms' => [$key => $valueArr],
        ];

        //闭包流程id大于0时, 启用嵌套筛选.
        if ($this->boolChildStart > 0) {
            //设置嵌套筛选条件 到当前 内存引用锚点.
            $this->boolWorker($terms, $bool);
        } else {
            //直接设置嵌套筛选条件 到 根锚点
            $this->bools[0]['bool'][$bool][] = $terms;
        }

        return $this;
    }

    //结构MAP - 精确索引 - 不支持多个条件
    protected function term($key, $value, $bool = 'must')
    {
        //精确查找，不支持多个条件
        $term = [
            'term' => ["$key" => $value],
        ];

        //闭包流程id大于0时, 启用嵌套筛选.
        if ($this->boolChildStart > 0) {
            //设置嵌套筛选条件 到当前 内存引用锚点.
            $this->boolWorker($term, $bool);
        } else {
            //直接设置嵌套筛选条件 到 根锚点
            $this->bools[0]['bool'][$bool][] = $term;
        }

        //非评分模式执行
        $this->query['constant_score'] = $bool;
        return $this;
    }

    /**
     * notes: 结构MAP - 度量聚合&桶聚合
     * @param $key - 字段名
     * @param string $aggType - 度量聚合:min/max/sum/count/avg/stats/cardinality 桶聚合:terms
     * @return $this
     * @author 陈鸿扬 | @date 2022/1/24 11:29
     */
    public function aggs($key, $aggType = 'terms')
    {
        //count聚合不支持,转化为terms, 再合计返回结果的 doc_count
        if ($aggType == "count") {
            $aggType = 'terms';
        }
        //
        $this->aggs[$key]["$aggType"]["field"] = $key;
        return $this;
    }

    //平行聚合计算 - 非嵌套的 - 度量聚合&桶聚合
    protected function aggsCompute($key, $compute = 'terms')
    {
        //平行聚合
        $this->aggsRecursion = false; //递归聚合开关 - 关闭时 toArray()转换聚合逻辑将改变
        $this->aggsKeyArr[]  = $key . "." . $compute; //聚合 相关key记录,用于toArray()转换数据

        //单字段聚合
        $this->aggs($key, $compute);
    }

    //递归聚合计算 - 嵌套的 - 度量聚合&桶聚合
    protected function aggsComputeRecursion($keyArr, $index = 0, &$parent = [])
    {
        if ($index < count($keyArr)) {

            //分割字段和计算符
            $this->aggsComputeFilter($keyArr[$index], $key, $aggType);
            //聚合count不支持,用terms,取doc_count
            if ($aggType == 'count' || empty($aggType)) {
                $aggType = 'terms';
            }
            //dd( $key,$aggType );//

            //组合参数
            if ($index == 0) {
                $index          += 1;
                $this->aggsPack = ["$key" => ["$aggType" => ["field" => $key]]];
                $this->aggsComputeRecursion($keyArr, $index, $this->aggsPack["$key"]);
            } else if (!empty($parent)) {
                $index          += 1;
                $parent['aggs'] = ["$key" => ["$aggType" => ["field" => $key]]];
                $this->aggsComputeRecursion($keyArr, $index, $parent['aggs']["$key"]);
            }

        } else {
            return $parent;
        }

    }

    protected function lowCaseValueMap($data, $key)
    {
        $lowCaseValueMap = [];
        $valueColumn     = array_unique(array_column($data, $key));
        array_walk($valueColumn, function ($value, $index) use (&$lowCaseValueMap) {
            $lowCaseValueMap[strtolower($value)] = $value;
        });
        return $lowCaseValueMap;
    }

    //分割字段和计算符
    protected function aggsComputeFilter($keyItem, &$key, &$aggType)
    {
        $keyItem = explode(".", $keyItem);
        $key     = $keyItem[0];
        if (isset($keyItem[1])) {
            switch ($keyItem[1]) {
                default:
                    $aggType = 'terms';
                    break;
                case 'min':
                case 'max':
                case 'avg':
                case 'sum':
                case 'count':
                case 'stats':
                case 'cardinality':
                    $aggType = $keyItem[1];
                    break;
            }
        }
    }

    //根据字段和计算符 选择组合返回数据
    protected function aggsComputeDataFilter(&$groupArr, $item, $key, $aggType)
    {
        switch ($aggType) {
            default:
                break;
            case 'count':
                $groupArr["$key" . "_" . "$aggType"] = $item["doc_count"]; //计算个数
                break;
            case 'min':
            case 'max':
            case 'avg':
            case 'sum':
                $groupArr["$key" . "_" . "$aggType"] = $item["$key"]["value"]; //其它计算
                break;
        }

        return $groupArr;
    }

    //结构MAP - 排序
    public function sort($key, $value)
    {
        $sort         = ["$key" => ["order" => $value]];
        $this->sort[] = $sort;
        return $this;
    }

    //结构MAP - 排序Raw
    public function sortRaw(\Closure $closure = null)
    {
        $script = $closure();
        if (!empty($script)) {
            $this->sort[] = ["_script" => $script];
        }
        return $this;
    }

    //结构MAP - 步进翻页
    public function limit($raw = 0, $perpage = 20)
    {
        $this->from = $raw;
        $this->size = $perpage;
        return $this;
    }

    //输出查询结构
    public function toDSL(\Closure $closure = null)
    {
        $this->ignoreError();//是否忽略错误信息

        //筛选索引字段
        if (!empty($this->source)) {
            $this->params['_source'] = $this->source;
        }

        //first()函数专用
        if ($this->first) {
            $this->from = 0;
            $this->size = 1;
        }

        //非find()函数专用
        if (!$this->find) {
            unset($this->params['id']);
        }

        //select() //count() sum() ... //first() 等函数专用
        if ($this->select || $this->selectStats || $this->first || $this->aggsRecursion) {
            $this->params['body']['from']         = $this->from;
            $this->params['body']['size']         = $this->size;
            $this->params['body']['track_scores'] = true; //自定义排序支持

            //聚合条件
            if ($this->aggsRecursion == true && !empty($this->aggsPack)) {
                //递归聚合
                $this->params['body']['aggs'] = $this->aggsPack;
            } else if (!empty($this->aggs)) {
                //平行聚合
                $this->params['body']['aggs'] = $this->aggs;
            }

        }

        //筛选条件
        if (!empty($this->bools[0]['bool'])) {
            $this->params['body']['query']['bool'] = $this->bools[0]['bool'];
        }

        //排序
        if (!empty($this->sort)) {
            $this->params['body']['sort'] = $this->sort;
        }

        //对查询结构做补充
        if (!empty($closure)) {
            $closure($this->params);
        }

        return $this->params;
    }

    //执行查询
    public function select(\Closure $closure = null)
    {
        $this->select = true; //提取列表数据 - 供toArray()函数 做相应转换
        $this->toDSL($closure);

        $client       = $this->esClient();
        $this->result = $client->search($this->params);

        return $this;
    }

    //执行查询
    public function first(\Closure $closure = null)
    {
        $this->first = true;//获取单行数据 - toArray()函数 做相应转换
        $this->toDSL($closure);
        $client       = $this->esClient();
        $this->result = $client->search($this->params);
        return $this;
    }

    /**
     * notes: 主键id单查询 - 通过 doc id 获取
     * @param $id
     * @param string $idField
     * @return $this
     * @author 陈鸿扬 | @date 2021/12/30 19:20
     */
    public function find($id, $idField = 'id', \Closure $closure = null)
    {
        $this->ignoreError();//是否忽略错误信息
        $this->find = true;   //获取单行数据 - toArray()函数 做相应转换

        $this->params["$idField"] = $id;
        $this->toDSL($closure);

        //排除其它查询条件
        unset($this->params['body']);

        $client = $this->esClient();
        $result = $client->get($this->params);
        if (isset($result['found']) && $result['found'] != false) {
            $this->result = $result;
        }
        //id不传递
        //unset($this->params["$idField"]);
        //#
        return $this;
    }

    //检查结果是否为空
    public function isEmpty(&$result = null)
    {
        $result = $this->result;
        return empty($this->result);
    }

    /**
     * notes: 输出原始数据
     * @author 陈鸿扬 | @date 2021/12/30 20:01
     */
    public function toSource()
    {
        $result = $this->result;

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $result;
    }

    /**
     * notes: 输出标准数组
     * @param bool $info - 数据详细: true|false
     * @return array
     * @author 陈鸿扬 | @date 2021/12/30 17:41
     */
    public function toArray($info = false)
    {
        //获取索引结构 - 暂存到内部变量
        $this->attributes($fieldTemple);

        $result = $this->result;
        $data   = [];
        $meta   = [];
        $shards = [];
        //dd($result);//

        //区分 单行|多行 数据
        //获取单行
        if ($this->find) {

            if (isset($result['_source'])) {
                $currArr = $this->fieldTemple; //默认索引结构数据模板

                $currArr = array_merge($currArr, $result['_source']);

                //对输出结果字段做佚名处理
                $this->doneFieldAlias($currArr);

                $data = $currArr;
            }

        } //获取多行
        else {

            if (isset($result['hits']['hits'])) {
                $hits = $result['hits']['hits'];
                array_walk($hits, function ($hit) use (&$data, $info) {
                    if (isset($hit['_source'])) {
                        $currArr = $this->fieldTemple; //默认索引结构数据模板

                        $currArr = array_merge($currArr, $hit['_source']);

                        $data[] = $currArr;
                    }
                });
            }

            $meta['page']    = (int)request()->get('_page', 1);
            $meta['perpage'] = $this->size;
            $meta['total']   = 0;
            if (isset($result['hits']['total'])) {
                $total              = $result['hits']['total'];
                $meta['total_page'] = (int)ceil($total / $this->size);
                $meta['perpage']    = $this->size;
                $meta['total']      = $result['hits']['total'];
            }
            $meta['from'] = $this->from;
            $meta['size'] = $this->size;

            if (isset($result['hits']['max_score'])) {
                $meta['max_score'] = $result['hits']['max_score'];
            }
            if (isset($result['took'])) {
                $meta['took'] = $result['took'];
            }
            if (isset($result['timed_out'])) {
                $meta['timed_out'] = $result['timed_out'];
            }

            if (isset($result['_shards'])) {
                $shards = $result['_shards'];
            }

            //聚合处理
            $groupArr = [];
            if (isset($result['aggregations'])) {
                $aggregations = $result['aggregations'];

                //默认平行聚合
                if ($this->aggsRecursion == false) {
                    $groupArr = $this->aggsGroupTrans($this->aggsKeyArr, $aggregations, $data, $info);
                } else {
                    //当递归聚合启用时
                    $groupArr = $this->aggsRecursionGroupTrans($this->aggsPackKeyArr, $aggregations, $data, $info);
                }
            }

            //对输出结果字段做佚名处理
            if ($this->selectStats == true) {
                //度量计算场景
                //count() sum() stats() ...等函数 单独度量计算时, 只取首个聚合数据集.
                $data = $groupArr[0];
                $this->doneFieldAlias($data);
            } else if (isset($result['aggregations'])) {
                //聚合计算场景
                $data = $groupArr;
                array_walk($data, function (&$currArr, $index) {
                    $this->doneFieldAlias($currArr);
                });
            } else {
                //默认场景 - 非度量计算,非聚合计算
                array_walk($data, function (&$currArr, $index) {
                    $this->doneFieldAlias($currArr);
                });
            }

        }

        //输出 区分 单行|多行提取单行|多行|直接计算 数据
        if ($this->find) {
            $response = $data;
        } elseif ($this->first) {
            $response = $data[0] ?? [];
        } elseif ($this->selectStats) {
            $response         = ['data' => $data];
            $response['meta'] = $meta;
        } else {
            $response         = ['data' => $data];
            $response['meta'] = $meta;
        }

        //重置DSL内部常量 - 已便开始新的查询时不受到污染
        $this->resetDslConstant();

        return $response;
    }

    //对输出结果字段-做佚名处理
    protected function doneFieldAlias(array &$currArr)
    {
        foreach ($currArr as $key => $value) {
            if (isset($this->sourceAlias[$key])) {
                $currArr[$this->sourceAlias[$key]] = $value;
                unset($currArr["$key"]);
            }
        }
        return $currArr;
    }

    //输出标准数组 - 平行聚合
    protected function aggsGroupTrans($aggsKeyArr, $aggregations, $data, $info = false)
    {
        //平行聚合 - 数据转换
        $this->aggsBucketTrans($aggsKeyArr, $aggregations, $groupArr);

        //数据详细
        if ($info == true) {
            //平行聚合 - 合并hits数据
            $this->aggsBucketMerge($groupArr, $data);
        }

        return $groupArr;
    }

    //输出标准数组 - 平行聚合 - 数据转换
    protected function aggsBucketTrans($aggsKeyArr, $aggregations, &$groupArr = [])
    {
        //分割字段和运算符 为 数组结构
        $keysCompute = [];
        array_walk($aggsKeyArr, function ($filedStr, $ind) use (&$keysCompute) {
            $filedArr = explode('.', $filedStr);
            if (count($filedArr) == 3) {
                //"name.keyword.count" 的情况
                $keysCompute["$filedArr[0]" . "." . "$filedArr[1]"] = $filedArr[2] ?? 'terms';
            } else {
                //"name.count" 的情况
                $keysCompute["$filedArr[0]"] = $filedArr[1] ?? 'terms';
            }
        });

        array_walk($aggregations, function ($item, $key) use (&$groupArr, $keysCompute) {
            $computeStr = $keysCompute["$key"] ?? 'terms';
            switch ($keysCompute["$key"]) {
                default:
                    $currArr = [];
                    break;
                case 'count':
                    $compute = array_sum(array_column($item["buckets"], 'doc_count'));
                    //补充未列出的量
                    if (isset($item["sum_other_doc_count"])) {
                        $compute += $item["sum_other_doc_count"];
                    }
                    //
                    $currArr[$key . "_" . $computeStr] = $compute;
                    break;
                case 'min':
                case 'max':
                case 'sum':
                case 'avg':
                case 'cardinality':
                    $compute                           = $item["value"];
                    $currArr[$key . "_" . $computeStr] = $compute;
                    break;
                case 'stats':
                    $currArr = [];
                    array_walk($item, function ($val, $currComputeStr) use (&$currArr, $key) {
                        $currArr[$key . "_" . $currComputeStr] = $val;
                    });
                    break;
            }

            $groupArr[] = $currArr;
        });

    }

    //平行聚合 - 合并hits数据
    protected function aggsBucketMerge($groupArr, $data)
    {
        //好像用不上,先留着.
    }

    //输出标准数组 - 递归聚合
    protected function aggsRecursionGroupTrans($aggsRecursionKeyArr, $aggregations, $data, $info = false)
    {
        //递归聚合 - 数据转换
        $this->aggsRecursionBucketTrans($aggsRecursionKeyArr, $aggregations, $groupArr, 0, $data);

        //数据详细
        if ($info == true) {
            //递归聚合 - 合并hits数据
            $this->aggsRecursionBucketMerge($groupArr, $data);
        }

        if (empty($groupArr['_data'])) {
            $groupArr['_data'] = [];
        }
        return $groupArr['_data'];
    }

    //输出标准数组 - 递归聚合 - 数据转换
    protected function aggsRecursionBucketTrans($aggsRecursionKeyArr, $aggregations, &$groupArr = [], $index = 0, $data = [])
    {
        if ($index < count($aggsRecursionKeyArr)) {
            //组查询字段 - 最大上标
            $maxIndex = count($aggsRecursionKeyArr) - 1;
            //组查询字段 -
            $keyItem = $aggsRecursionKeyArr[$index];
            //分割字段和运算符
            $this->aggsComputeFilter($keyItem, $key, $aggType);
            //截取 聚合字段 在数据中的值
            $lowCaseValueMap = $this->lowCaseValueMap($data, $key);
            //
            if (isset($aggregations["$key"]["buckets"]) && $aggType != 'count') {

                //# term 非运算返回结构特征
                array_walk($aggregations["$key"]["buckets"],
                    function (&$item, $ind) use (&$groupArr, $key, $aggType, $aggsRecursionKeyArr, $index, $maxIndex, $data, $lowCaseValueMap) {

                        //字符串聚合时,会转成小写. 需要截取元数据值 转回来. - 不实用,元数据不包含所有值,有缺陷,关闭.
//                        $keyValue = $item["key"];
//                        if (gettype($keyValue) == 'string') {
//                            $item["key"] = $lowCaseValueMap[$keyValue] ?? $keyValue;
//                        }

                        //缓存上层数据
                        $groupArr["_temp"]["$key"] = $item["key"];
                        $currTemp                  = $groupArr["_temp"];

                        //递归到最后一层,才设置数据
                        if ($index == $maxIndex) {
                            $currArr["$key"]     = $item["key"];
                            $groupArr["_data"][] = array_merge($currTemp, $currArr);
                        }

                        //往下一层递归
                        $index += 1;
                        $this->aggsRecursionBucketTrans($aggsRecursionKeyArr, $item, $groupArr, $index, $data);
                    });
            } elseif (isset($aggregations["$key"]["buckets"]) && $aggType == 'count') {
                //# count 运算返回结构特征

                //上级缓存数据
                if (empty($groupArr["_temp"])) {
                    $currTemp = [];
                } else {
                    $currTemp = $groupArr["_temp"];
                }

                //合计命中字段的doc个数
                $buckets      = $aggregations["$key"]["buckets"];
                $bucketsCount = array_sum(array_column($buckets, 'doc_count'));

                //组合运算类型数据
                $currArr["$key" . "_" . "$aggType"] = $bucketsCount;
                //本级数据 合并覆盖 上级缓存数据
                $groupArr["_data"][] = array_merge($currTemp, $currArr);

            } else if (isset($aggregations["$key"]["value"])) {
                //# sum/avg/min/max 运算返回结构特征

                //上级缓存数据
                if (empty($groupArr["_temp"])) {
                    $currTemp = [];
                } else {
                    $currTemp = $groupArr["_temp"];
                }

                //递归到最后一层,才设置数据
                if ($index == $maxIndex) {
                    //组合运算类型数据
                    $currArr["$key" . "_" . "$aggType"] = $aggregations["$key"]["value"];
                    //本级数据 合并覆盖 上级缓存数据
                    $groupArr["_data"][] = array_merge($currTemp, $currArr);
                }

            } else if (isset($aggregations["$key"]) && $aggType == 'stats') {
                //# stats 运算返回结构特征

                //上级缓存数据
                if (empty($groupArr["_temp"])) {
                    $currTemp = [];
                } else {
                    $currTemp = $groupArr["_temp"];
                }

                //组装
                $stats   = $aggregations["$key"];
                $currArr = [];
                array_walk($stats, function ($value, $computeKey) use ($key, &$currArr) {
                    $currArr[$key . "_" . $computeKey] = $value;
                });

                //本级数据 合并覆盖 上级缓存数据
                $groupArr["_data"][] = array_merge($currTemp, $currArr);

            }

        }
    }

    //输出标准数组 - 递归聚合 - 合并hits数据
    protected function aggsRecursionBucketMerge(&$groupArr, $data)
    {
        if (!empty($groupArr['_data'])) {
            array_walk($groupArr['_data'], function (&$item, $key) use ($data) {

                //#本级$item所有key的值 在 $data中, 找到多个值完全匹配的行.

                //获取所有字段名
                $keys = array_keys($item);
                //初始化缓存变量
                $temp  = [];
                $index = -1;

                //多个字段名
                if (count($keys) > 1) {
                    foreach ($keys as $i => $k) {
                        //hits data 字段名对应的列
                        $columns = array_column($data, $k);
                        //得到对应的上标
                        $searchIndex = array_search($item["$k"], $columns);
                        if ($searchIndex !== false) {
                            $index = $searchIndex;
                        }

                        //积累 $item所有key的值 在 hit data 中的位置 - 后面出现在相同位置的上标 将同步到$index
                        foreach ($columns as $ii => $kk) {
                            if ($item[$k] == $kk) {
                                //匹配上标与 积累上标有重叠时, 确认最终上标.
                                if (in_array($ii, $temp)) {
                                    $index = $ii;
                                }
                                //累记上标
                                $temp[] = $ii;
                            }
                        }
                    }
                } else {
                    //单个字段
                    foreach ($keys as $i => $k) {
                        $columns     = array_column($data, $k);
                        $searchIndex = array_search($item["$k"], $columns);
                        if ($searchIndex !== false) {
                            $index = $searchIndex;
                        }
                    }
                }

                if ($index != -1) {
                    $item = array_merge($data[$index], $item);
                }

            });
        }
    }

    //忽略错误信息
    protected function ignoreError()
    {
        $this->params['client']['ignore'] = 404;
    }

    //二级数组合并
    protected static function ArrayMergeLv2($list, $beList, $pKey = 'id')
    {
        $newArr = $beList;
        $docIds = array_column($beList, $pKey);
        array_walk($list, function (&$item, $index) use (&$newArr, $pKey, $docIds, $beList) {
            if (in_array($item["$pKey"], $docIds)) {
                $ind  = array_search($item['doc_id'], $docIds);
                $item = array_merge($item, $beList[$ind]);
                //新列表的位置替换合并后的内容
                $newArr[$ind] = $item;
            }
        });
        return $newArr;
    }

}
