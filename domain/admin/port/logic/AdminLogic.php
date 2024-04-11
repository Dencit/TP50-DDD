<?php

namespace domain\admin\port\logic;

use domain\admin\error\AdminRootError;
use domain\admin\repository\AdminRepo;
use domain\base\exception\Exception;
use domain\base\logic\BaseLogic;
use domain\oauth\repository\OauthClientRepo;
use domain\oauth\repository\OauthRoleRepo;
use domain\oauth\srv\OauthTokenSrv;
use domain\user\repository\UserRepo;
use extend\utils\QueryMatch;
use think\Db;

/**
 * notes: 应用层-业务类
 * 说明: 业务类数据操作,一般不直接调用模型,通过仓储类提供存粹的数据执行函数, 跨 应用端/模块 操作同一数据类型的业务, 建议抽象到 领域层-业务类, 减少冗余.
 * 调用原则: 向下调用[仓储类,领域层-业务类]
 */
class AdminLogic extends BaseLogic
{

    /*
     * 根据 主键id 更新详情
     */
    public function adminLogin($mobile, &$requestInput)
    {

        //检查权限
        $scopeId  = $requestInput['scope_id'];
        $clientId = $requestInput['client_id'];

        $OauthClientRepo = OauthClientRepo::searchInstance();
        $oauthClient     = $OauthClientRepo->isOauthClientExist($scopeId, $clientId);
        $clientSecret    = $oauthClient->client_secret;

        //业务逻辑
        $AdminRepo = AdminRepo::searchInstance();
        $admin     = $AdminRepo->isMobileExist($mobile);
        $adminId   = $admin->id;
        $adminRole = $admin->role;
        $userId    = $admin->user_id;

        //验证密码
        $pw = md5($requestInput['pass_word']);
        unset($requestInput['pass_word']);
        if ($admin->pass_word != $pw) {
            Exception::app(AdminRootError::code("PASS_WORD_WRONG"), AdminRootError::msg("PASS_WORD_WRONG"), __METHOD__);
        }

        $now                          = date("Y-m-d H:i:s", time());
        $requestInput['on_line_time'] = $now;

        //自动事务函数
        $result = Db::transaction(function () use ($adminId, $adminRole, $userId, $scopeId, $clientId, $clientSecret, $requestInput) {

            //更新用户登录
            $result = AdminRepo::update($requestInput, ['id' => $adminId]);

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
                $OauthTokenSrv   = new OauthTokenSrv();
                $oauthToken      = $OauthTokenSrv->oauthTokenCreateByAdmin($adminId, $adminRole, $userId, $oauthInput, $extData);
                $result->id      = $adminId;
                $result->role    = $adminRole;
                $result->user_id = $userId;
                $result->auth    = $oauthToken;
                return $result;
            }

            return false;
        });

        return $result;

    }

    /*
     * 根据 主键id 获取详情
     */
    public function adminMeRead($id)
    {
        $AdminRepo = AdminRepo::searchInstance();
        $result    = $AdminRepo->isIdHave($id);
        return $result;
    }

    public function adminMeUpdate($id, &$requestInput)
    {
        //业务逻辑
        $AdminRepo = AdminRepo::searchInstance();
        $AdminRepo->isIdExist($id);

        $result = AdminRepo::update($requestInput, ['id' => $id]);
        return $result;
    }


    /*
     * 系统-新增-管理员
     */
    public function adminSysSave(&$requestInput)
    {

        $AdminRepo = AdminRepo::searchInstance();
        $AdminRepo->isMobileUnique($requestInput['mobile']);

        $requestInput['pass_word']     = md5($requestInput['pass_word']);
        $requestInput['client_driver'] = '';

        //业务逻辑
        $result = AdminRepo::create($requestInput);

        return $result;
    }


    /*
     * 系统-获取-管理员列表
     */
    public function adminSysIndex(array $requestQuery)
    {
        //业务逻辑
        //{@field_collect
        $fields = ['*'];
        //@field_collect}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = AdminRepo::searchInstance($fields);
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


    public function adminSysRead(array $requestQuery, int $id)
    {
        //业务逻辑
        //{@field_detail
        $fields = ['*'];
        //@field_detail}

        //主表筛选逻辑-获取query查询表达式参数
        $QM = QueryMatch::instance($requestQuery);

        $query = AdminRepo::searchInstance($fields);
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
     * 系统-更新-管理员详情
     */
    public function adminSysUpdate($id, &$requestInput)
    {
        //业务逻辑
        $AdminRepo = AdminRepo::searchInstance();
        $AdminRepo->isIdExist($id);

        //检查用户
        if (isset($requestInput['user_id']) && $requestInput['user_id'] != 0) {
            $UserRepo = UserRepo::searchInstance();
            $UserRepo->isIdExist($requestInput['user_id']);
        }
        //检查角色
        if (isset($requestInput['role'])) {
            $OauthRoleRepo = OauthRoleRepo::searchInstance();
            $OauthRoleRepo->isRoleIdExist($requestInput['role']);
        }

        $result = AdminRepo::update($requestInput, ['id' => $id]);

        return $result;
    }

    /*
     * 系统-删除-管理员信息
     */
    public function adminSysDelete($id)
    {
        //业务逻辑
        $query = AdminRepo::newInstance();
        $query->isIdExist($id);

        //软删除数据
        $result = (bool)AdminRepo::destroy($id);

        //软删除数据 - 删除字段是整型的情况
        //$result = AdminRepo::update(['is_deleted'=>1],['id'=>$id]);

        //恢复软删除数据
        //$demo = AdminRepo::onlyTrashed()->find(3);
        //$demo->restore();

        return $result;
    }

}