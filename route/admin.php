<?php

use domain\admin\port\controller\AdminController;
use domain\base\behavior\AdminAuthBehavior;
use domain\base\behavior\SystemAuthBehavior;
use domain\base\behavior\UserAuthBehavior;
use think\Config;
use think\Route;

//开放权限
Route::group('admin', function () {

    //只对测试开放-正式接口不要放这里
    if( Config::get('app_debug') ) {
        //-新增-异步
        //Route::post('/job', AdminController::class . '@adminJobSave');
    }

    Route::put('/login', AdminController::class . '@adminLogin');

});


//用户以上权限
Route::group('admin', function () {


}, ['after_behavior'=> UserAuthBehavior::class] );


//管理以上权限
Route::group('admin', function () {

    //管理-获取-自己的信息
    Route::get('/me', AdminController::class . '@adminMeRead');
    //管理员-更新-自己的详情
    Route::put('/me', AdminController::class . '@adminMeUpdate');

}, ['after_behavior'=> AdminAuthBehavior::class] );


//系统以上权限
Route::group('admin', function () {

    //系统-新增-管理员
    Route::post('/sys', AdminController::class . '@adminSysSave');
    //系统-获取-管理员列表
    Route::get('/sys-list', AdminController::class . '@adminSysIndex');
    //系统-获取-管理员详情
    Route::get('/sys/:id', AdminController::class . '@adminSysRead',[],['id'=>'\d+']);
    //系统-更新-管理员详情
    Route::put('/sys/:id', AdminController::class . '@adminSysUpdate',[],['id'=>'\d+']);
    //系统-删除-管理员信息
    Route::delete('/sys/:id', AdminController::class . '@adminSysDelete',[],['id'=>'\d+']);

}, ['after_behavior'=> SystemAuthBehavior::class] );