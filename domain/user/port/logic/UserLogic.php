<?php

namespace domain\user\port\logic;

use domain\base\exception\Exception;
use domain\base\logic\BaseLogic;
use domain\oauth\repository\OauthClientRepo;
use domain\oauth\repository\OauthRoleRepo;
use domain\oauth\srv\OauthTokenSrv;
use domain\user\error\UserRootError;
use domain\user\repository\UserRepo;
use extend\utils\QueryMatch;
use think\Db;

//{@hidden

//@hidden}

/**
 * notes: 应用层-业务类
 * 说明: 业务类数据操作,一般不直接调用模型,通过仓储类提供存粹的数据执行函数, 跨 应用端/模块 操作同一数据类型的业务, 建议抽象到 领域层-业务类, 减少冗余.
 * 调用原则: 向下调用[仓储类,领域层-业务类]
 */
class UserLogic extends BaseLogic
{
    /*
     * 用户-新增-注册
     */
    public function userRegister(&$requestInput)
    {
        $UserRepo = UserRepo::searchInstance();
        $UserRepo->isMobileUnique($requestInput['mobile']);

        $requestInput['pass_word'] = md5($requestInput['pass_word']);

        //业务逻辑
        $result = UserRepo::create($requestInput);

        return $result;
    }


    /*
     * 用户-更新-登录
     */
    public function userLogin($mobile, &$requestInput)
    {
        //检查权限
        $scopeId  = $requestInput['scope_id'];
        $clientId = $requestInput['client_id'];

        $OauthClientRepo = OauthClientRepo::searchInstance();
        $oauthClient     = $OauthClientRepo->isOauthClientExist($scopeId, $clientId);
        $clientSecret    = $oauthClient->client_secret;

        //业务逻辑
        $UserRepo = UserRepo::searchInstance();
        $user     = $UserRepo->isMobileExist($mobile);
        $userId   = $user->id;
        $userRole = $user->role;

        //验证密码
        $pw = md5($requestInput['pass_word']);
        unset($requestInput['pass_word']);
        if ($user->pass_word != $pw) {
            Exception::app(UserRootError::code("PASS_WORD_WRONG"), UserRootError::msg("PASS_WORD_WRONG"), __METHOD__);
        }

        $now                          = date("Y-m-d H:i:s", time());
        $requestInput['on_line_time'] = $now;

        //自动事务函数
        $result = Db::transaction(function () use ($userId, $userRole, $scopeId, $clientId, $clientSecret, $requestInput) {

            //更新用户登录
            $result = UserRepo::update($requestInput, ['id' => $userId]);

            $extData = [
                'mobile' => $requestInput['mobile'],
            ];

            //生成 access_token ;
            if ($result) {

                $oauthInput = [
                    'scope_id' => $scopeId, 'client_id' => $clientId, 'client_secret' => $clientSecret,
                    'expire'   => 7200,
                ];
                //记录token
                $OauthTokenSrv = new OauthTokenSrv();
                $oauthToken    = $OauthTokenSrv->oauthTokenCreateByUser($userId, $userRole, $oauthInput, $extData);
                $result->id    = $userId;
                $result->role  = $userRole;
                $result->auth  = $oauthToken;

                return $result;
            }

            return false;
        });

        return $result;
    }


    /*
     * 用户-获取-自己的详情
     */
    public function userMeRead($userId)
    {
        $UserRepo = UserRepo::searchInstance();
        $result   = $UserRepo->isUserIdHave($userId);
        return $result;
    }


    /*
     * 用户-更新-自己的详情
     */
    public function userMeUpdate($id, &$requestInput)
    {
        $UserRepo = UserRepo::searchInstance();
        $UserRepo->isIdExist($id);

        $result = UserRepo::update($requestInput, ['id' => $id]);
        return $result;
    }


    /*
     * 管理员-获取-用户列表
     */
    public function userAdmIndex(array $requestQuery)
    {
        //业务逻辑
        //{@field_collect
        $fields = ['*'];
        //@field_collect}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = UserRepo::searchInstance($fields);
        $query->queryMatchIndex($QM);

        //?extend=param 副表扩展查询-用于附加查询条件,不是数据输出.
        $query->scopeExtend($requestQuery);

        //默认排序
        $query->order('update_time', 'desc');

        //dd($query->fetchSql()->select());//
        //$result = $query->pageGet($QM); //特殊查询参数会导致异常,逐渐废弃.
        $result = $query->pageData($QM); //继承框架翻页的稳定版本.
        //$result = $query->pageGetNoTotal($QM); //不合计总数的稳定版本.
        //dd($result['data'][0]->toArray());//

        return $result;
    }


    /*
     * 管理员-获取-用户详情
     */
    public function userAdmRead(array $requestQuery, int $id)
    {
        //业务逻辑
        //{@field_detail
        $fields = ['*'];
        //@field_detail}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = UserRepo::searchInstance($fields);
        $query->queryMatchRead($QM);

        //默认排序
        $query->order('update_time', 'desc');

        //dd($query->fetchSql()->find());//
        if(!empty($id)){
            $result = $query->find($id);
        }else{
            $result = $query->find();
        }
        //dd($result->toArray());//

        return $result;
    }


    /*
     * 管理员-更新-用户详情
     */
    public function userAdmUpdate($id, &$requestInput)
    {
        $UserRepo = UserRepo::searchInstance();
        $UserRepo->isIdExist($id);

        //检查角色
        if (isset($requestInput['role'])) {
            $OauthRoleRepo = OauthRoleRepo::searchInstance();
            $OauthRoleRepo->isRoleIdExist($requestInput['role']);
        }

        $result = UserRepo::update($requestInput, ['id' => $id]);

        return $result;
    }


    /*
     * 系统-删除-用户详情
     */
    public function userSysDelete($id)
    {
        $UserRepo = UserRepo::searchInstance();
        $UserRepo->isIdExist($id);

        //软删除数据
        $result = (bool)UserRepo::destroy($id);

        //软删除数据 - 删除字段是整型的情况
        //$result = UserRepo::update(['is_deleted'=>1],['id'=>$id]);

        //恢复软删除数据
        //$demo = UserRepo::onlyTrashed()->find(3);
        //$demo->restore();

        return $result;
    }

}