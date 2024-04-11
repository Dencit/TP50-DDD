<?php

namespace domain\demo\port\controller;

use domain\base\controller\BaseController;
use domain\base\middleware\Auth;
use domain\base\response\ApiTrans;
use domain\demo\port\logic\SampleLogic;
use domain\demo\port\request\SampleRequest;
use domain\demo\port\trans\SampleTrans;
use extend\log\backTrace;
use extend\utils\ApiCache;
use think\Request;

/**
 * notes: 应用层-控制器
 * 说明: 控制器内不写业务,只写http层面相关的逻辑,
 * 调用原则: 向下调用[输入验证类,业务类,输出转化类].
 */
class SampleController extends BaseController
{

//{@block_c}
    /*
     * 新增数据 - 模板
     */
    public function sampleSave()
    {
        //输入逻辑控制
        $requestInput = request()->param();
        $validate     = new SampleRequest();
        $validate->checkSceneValidate('save', $requestInput);

        //$userId = Auth::userId();

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleSave($requestInput);

        //输出逻辑控制
        $result = ApiTrans::save($result);

        return ApiTrans::response($result);
    }
//{@block_c/}

//{@block_cj}
    /*
     * 队列新增数据 - 模板
     */
    public function sampleJobSave()
    {
        //$userId = Auth::userId();

        //输入逻辑控制
        $requestInput = request()->post();
        $validate     = new SampleRequest();
        $validate->checkSceneValidate('save', $requestInput);

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleJobSave($requestInput);

        //输出逻辑控制
        $result = ApiTrans::save($result);

        return ApiTrans::response($result);
    }
//{@block_cj/}

//{@block_bc}
    /*
     * 批量新增数据 - 模板
     */
    public function sampleBatchSave()
    {
        //输入逻辑控制
        $requestInput = request()->param();
        $rules        = [];
        $validate     = new SampleRequest();
        $this->batchDone(
            $requestInput, function ($item) use ($rules, $validate) {
            $this->arrayExcept($item, $rules);//数组排除输入字段
            $validate->checkSceneValidate('save', $item);
        }
        );

        //$userId = Auth::userId();

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleBatchSave($requestInput);

        //输出逻辑控制
        $result = ApiTrans::batchSave($result);

        return ApiTrans::response($result);
    }
//{@block_bc/}

//{@block_u}
    /*
     * 根据 主键id 更新详情 - 模板
     */
    public function sampleUpdate($id)
    {
        //输入逻辑控制
        $rules        = [];
        $requestInput = request()->except($rules);
        $validate     = new SampleRequest();
        $validate->checkSceneValidate('update', $requestInput);

        //$userId = Auth::userId();

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleUpdate($id, $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }
//{@block_u/}

//{@block_bu}
    /*
     * 根据 主键id 批量更新 - 模板
     */
    public function sampleBatchUpdate($id)
    {
        //输入逻辑控制
        $requestInput = request()->param();
        $rules        = [];
        $validate     = new SampleRequest();
        $this->batchDone(
            $requestInput, function ($item) use ($rules, $validate) {
            $this->arrayExcept($item, $rules);//数组排除输入字段
            $validate->checkSceneValidate('update', $item);
        }
        );

        //$userId = Auth::userId();

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleBatchUpdate($requestInput);

        //输出逻辑控制
        $result = ApiTrans::batchUpdate($result);

        return ApiTrans::response($result);
    }
//{@block_bu/}

//{@block_br}
    /*
     * 列表筛选 - 模板
     */
    public function sampleIndex(Request $request)
    {
        //$userId = Auth::userId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery) {

            //业务逻辑控制
            $result = (new SampleLogic())->sampleIndex($requestQuery);

            //输出逻辑控制
            return ApiTrans::index($result, SampleTrans::class, 'transform');

        }, 300);

        return ApiTrans::response($result);
    }
//{@block_br/}

//{@block_r}
    /*
     * 根据 主键id 获取详情 - 模板
     */
    public function sampleRead(Request $request, $id)
    {
        $userId = Auth::userId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery, $id) {

            //业务逻辑控制
            $logic  = new SampleLogic();
            $result = $logic->sampleRead($requestQuery, $id);

            //输出逻辑控制
            return ApiTrans::read($result, SampleTrans::class, 'transform');

        }, 300);

        return ApiTrans::response($result);
    }
//{@block_r/}

//{@block_d}
    /*
     * 根据 主键id 删除详情 - 模板
     */
    public function sampleDelete($id)
    {
        //$userId = Auth::userId();

        //业务逻辑控制
        $logic  = new SampleLogic();
        $result = $logic->sampleDelete($id);

        //输出逻辑控制
        $result = ApiTrans::delete($result);

        return ApiTrans::response($result);
    }
//{@block_d/}

}