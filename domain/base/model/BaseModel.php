<?php

namespace domain\base\model;

use think\Config;
use think\Db;
use think\Model;

/**
 * Class BaseModel - 模型基础类 - 扩展模型功能
 * @see base/model/BaseModel.md - 扩展方法说明
 * @package domain\base\model
 */
class BaseModel extends Model
{
    //定义全局的查询范围 base - 调用 BaseModel->scopeBase()
    protected $globalScope = ['base'];

    //重设表名-兼容跨库sql语句
    public function tableNameReset(&$query)
    {
        $dbConfig = $query->getConfig();
        $database = $dbConfig['database'];
        $table    = $query->getTable();
        $query->setTable("`" . $database . "`.`" . $table . "`");
        return $query;
    }

    //取不到 实例值 时,返回null.
    public function __get($argv)
    {
        $data = self::getData();
        $keys = array_keys($data);
        if (!in_array($argv, $keys)) {
            return null;
        } else {
            //var_dump($argv);
            //var_dump($data["$argv"]);
            return $data["$argv"];
        }
    }

    //字段值递增 - 需要先查询单条数据 返回数据必须存在主键id
    public function increment($field, $incNum = 1, &$newNum = 1, $floatDecimal = null)
    {
        if (!empty($this->getData())) {
            $data   = $this->getData();
            $id     = $data['id'];
            $oldNum = $data["$field"];

            if (!empty($float)) {
                $newNum = bcadd($oldNum, $incNum, $floatDecimal); //精度保留小数后n位
            } else {
                $newNum = $oldNum + $incNum;//返回形参引用值
            }

            return $this->update(["$field" => $newNum], ['id' => $id]);
        }
        return null;
    }

    //字段值递减 - 需要先查询单条数据 返回数据必须存在主键id
    public function decrement($field, $incNum = 1, &$newNum = 1, $floatDecimal = null)
    {
        if (!empty($this->getData())) {
            $data   = $this->getData();
            $id     = $data['id'];
            $oldNum = $data["$field"];

            if (!empty($float)) {
                $newNum = bcsub($oldNum, $incNum, $floatDecimal); //精度保留小数后n位
            } else {
                $newNum = $oldNum - $incNum; //返回形参引用值
            }

            return $this->update(["$field" => $newNum], ['id' => $id]);
        }
        return null;
    }

    //获取数据库字段
    public function getFieldKeys()
    {
        if (!empty($this->type)) {
            //type
            $fieldKeyArr = $this->type;
        } else if (!empty($this->schema)) {
            //schema
            $fieldKeyArr = $this->schema;
        } else {
            //db fields
            $fieldKeyArr = $this->getFieldKeys();
        }
        $fieldKeys = array_keys($fieldKeyArr);
        return $fieldKeys;
    }

    //获取查询结果中的id - 需要先查询单条数据 返回数据必须存在主键id
    public function getId()
    {
        if (!empty($this->getData())) {
            $pk = $this->getPk();
            return $this->getData()[$pk];
        }
        return null;
    }

    //获取查询结果中的id进行更新操作 - 需要先查询单条数据 返回数据必须存在主键id
    public function findAndUpdate($upData)
    {
        if (!empty($this->getData())) {
            $data   = $this->getData();
            $pk     = $this->getPk();
            $result = $this->update($upData, [$pk => $data[$pk]]);
            if ($result) {
                $upData[$pk] = $data[$pk];
                ksort($upData);
                return $upData;
            }
        }
        return null;
    }

