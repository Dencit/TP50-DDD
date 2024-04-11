<?php

use domain\base\behavior\AdminAuthBehavior;
use domain\base\behavior\SystemAuthBehavior;
use domain\base\behavior\UserAuthBehavior;
use domain\user\port\controller\UserController;
use think\Config;
use think\Route;

//开放权限
Route::group('user', function () {

    //只对测试开放-正式接口不要放这里
    if (Config::get('app_debug')) {

    }

    //用户-新增-注册
    Route::post('/register', UserController::class . '@userRegister');
    //用户-更新-登录
    Route::put('/login', UserController::class . '@userLogin');

}
);


//用户以上权限
Route::group(
    'user', function () {

    //用户-获取-自己的详情
    Route::get('/me', UserController::class . '@userMeRead');
    //用户-更新-自己的详情
    Route::put('/me', UserController::class . '@userMeUpdate');

}, ['after_behavior' => UserAuthBehavior::class]
);


//管理以上权限
Route::group(
    'user', function () {

    //管理员-获取-用户列表
    Route::get('/adm-list', UserController::class . '@userAdmIndex');

    //管理员-获取-用户详情
    Route::get('/adm/:id', UserController::class . '@userAdmRead', [], ['id' => '\d+']);

    //管理员-更新-用户详情
    Route::put('/adm/:id', UserController::class . '@userAdmUpdate', [], ['id' => '\d+']);


}, ['after_behavior' => AdminAuthBehavior::class]
);


//系统以上权限
Route::group(
    'user', function () {

    //系统-删除-用户详情
    Route::delete('/sys/:id', UserController::class . '@userSysDelete', [], ['id' => '\d+']);

}, ['after_behavior' => SystemAuthBehavior::class]
);