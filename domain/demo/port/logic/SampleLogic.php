<?php

namespace domain\demo\port\logic;

use domain\base\logic\BaseLogic;
use domain\demo\job\SampleSaveJob;
use domain\demo\repository\SampleRepo;
use extend\log\backTrace;
use extend\utils\QueryMatch;
use think\Queue;

//{@hidden
//@hidden}

/**
 * notes: 应用层-业务类
 * 说明: 业务类数据操作,一般不直接调用模型,通过仓储类提供存粹的数据执行函数, 跨 应用端/模块 操作同一数据类型的业务, 建议抽象到 领域层-业务类, 减少冗余.
 * 调用原则: 向下调用[仓储类,领域层-业务类]
 */
class SampleLogic extends BaseLogic
{
//{@block_c}
    /*
     * 新增数据 - 模板
     */
    public function sampleSave(&$requestInput)
    {
        //业务逻辑
        $result = SampleRepo::create($requestInput);

        return $result;
    }
//{@block_c/}

//{@block_cj}
    /*
     * 新增队列数据 - 模板
     */
    public function sampleJobSave(&$requestInput){

        //队列发送
        //Queue::push(SampleSaveJob::class , $requestInput , 'SampleSaveJob' );
        Queue::later(1, SampleSaveJob::class, $requestInput, 'SampleSaveJob');

        return $requestInput;
    }
//{@block_cj/}

//{@block_bc}
    /*
     * 批量新增数据 - 模板
     */
    public function sampleBatchSave(&$requestInput)
    {
        //业务逻辑
        $result = (new SampleRepo())->saveAll($requestInput);

        return $result;
    }
//{@block_bc/}

//{@block_u}
    /*
     * 根据 主键id 更新详情 - 模板
     */
    public function sampleUpdate($id, &$requestInput)
    {
        //业务逻辑
        $query = SampleRepo::newInstance();
        $query->isIdExist($id);

        $result = SampleRepo::update($requestInput, ['id' => $id]);

        return $result;
    }
//{@block_u/}

//{@block_bu}
    /*
     * 根据 主键id 批量更新 - 模板
     */
    public function sampleBatchUpdate(&$requestInput)
    {
        //业务逻辑
        $ids = array_column($requestInput, 'id');

        $query = SampleRepo::newInstance();
        $query->isBatchIdsExist($ids);

        $result = [];
        foreach ($requestInput as $ind => $item) {
            $id = $item['id'];
            $result[] = SampleRepo::update($item, ['id' => $id]);
        }

        return $result;
    }
//{@block_bu/}

//{@block_br}
    /*
     * 列表筛选 - 模板
     */
    public function sampleIndex(array $requestQuery)
    {
        //业务逻辑
        //{@field_collect
        $fields = ['*'];
        //@field_collect}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = SampleRepo::searchInstance($fields);
        $query->queryMatchIndex($QM);

        //?extend=param 副表扩展查询-用于附加查询条件,不是数据输出.
        $query->scopeExtend($requestQuery);

        //默认排序
        $query->order('update_time', 'desc');

        //dd($query->fetchSql()->select());//
        //$result = $query->pageGet($QM); //特殊查询参数会导致异常,逐渐废弃.
        $result = $query->pageData($QM); //继承框架翻页的稳定版本.
        //$result = $query->pageGetNoTotal($QM); //不合计总数的稳定版本.
        //dd($result['data'][0]->toArray());//

        return $result;
    }
//{@block_br/}

//{@block_r}
    /*
     * 根据 主键id 获取详情 - 模板
     */
    public function sampleRead(array $requestQuery, int $id)
    {
        //业务逻辑
        //{@field_detail
        $fields = ['*'];
        //@field_detail}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = SampleRepo::searchInstance($fields);
        $query->queryMatchRead($QM);

        //默认排序
        $query->order('update_time', 'desc');

        //dd($query->fetchSql()->find());//
        if(!empty($id)){
            $result = $query->find($id);
        }else{
            $result = $query->find();
        }
        //dd($result->toArray());//

        return $result;
    }
//{@block_r/}

//{@block_d}
    /*
     * 根据 主键id 删除详情 - 模板
     */
    public function sampleDelete($id)
    {
        //业务逻辑
        $query = SampleRepo::newInstance();
        $query->isIdExist($id);

        //软删除数据
        $result = (bool) SampleRepo::destroy($id);

        //软删除数据 - 删除字段是整型的情况
        //$result = SampleRepo::update(['is_deleted'=>1],['id'=>$id]);

        //恢复软删除数据
        //$demo = SampleRepo::onlyTrashed()->find(3);
        //$demo->restore();

        return $result;
    }
//{@block_d/}

}