    /**
     * notes: 批量新增或更新
     * @author 陈鸿扬 | @date 2022/3/26 21:11
     * @param $data - 列数据
     * @param $fieldKey - 唯一查询条件 - 可以是主键 或 业务唯一ID
     * @param string $primaryKey - 主键ID用于更新数据 不用复杂条件更新 不好控制
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function batchCover($data, $fieldKey, $primaryKey = 'id')
    {
        $ids         = array_column($data, $fieldKey);
        $map         = [
            "$fieldKey" => ["IN", $ids],
        ];
        $fieldArr    = [$primaryKey, $fieldKey];
        $upIdsList   = Db::table($this->getTable())->field($fieldArr)->where($map)->select();
        $upFieldKeys = array_column($upIdsList, $fieldKey);

        $inData = [];
        $upData = [];
        //数据分流
        array_walk($data, function ($item) use (&$inData, &$upData, $fieldKey, $upFieldKeys, $upIdsList, $primaryKey) {
            if (in_array($item["$fieldKey"], $upFieldKeys)) {
                $index               = array_search($item["$fieldKey"], $upFieldKeys);
                $item["$primaryKey"] = $upIdsList[$index]["$primaryKey"];
                $upData[]            = $item;
            } else {
                $inData[] = $item;
            }
        });

        if (!empty($inData)) {
            $this->addAll($inData);
        }
        if (!empty($upData)) {
            $this->updateAll($upData, $primaryKey);
        }

    }

    //批量新增
    public function addAll(Array $data)
    {
        $result = Db::table($this->getTable())->insertAll($data);
        return $result;
    }

    //批量更新
    public function updateAll($data, $primaryKey = 'id')
    {
        $ids        = [];
        $upFieldArr = [];
        $whenStrArr = [];
        $updateSql  = "";
        if (!empty($data)) {
            //获取 待赋值字段 集合
            foreach ($data[0] as $m => $n) {
                if ($m == $primaryKey) {
                    continue;
                }
                $upFieldArr[] = $m;
            }
            //拼接 待赋值 query
            foreach ($data as $k => $v) {
                $ids[] = $v[$primaryKey];
                foreach ($upFieldArr as $ind => $key) {
                    $whenStr = " WHEN '" . $v[$primaryKey] . "' THEN '" . $v[$key] . "'";
                    if ($k == 0) {
                        $whenStrArr[$key] = "`" . $key . "` = CASE " . $primaryKey . " " . $whenStr;
                        if ($k == count($data) - 1) {
                            $whenStrArr[$key] .= " END ";
                        }
                    } else if ($k == count($data) - 1) {
                        $whenStrArr[$key] .= $whenStr . " END ";
                    } else {
                        $whenStrArr[$key] .= $whenStr;
                    }
                }
            }
            //合成 总query
            $whenStrArr = array_values($whenStrArr);
            $ids        = implode(',', $ids);
            //数据库参数
            $prefix = Config::get('database.prefix');
            $table  = $this->getTable();
            //
            $updateSql = "UPDATE `" . $table . "` SET ";
            foreach ($whenStrArr as $ke => $va) {
                if ($ke == count($whenStrArr) - 1) {
                    $updateSql .= $va;
                } else {
                    $updateSql .= $va . ", ";
                }
            }
            $updateSql .= "WHERE `" . $primaryKey . "` IN(" . $ids . ")";
            $result    = DB::execute(DB::raw($updateSql));
            return $result;
        }
        return null;
    }

    //附加翻页信息
    public static function pageAble($query)
    {

        $collect = [];
        //打开翻页时 才有 meta 数据
        if (request()->param("_pagination") != 'false') {

            $tableQuery = $query;
            $tableCount = $tableQuery->count('id');

            //翻页默认值预处理
            self::pageLimitFilter($page, $perpage);
            $row = ($page - 1) * $perpage;
            $query->limit($row, $perpage);

            $meta['pagination'] = true;
            $meta['perpage']    = $perpage;
            $meta['page']       = $page;
            $meta['total_page'] = ceil($tableCount / $perpage);
            $meta['total']      = $tableCount;

            $collect['meta'] = $meta;
        }

        //执行数据查询
        $collectArr         = $query->select();
        $collect['collect'] = $collectArr;

        return $collect;
    }

    //翻页默认值预处理
    protected static function pageLimitFilter(&$page, &$perpage)
    {
        if (request()->param("_pagination") != 'false') {
            $perpage = (int)request()->get('_page_size', 20);
            $page    = (int)request()->get('_page', 1);
        } else {
            //如果关闭翻页 最大翻页条数 上限到100
            $perpage = (int)request()->get('_page_size', 100);
            $page    = (int)request()->get('_page', 1);
        }
        if ($page < 1) {
            $page = 1;
        };
    }

    //目标数据不为空
    public static function NoEmpty($data, $key)
    {
        if (isset($data[$key]) && $data[$key] != '') {
            return true;
        }
        return false;
    }

    /*
     * 提交数据过滤
     */
    public static function tableFieldFilter(&$model)
    {

        $field = self::getTableFields();
        $data  = $model->data;
        foreach ($data as $k => $v) {
            if (!in_array($k, $field)) {
                unset($data[$k]);
            }
        }
        $model->data = $data;

    }


