<?php

namespace domain\oauth\repository;

use domain\base\exception\Exception;
use domain\base\repository\BaseRepository;
use domain\oauth\error\OauthRootError;
use domain\oauth\model\OauthRoleModel;
use extend\utils\QueryMatch;

/**
 * notes: 领域层-仓储类
 * 说明: 只写数据操作,不写别的内容,对应同名model
 * 调用原则: 向下调用[模型类]
 */
class OauthRoleRepo extends BaseRepository
{
    //初始化 self::$query 模型对象
    protected $model = OauthRoleModel::class;

//{@block_br}
    /*
     * 获取query查询表达式参数 - 适合不依赖 Model::scope 的场景
     * 通常不需要修改,可在同一个数据单元内共用. 如果需要附加查询条件,可调用 scopeExtend().
     */
    public static function queryMatchIndex(QueryMatch $QM)
    {
        $query = self::$query;

        //获取query查询表达式参数

        //根据 _search=default 参数, 切换 捕捉到 ?type=1&status=1 ...的值的运算符.
        $rule = null;
        $QM->searchAction($action);
        if ($action == 'default') {
            //$rule = ['type' => '>=', 'status' => '>',]; //例子
        }

        //捕捉 ?type=1&status=1 ... 的值, 转化成查询数组
        $filterArr = (new OauthRoleModel())->getFieldKeys();
        $QM->search($searchArr, $rule, $filterArr);
        if (!empty($searchArr)) {
            $QM->whereClosure($searchArr, function ($data) use (&$query) {
                $query->where($data[0], $data[1], $data[2]);
            });
        }

        //?where_in_sort=status/1,2,3 //按id顺序返回结果
        $QM->whereInSort($whereInSortArr, $sortItem);
        if (!empty($whereInSortArr)) {
            $QM->whereInSortClosure($whereInSortArr, $sortItem, function ($data, $rawStr) use (&$query) {
                $query->where($data[0], $data[1], $data[2]);
                $query->orderRaw($rawStr);
            }
            );
        }

        //?sort = -id
        $QM->sort($sortArr);
        if (!empty($sortArr)) {
            $query->order($sortArr);
        }

        //?include=user,info - 副表关联模型,用于数据输出,不是查询条件.
        $except = []; //排除关联模型-改为后置关联模型调用
        $QM->include($includeArr, OauthRoleModel::class, $except);
        if (!empty($includeArr)) {

            //关联预载入,分开查询再组合,不是联表,提高性能.
            $query->with($includeArr);

        }

    }
//{@block_br/}

//{@block_br}
    /*
     * 副表查询扩展 - 扩展类型+传参方式 - 用于附加查询条件,不是数据输出.
     * 对query: 'extend' 参数的获取, 作为关联查询的触发条件, 编写具体逻辑作用到当前查询中
     * where子查询: https://www.kancloud.cn/manual/thinkphp6_0/1037569
     */
    public function scopeExtend(array $requestQuery)
    {
        $_extend = $requestQuery['extend'] ?? null;
        $extendArr = explode(',', $_extend);
        if (in_array('param', $extendArr)) {
            $query = self::$query;
            //副表条件 - 子查询附加条件到主表, 降低直接联表的时间复杂度(笛卡尔积).
            //$query->where('id', 'IN', function ($childQuery) use ($requestQuery) {});
        }
    }
//{@block_br/}

//{@block_r}
    /*
     * 获取query查询表达式参数 - 适合不依赖 Model::scope 的场景
     * 通常不需要修改,可在同一个数据单元内共用. 如果需要附加查询条件,可调用 scopeExtend().
     */
    public function queryMatchRead(QueryMatch $QM)
    {
        $query = self::$query;

        //获取query查询表达式参数

        //根据 _search=default 参数, 切换 捕捉到 ?type=1&status=1 ...的值的运算符.
        $rule = null;
        $QM->searchAction($action);
        if ($action == 'default') {
            //$rule = ['type' => '>=', 'status' => '>',]; //例子
        }

        //捕捉 ?type=1&status=1 ... 的值, 转化成查询数组
        $filterArr = (new OauthRoleModel())->getFieldKeys();
        $QM->search($searchArr, $rule, $filterArr);
        if (!empty($searchArr)) {
            $QM->whereClosure($searchArr, function ($data) use (&$query) {
                $query->where($data[0], $data[1], $data[2]);
            });
        }

        //?sort = -id
        $QM->sort($sortArr);
        if (!empty($sortArr)) {
            $query->order($sortArr);
        }

        //?include=user,info - 副表关联模型,用于数据输出,不是查询条件.
        $except = []; //排除关联模型-改为后置关联模型调用
        $QM->include($includeArr, OauthRoleModel::class, $except);
        if (!empty($includeArr)) {

            //关联预载入,分开查询再组合,不是联表,提高性能.
            $query->with($includeArr);

        }

    }
//{@block_r/}

    //根据ID获取详细
    public function isIdHave($id)
    {
        $where = ['id' => $id];
        $field = ['id'];
        $field = array_merge($field, array_keys($where));
        $query = self::$query->field($field)->where($where);

        $result = $query->find();
        return $result;
    }

    //检查是否存在
    public function isIdExist($id)
    {
        $where = ['id' => $id];
        $field = ['id'];
        $field = array_merge($field, array_keys($where));
        $query = self::$query->field($field)->where($where);

        $result = $query->find();
        if (!$result) {
            Exception::App(OauthRootError::code("ID_NOT_FOUND"), OauthRootError::msg("ID_NOT_FOUND"), __METHOD__);
        };
        return $result;
    }

    public static function isRoleIdExist($roleId){
        $where=["role_id"=>$roleId];
        $field=['id','role_id'];
        $field= array_merge( $field, array_keys($where) );
        $result=self::$query->field($field)->where($where)->find();
        if( !$result ){
            Exception::app( OauthRootError::code("ID_NOT_FOUND") , OauthRootError::msg("ID_NOT_FOUND") ,__METHOD__);
        };
        return $result;
    }

    //检查是否重复
    public function isIdUnique($id)
    {
        $where = ['id' => $id];
        $field = ['id'];
        $field = array_merge($field, array_keys($where));
        $query = self::$query->field($field)->where($where);

        $result = $query->find();
        if ($result) {
            Exception::App(OauthRootError::code("ID_NOT_UNIQUE"), OauthRootError::msg("ID_NOT_UNIQUE"), __METHOD__);
        };
        return $result;
    }

    //检查 id数量 和 返回id数量 是否相等
    public function isBatchIdsExist($ids)
    {
        $field = ['id'];
        $query = self::$query->field($field)->whereIn('id', $ids);

        $result = $query->select();
        if (count($ids) != count($result)) {
            Exception::App(OauthRootError::code("BATCH_IDS_NOT_FOUND"), OauthRootError::msg("BATCH_IDS_NOT_FOUND"), __METHOD__);
        };
        return $result;
    }

}