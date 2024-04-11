<?php
/**
 * notes: 全局调用授权信息
 * @author 陈鸿扬 | @date 2022/4/7 12:10
 */

namespace domain\base\middleware;

class Auth
{
    //创建静态私有的变量保存该类对象
    static private $instance;
    //防止使用new直接创建对象
    private function __construct(){}
    //防止使用clone克隆对象
    private function __clone(){}

    //获取全部授权信息
    public static function instance()
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //获取 UserId
    public static function userId(){
        self::instance();
        $userId = 0;
        if(isset(self::$instance->userId)){
            $userId = self::$instance->userId;
        }
        return $userId;
    }

    //获取 $userInfo
    public static function userInfo(){
        self::instance();
        $userInfo = [];
        if(isset(self::$instance->userInfo)){
            $userInfo = self::$instance->userInfo;
        }
        return $userInfo;
    }

    //获取 AdminId
    public static function adminId(){
        self::instance();
        $adminId = 0;
        if(isset(self::$instance->adminId)){
            $adminId = self::$instance->adminId;
        }
        return $adminId;
    }

    //获取companyId
    public static function companyId(){
        self::instance();
        $companyId = 0;
        if(isset(self::$instance->companyId)){
            $companyId = self::$instance->companyId;
        }
        return $companyId;
    }

    //获取type
    public static function clientType(){
        self::instance();
        $type = 0;
        if(isset(self::$instance->type)){
            $type = self::$instance->type;
        }
        return $type;
    }

}