    ######


    //后置关联预载入
    protected $dataType;
    protected $resultData;
    protected $arrayData;

    //后置关联预载入-变量
    protected $withName; //输出集合字段名
    protected $startKey; //起始表-关联字段
    protected $leftKey;  //中间表-左联字段
    protected $rightKey; //中间表-右联字段
    protected $rightValueChange; //中间表-右联字段-分隔符值截取
    protected $endKey;  //结束表-关联字段

    //后置关联预载入-模型
    protected $startModel; //起始模型
    protected $middleModel; //中间模型
    protected $endModel; //结束模型

    /**
     * notes: 后置关联预载入
     * @param array $withGroup - 关联子级继承模型 带 "af_"+方法名 的函数
     * @author 陈鸿扬 | @date 2023/1/29 18:41
     */
    public function afterWith(array $withGroup)
    {
        $collectObj = $this; //执行查询之后的整体继承模型-模型内包含查询结果
        if (!empty($withGroup)) {
            array_walk($withGroup, function ($withName) use ($collectObj) {
                $withMethod   = 'af_' . $withName;
                $methodExists = method_exists($this, $withMethod);
                if ($methodExists == true) {
                    $this->{$withMethod}($collectObj);
                }
            });
        }
    }

    /**
     * notes: 后置关联预载入-初始化-多对多
     * @param $resultData - 起始表查询结果对象
     * @param $withName - 输出集合字段名
     * @return $this
     * @author 陈鸿扬 | @date 2022/4/13 19:17
     */
    public function afterWithInit(&$resultData, $withName)
    {
        $this->arrayData = json_decode(json_encode($resultData), true);
        //dd( gettype($this->arrayData),  isset($this->arrayData[0]) );
        if (!empty($this->arrayData)) {
            //单行数据判断
            $this->dataType = 'list';
            if (!isset($this->arrayData[0])) {
                $this->dataType = 'info';
            }
            $this->resultData = $resultData;
            $this->withName   = $withName;
        }
        return $this;
    }

