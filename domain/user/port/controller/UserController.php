<?php

namespace domain\user\port\controller;

use domain\base\controller\BaseController;
use domain\base\middleware\Auth;
use domain\base\response\ApiTrans;
use domain\user\port\logic\UserLogic;
use domain\user\port\request\UserRequest;
use domain\user\port\trans\UserTrans;
use extend\utils\ApiCache;
use think\Request;

class UserController extends BaseController
{

    /*
     * 用户-新增-注册
     */
    public function userRegister()
    {
        //输入逻辑控制
        $requestInput = request()->param();
        $validate = new UserRequest();
        $validate->checkSceneValidate('register', $requestInput);

        //业务逻辑控制
        $Logic = new UserLogic();
        $result = $Logic->userRegister($requestInput);

        //输出逻辑控制
        $result = ApiTrans::save($result);

        return ApiTrans::response($result);
    }

    /*
     * 用户-更新-登录
     */
    public function userLogin()
    {
        //输入逻辑控制
        $rules = ['id'];
        $requestInput = request()->except($rules);
        $validate = new UserRequest();
        $validate->checkSceneValidate('login', $requestInput);

        //业务逻辑控制
        $Logic = new UserLogic();
        $result = $Logic->userLogin($requestInput['mobile'], $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }

    /*
     * 用户-获取-自己的详情
     */
    public function userMeRead(Request $request)
    {
        $userId = Auth::userId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        $queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery, $userId) {

            //业务逻辑控制
            $Logic  = new UserLogic();
            $result = $Logic->userMeRead($userId);

            //输出逻辑控制
            return ApiTrans::read($result, UserTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }


    /*
     * 用户-更新-自己的详情
     */
    public function userMeUpdate()
    {
        $userId = Auth::userId();

        //输入逻辑控制
        $rules = ['nick_name', 'avatar', 'sex'];
        $requestInput = request()->only($rules);
        $validate = new UserRequest();
        $validate->checkValidate($requestInput);

        //业务逻辑控制
        $Logic = new UserLogic();
        $result = $Logic->userMeUpdate($userId, $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }

    /*
     * 管理员-获取-用户列表
     */
    public function userAdmIndex(Request $request)
    {
        //$adminId = Auth::adminId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery) {

            //业务逻辑控制
            $result = (new UserLogic())->userAdmIndex($requestQuery);

            //输出逻辑控制
            return ApiTrans::index($result, UserTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }


    /*
     * 管理员-获取-用户详情
     */
    public function userAdmRead(Request $request, $id)
    {
        //$adminId = Auth::adminId();

        //query string
        $requestQuery = $request->get();

        //api查询缓存
        $hKey     = ApiCache::makeHKeyByClassMethod(__CLASS__ . '@' . __FUNCTION__);
        $queryKey = ApiCache::makeQueryKeyByRequest($requestQuery);
        //$queryKey.='&user_id='.$userId;
        $result = (new ApiCache)->collect(
            $hKey, $queryKey, function () use ($requestQuery, $id) {

            //业务逻辑控制
            $logic  = new UserLogic();
            $result = $logic->userAdmRead($requestQuery, $id);

            //输出逻辑控制
            return ApiTrans::read($result, UserTrans::class, 'transform');

        }, -1);

        return ApiTrans::response($result);
    }


    /*
     * 管理员-更新-用户详情
     */
    public function userAdmUpdate($id)
    {
        //$adminId = Auth::adminId();

        //输入逻辑控制
        $rules = ['nick_name', 'avatar', 'sex', 'role', 'status'];
        $requestInput = request()->only($rules);
        $validate = new UserRequest();
        $validate->checkValidate($requestInput);

        //业务逻辑控制
        $logic  = new UserLogic();
        $result = $logic->userAdmUpdate($id, $requestInput);

        //输出逻辑控制
        $result = ApiTrans::update($result);

        return ApiTrans::response($result);
    }


    /*
     * 系统-删除-用户详情
     */
    public function userSysDelete($id)
    {
        //$adminId = Auth::adminId();

        //业务逻辑控制
        $Logic = new UserLogic();
        $result = $Logic->userSysDelete($id);

        $result = ApiTrans::delete($result);

        return ApiTrans::response($result);
    }


}