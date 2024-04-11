<?php

namespace domain\admin\port\controller;

use domain\admin\port\logic\AdminLogic;
use domain\admin\port\request\AdminRequest;
use domain\admin\port\trans\AdminTrans;
use domain\base\controller\BaseController;
use domain\base\middleware\Auth;
use domain\base\response\ApiTrans;
use extend\utils\ApiCache;
use think\Request;

class AdminController extends BaseController
{
    /*
     * 管理员登录
     */
    public function adminLogin()
    {
        //输入逻辑控制
        $rules        = ['id'];
        $requestInput = request()->except($rules);
        $validate     = new AdminRequest();
        $validate->checkSceneValidate('login', $requestInput);

        //$userId = $this->auth('user_id');

        //业务逻辑控制
        $Logic  = new AdminLogic();
        $result = $Logic->adminLogin($requestInput['mobile'], $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }


    /*
     * 管理-获取-自己的信息
     */
    public function adminMeRead(Request $request)
    {
        $adminId = Auth::adminId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery, $adminId) {

            //业务逻辑控制
            $logic  = new AdminLogic();
            $result = $logic->adminMeRead($adminId);

            //输出逻辑控制
            return ApiTrans::read($result, AdminTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }


    public function adminMeUpdate()
    {
        $adminId = Auth::adminId();

        //输入逻辑控制
        $rules        = ['name', 'avatar', 'sex'];
        $requestInput = request()->only($rules);
        $validate     = new AdminRequest();
        $validate->checkValidate($requestInput);

        //业务逻辑控制
        $Logic  = new AdminLogic();
        $result = $Logic->adminMeUpdate($adminId, $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }


    /*
     * 系统-新增-管理员
     */
    public function adminSysSave()
    {
        //$adminId = Auth::adminId();

        //输入逻辑控制
        $rules        = ['name', 'mobile', 'pass_word'];
        $requestInput = request()->only($rules);
        $validate     = new AdminRequest();
        $validate->checkSceneValidate('create', $requestInput);

        //业务逻辑控制
        $Logic  = new AdminLogic();
        $result = $Logic->adminSysSave($requestInput);

        //输出逻辑控制
        $result = ApiTrans::save($result);

        return ApiTrans::response($result);
    }

    /*
     * 系统-获取-管理员列表
     */
    public function adminSysIndex(Request $request)
    {
        //$adminId = Auth::adminId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&admin_id='.$adminId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery) {

            //业务逻辑控制
            $result = (new AdminLogic())->adminSysIndex($requestQuery);

            //输出逻辑控制
            return ApiTrans::index($result, AdminTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }

    /*
     * 系统-获取-管理员详情
     */
    public function adminSysRead(Request $request, $id)
    {
        $adminId = Auth::adminId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        $queryKey.='&admin_id='.$adminId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery, $id) {

            //业务逻辑控制
            $logic  = new AdminLogic();
            $result = $logic->adminSysRead($requestQuery, $id);

            //输出逻辑控制
            return ApiTrans::read($result, AdminTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }

    /*
     * 系统-更新-管理员详情
     */
    public function adminSysUpdate($id)
    {
        //输入逻辑控制
        $rules        = ['name', 'avatar', 'sex', 'role', 'status'];
        $requestInput = request()->only($rules);
        $validate     = new AdminRequest();
        $validate->checkValidate($requestInput);

        //$adminId = Auth::adminId();

        //业务逻辑控制
        $Logic  = new AdminLogic();
        $result = $Logic->adminSysUpdate($id, $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }


    /*
     * 系统-删除-管理员信息
     */
    public function adminSysDelete($id)
    {
        //$adminId = Auth::adminId();

        //业务逻辑控制
        $Logic  = new AdminLogic();
        $result = $Logic->adminSysDelete($id);

        //输出逻辑控制
        $result = ApiTrans::delete($result);

        return ApiTrans::response($result);
    }


}