    /**
     * notes: 后置关联预载入-中间表-多对多
     * @param $startKey - 起始表关联字段
     * @param $middleModelClass - 中间表模型
     * @param $endModelClass - 结束表模型
     * @param $leftKey - 左联字段
     * @param $rightKey - 右联字段
     * @param $endKey - 结束表关联字段
     * @return $this
     * @author 陈鸿扬 | @date 2022/4/13 19:20
     */
    public function afLongsToMany($middleModelClass, $endModelClass, $startKey, $leftKey, $rightKey, $endKey)
    {
        if (!empty($this->arrayData)) {

            //起始表
            $this->startKey = $startKey;
            //#

            //中间表条件筛选
            $this->leftKey = $leftKey;
            if ($this->dataType == 'list') {
                $startIds = array_column($this->arrayData, $startKey);
            }
            if ($this->dataType == 'info') {
                $startIds = [$this->arrayData["$startKey"]];
            }
            //
            if (gettype($middleModelClass) == 'object') {
                $this->middleModel = $middleModelClass->whereIn($leftKey, $startIds)->select();
            } else {
                $this->middleModel = $middleModelClass::field(['*'])->whereIn($leftKey, $startIds)->select();
            }
            //#

            //结束表条件筛选
            $this->endKey = $endKey;
            $middleData   = [];
            if (isset($this->middleModel[0])) {
                array_walk($this->middleModel, function (&$itemObj, $index) use (&$middleData) {
                    $middleData[] = $itemObj->toArray();
                });
            } else {
                $middleData[] = !empty($this->middleModel) ? $this->middleModel->toArray() : [];
            }
            //结束表-关联id批处理
            if (gettype($rightKey) == 'object') {
                $rightKeyWalk = $rightKey; //中间表-右联字段-处理闭包
                $endIds       = [];
                array_walk($middleData, function ($item) use (&$endIds, &$rightKeyWalk) {
                    $currRightKey = null;
                    //闭包修改了$currRightKey,返回了截取值.
                    $currEndId = $rightKeyWalk($item, $currRightKey);
                    if (!empty($currRightKey) && getType($currEndId) != 'NULL') {
                        $endIds[]       = $currEndId;
                        $this->rightKey = $currRightKey;
                        //暂存 分隔符值截取值-关系数组 [ 旧值 => 新值 ]
                        $currValue                          = $item[$currRightKey];
                        $this->rightValueChange[$currValue] = $currEndId;
                    }
                });
            } else {
                $endIds         = array_column($middleData, $rightKey);
                $this->rightKey = $rightKey;
            }

            //
            if (gettype($endModelClass) == 'object') {
                $this->endModel = $endModelClass->whereIn($endKey, $endIds);
            } else {
                $this->endModel = $endModelClass::field(['*'])->whereIn($endKey, $endIds);
            }
            //#

            //结束表数据查询
            $this->afSelect();
            //#

        }
        return $this;
    }

    /**
     * notes: 后置关联预载入-结束表数据查询
     * @return mixed
     * @author 陈鸿扬 | @date 2022/4/21 17:55
     */
    private function afSelect()
    {
        if (!empty($this->arrayData)) {

            $startKey = $this->startKey;
            $leftKey  = $this->leftKey;
            $rightKey = $this->rightKey;
            $endKey   = $this->endKey;

            //查询结束表
            $this->endModel = $this->endModel->select();

            $temp = [];
            if (!empty($this->endModel)) {
                foreach ($this->middleModel as $index => $item) {
                    $leftKeyIndex  = $item->{$leftKey};
                    $rightKeyIndex = $item->{$rightKey};
                    //检查 分隔符值截取值-关系数组, 有则替换
                    if (isset($this->rightValueChange[$rightKeyIndex])) {
                        $rightKeyIndex = $this->rightValueChange[$rightKeyIndex];
                    }
                    //获取 当前右联key 在 结束表中存在的数据
                    foreach ($this->endModel as $ind => $childItem) {
                        if ($rightKeyIndex == $childItem->{$endKey}) {
                            $temp[$leftKeyIndex][] = $childItem;
                        }
                    }
                }
            }

            if ($this->dataType == 'list') {
                foreach ($this->resultData as $index => &$item) {
                    if (isset($temp[$item->{$startKey}]) && !empty($temp)) {
                        $item->{"$this->withName"} = $temp[$item->{$startKey}];
                    }
                }
            }
            if ($this->dataType == 'info') {
                if (isset($this->resultData->{$startKey}) && !empty($temp)) {
                    $this->resultData->{"$this->withName"} = $temp[$this->resultData->{$startKey}];
                }
            }

        }
        return $this->resultData;
    }

