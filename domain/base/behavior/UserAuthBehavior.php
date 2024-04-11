<?php

namespace domain\base\behavior;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use domain\base\middleware\Auth;

/*
 * 用户以上权限
 */
class UserAuthBehavior extends BaseAuthBehavior{

    public function run()
    {
        $scopes = 'user_auth,admin_auth,system_auth';
        $scopes = explode(',',$scopes);

        //检查授权
        $auth = $this->apiAuth();
        $scopeId = $auth->scope_id;

        if( !in_array($scopeId,$scopes) ){
            Exception::app(BaseError::code('AUTH_SCOPE_FAIL'),BaseError::msg('AUTH_SCOPE_FAIL'));
        }

        //建立auth授权数据对象
        $authData = Auth::instance();
        $authData->userId = $auth->user_id;
        $authData->scopeId = $auth->scope_id;

        return true;
    }

}