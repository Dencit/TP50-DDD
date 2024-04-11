<?php

namespace domain\base\middleware;

use domain\base\error\BaseError;
use domain\base\exception\Exception;

class ApiAuth extends BaseAuth
{

    public function handle($request, \Closure $next, $scopes = []){
        $scopes = explode(',',$scopes);

        //检查授权
        $auth = $this->apiAuth();
        $scopeId = $auth->scope_id;
        $request->auth = $auth;
        
        if( !in_array($scopeId,$scopes) ){
            Exception::app(BaseError::code('AUTH_SCOPE_FAIL'),BaseError::msg('AUTH_SCOPE_FAIL'));
        }

        $response = $next($request);
        return $response;
    }


}