    /**
     * notes: 后置关联预载入-中间表-多对多
     * @param $startKey - 起始表关联字段
     * @param $middleModelClass - 中间表模型
     * @param $endModelClass - 结束表模型
     * @param $leftKey - 左联字段
     * @param $rightKey - 右联字段
     * @param $endKey - 结束表关联字段
     * @return $this
     * @author 陈鸿扬 | @date 2022/4/13 19:20
     */
    public function afLongsToOne($middleModelClass, $endModelClass, $startKey, $leftKey, $rightKey, $endKey)
    {
        if (!empty($this->arrayData)) {

            //起始表
            $this->startKey = $startKey;
            //#

            //中间表条件筛选
            $this->leftKey = $leftKey;
            if ($this->dataType == 'list') {
                $startIds = array_column($this->arrayData, $startKey);
            }
            if ($this->dataType == 'info') {
                $startIds = [$this->arrayData["$startKey"]];
            }
            //
            if (gettype($middleModelClass) == 'object') {
                $this->middleModel = $middleModelClass->whereIn($leftKey, $startIds)->select();
            } else {
                $this->middleModel = $middleModelClass::field(['*'])->whereIn($leftKey, $startIds)->select();
            }
            //#

            //结束表条件筛选
            $this->endKey = $endKey;
            $middleData   = [];
            if (isset($this->middleModel[0])) {
                array_walk($this->middleModel, function (&$itemObj, $index) use (&$middleData) {
                    $middleData[] = $itemObj->toArray();
                });
            } else {
                $middleData[] = !empty($this->middleModel) ? $this->middleModel->toArray() : [];
            }
            //结束表-关联id批处理
            if (gettype($rightKey) == 'object') {
                $rightKeyWalk = $rightKey; //中间表-右联字段-处理闭包
                $endIds       = [];
                array_walk($middleData, function ($item) use (&$endIds, &$rightKeyWalk) {
                    $currRightKey = null;
                    //闭包修改了$currRightKey,返回了截取值.
                    $currEndId = $rightKeyWalk($item, $currRightKey);
                    if (!empty($item[$currRightKey]) && getType($currEndId) != 'NULL') {
                        $endIds[]       = $currEndId;
                        $this->rightKey = $currRightKey;
                        //暂存 分隔符值截取值-关系数组 [ 旧值 => 新值 ]
                        $currValue                          = $item[$currRightKey];
                        $this->rightValueChange[$currValue] = $currEndId;
                    }
                });
            } else {
                $endIds         = array_column($middleData, $rightKey);
                $this->rightKey = $rightKey;
            }

            //
            if (gettype($endModelClass) == 'object') {
                $this->endModel = $endModelClass->whereIn($endKey, $endIds);
            } else {
                $this->endModel = $endModelClass::field(['*'])->whereIn($endKey, $endIds);
            }
            //#

            //结束表数据查询
            $this->afFind();
            //#

        }
        return $this;
    }

    /**
     * notes: 后置关联预载入-结束表数据查询
     * @return mixed
     * @author 陈鸿扬 | @date 2022/4/21 17:55
     */
    private function afFind()
    {
        if (!empty($this->arrayData)) {

            $startKey = $this->startKey;
            $leftKey  = $this->leftKey;
            $rightKey = $this->rightKey;
            $endKey   = $this->endKey;

            //查询结束表
            $this->endModel = $this->endModel->find();

            $temp = [];
            if (!empty($this->endModel)) {
                foreach ($this->middleModel as $index => $item) {
                    $leftKeyIndex  = $item->{$leftKey};
                    $rightKeyIndex = $item->{$rightKey};
                    //检查 分隔符值截取值-关系数组, 有则替换
                    if (isset($this->rightValueChange[$rightKeyIndex])) {
                        $rightKeyIndex = $this->rightValueChange[$rightKeyIndex];
                    }
                    //获取 当前右联key 在 结束表中存在的数据
                    if ($rightKeyIndex == $this->endModel->{$endKey}) {
                        $temp[$leftKeyIndex] = $this->endModel;
                    }
                }
            }

            if ($this->dataType == 'list') {
                foreach ($this->resultData as $index => &$item) {
                    if (isset($temp[$item->{$startKey}]) && !empty($temp)) {
                        $item->{"$this->withName"} = $temp[$item->{$startKey}];
                    }
                }
            }
            if ($this->dataType == 'info') {
                if (isset($this->resultData->{$startKey}) && !empty($temp)) {
                    $this->resultData->{"$this->withName"} = $temp[$this->resultData->{$startKey}];
                }
            }

        }
        return $this->resultData;
    }

}