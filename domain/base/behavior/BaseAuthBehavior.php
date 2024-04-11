<?php

namespace domain\base\behavior;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use extend\utils\JsonWebToken;

class BaseAuthBehavior{


    //检查授权
    public function apiAuth(){ $user = null;

        $header =request()->header();
        if( !isset($header['token']) ){
            Exception::app(BaseError::code('TOKEN_MUST'),BaseError::msg('TOKEN_MUST'),__METHOD__);
        }

        $token = $header['token'];
        $user = JsonWebToken::checkToken($token);

        return $user;
    }

    //检查角色
    public function userRole($role){

        switch ($role){
            default : Exception::app(BaseError::code('USER_ROLE_FAIL'),BaseError::msg('USER_ROLE_FAIL'),__METHOD__);
                break;
            case 'user':

                break;
            case 'adm':

                break;
            case 'sys':

                break;
        }

    }

}