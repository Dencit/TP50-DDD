<?php

namespace domain\oauth\srv;

use domain\oauth\model\OauthTokenModel;
use domain\oauth\repository\OauthTokenRepo;
use extend\utils\JsonWebToken;
use think\Cache;

/**
 * notes: 领域层-业务类
 * desc: 当不同 应用端/模块 的 应用层-业务类,对同一个表数据(或第三方API)进行操作, 该表的操作代码分散在多个应用端中且冗余, 就需要抽象到这一层.
 * 领域层-业务类 允许 被 跨应用端/模块 调用, 而 各应用层-业务 则保持隔离, 避免应用层业务耦合.
 * 调用原则: 向下调用[仓储类,第三方服务-SDK]
 */
class OauthTokenSrv
{
    /*
     * 新增数据 by user
     */
    public function oauthTokenCreateByUser($userId, $userRole, &$oauthInput, $extData = [])
    {

        $scopeId      = $oauthInput['scope_id'];
        $clientId     = $oauthInput['client_id'];
        $clientSecret = $oauthInput['client_secret'];
        $expire       = $oauthInput['expire'] ?? 7200;
        $userMark     = $scopeId . '_' . $userId;

        $nowTime       = time();
        $expTime       = $nowTime + $expire;
        $startDateTime = date('Y-m-d H:i:s', $nowTime);
        $expDateTime   = date('Y-m-d H:i:s', $expTime);

        //是否存在没过期的token
        $OauthTokenRepo = OauthTokenRepo::searchInstance();
        $OauthToken     = $OauthTokenRepo->isClientTokenHave($userMark, $scopeId, $clientId);
        if (!$OauthToken) {

            //生成token必须的数据结构
            $data = ['user_mark' => $userMark, 'user_id' => $userId, 'role' => $userRole,
                     'scope_id'  => $scopeId, 'client_id' => $clientId, 'client_secret' => $clientSecret,
                     'exp_time'  => $expTime, 'exp_date' => $expDateTime,
            ];
            //扩展数据
            $data  = array_merge($data, $extData);
            $token = JsonWebToken::signToken($data);
            //
            $ApiCacheRedis = Cache::store('redis')->handler();
            $ApiCacheRedis->select(0);
            $ApiCacheRedis->set($token, $clientSecret, $expire);

            //token 表数据
            $oauthInput['user_mark']   = $userMark;
            $oauthInput['token']       = $token;
            $oauthInput['start_time']  = $startDateTime;
            $oauthInput['expire_time'] = $expDateTime;
            $OauthToken                = OauthTokenRepo::create($oauthInput);

        } else {
            $ApiCacheRedis = Cache::store('redis')->handler();
            $ApiCacheRedis->select(0);
            $oldExpTime = strtotime($OauthToken->expire_time);
            $expTime    = $oldExpTime - $nowTime;
            $ApiCacheRedis->set($OauthToken->token, $OauthToken->client_secret, $expTime);
        }

        $OauthToken->user_id = $userId;
        $OauthToken->role    = $userRole;
        $OauthToken          = $OauthToken->toArray();
        unset($OauthToken['client_secret']);
        //扩展数据
        $OauthToken = array_merge($OauthToken, $extData);

        return $OauthToken;
    }


    /*
     * 新增数据 by admin
     */
    public function oauthTokenCreateByAdmin($adminId, $adminRole, $userId, &$oauthInput, $extData = [])
    {

        $scopeId      = $oauthInput['scope_id'];
        $clientId     = $oauthInput['client_id'];
        $clientSecret = $oauthInput['client_secret'];
        $expire       = $oauthInput['expire'] ?? 7200;
        $userMark     = $scopeId . '_' . $adminId;

        $nowTime       = time();
        $expTime       = $nowTime + $expire;
        $startDateTime = date('Y-m-d H:i:s', $nowTime);
        $expDateTime   = date('Y-m-d H:i:s', $expTime);

        //是否存在没过期的token
        $OauthTokenRepo = OauthTokenRepo::searchInstance();
        $OauthToken     = $OauthTokenRepo->isClientTokenHave($userMark, $scopeId, $clientId);
        if (!$OauthToken) {

            //生成token必须的数据结构
            $data = ['admin_id' => $adminId, 'role' => $adminRole, 'user_id' => $userId,
                     'scope_id' => $scopeId, 'client_id' => $clientId, 'client_secret' => $clientSecret,
                     'exp_time' => $expTime, 'exp_date' => $expDateTime,
            ];
            //扩展数据
            $data  = array_merge($data, $extData);
            $token = JsonWebToken::signToken($data);
            //
            $ApiCacheRedis = Cache::store('redis')->handler();
            $ApiCacheRedis->select(0);
            $ApiCacheRedis->set($token, $clientSecret, $expire);

            $oauthInput['user_mark']   = $userMark;
            $oauthInput['token']       = $token;
            $oauthInput['start_time']  = $startDateTime;
            $oauthInput['expire_time'] = $expDateTime;
            $OauthToken                = OauthTokenRepo::create($oauthInput);
        } else {
            $ApiCacheRedis = Cache::store('redis')->handler();
            $ApiCacheRedis->select(0);
            $oldExpTime = strtotime($OauthToken->expire_time);
            $expTime    = $oldExpTime - $nowTime;
            $ApiCacheRedis->set($OauthToken->token, $OauthToken->client_secret, $expTime);
        }

        $OauthToken->admin_id = $adminId;
        $OauthToken->role     = $adminRole;
        $OauthToken->user_id  = $userId;
        $OauthToken           = $OauthToken->toArray();
        unset($OauthToken['client_secret']);
        //扩展数据
        $OauthToken = array_merge($OauthToken, $extData);

        return $OauthToken;

    }

}