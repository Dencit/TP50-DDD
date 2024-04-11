<?php

namespace extend\utils;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use think\Cache;

class  JsonWebToken{

    /*protected $clientId;
    protected $clientSecret;
    protected $clientScope;

    public function __construct(){

    }*/

    static function signToken( &$data ){
        //$userId = $data['user_id'];
        $scopeId = $data['scope_id']; //unset($data['client_id']);
        $clientId = $data['client_id']; //unset($data['client_id']);
        $clientSecret = $data['client_secret']; unset($data['client_secret']); //加密中常用的 盐salt
        $exp_time = $data['exp_time'];

        $option=[
            "iss"=>$clientId,   //签发者 可以为空
            "aud"=>$scopeId, //面象的用户，可以为空
            "iat"=>time(),      //签发时间
            "nbf"=>time()+0, //在什么时候jwt开始生效（这里表示生成100秒后才生效）
            "exp"=>$exp_time, //token 过期时间
            "data"=>$data
        ];
        //  print_r($token);
        $token = JWT::encode( $option, $clientSecret, "HS256" );  //根据参数生成了 token

        return $token;
    }

    //验证token
    static function checkToken($token){
        $ApiCacheRedis = Cache::store('redis')->handler();
        $ApiCacheRedis->select(0);
        $clientSecret = $ApiCacheRedis->get( $token );
        if( !$clientSecret ){
            Exception::app(BaseError::code('TOKEN_FAIL'),BaseError::msg('TOKEN_FAIL'),__METHOD__);
        }

        $status=["code"=>2];
        try {
            //JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, $clientSecret, ['HS256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;
            $res['code']=1;
            $res['data']= $arr['data'];
            return $res['data'];
        } catch(SignatureInvalidException $e) { //签名不正确
            $status['msg']="签名不正确";
            return $status;
        }catch(BeforeValidException $e) { // 签名在某个时间点之后才能用
            $status['msg']="token失效";
            return $status;
        }catch(ExpiredException $e) { // token过期
            $status['msg']="token失效";
            return $status;
        }catch(Exception $e) { //其他错误
            $status['msg']="未知错误";
            return $status;
        }
    }

}