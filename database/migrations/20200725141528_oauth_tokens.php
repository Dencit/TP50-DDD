<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OauthTokens extends Migrator
{

    public function up(){
        parent::up();

        $table = $this->table('oauth_tokens',[ 'engine'=>'InnoDb','charset'=> 'utf8mb4', 'collation'=> 'utf8mb4_general_ci','comment'=>'授权信息表']);

        $table
            ->addColumn('user_mark','string',['limit'=>255,'default'=>'','comment'=>'用户标记: user_1,admin_1'])

            ->addColumn('scope_id','string',['limit'=>255,'default'=>'','comment'=>'授权范围-标记: 字符串'])
            ->addColumn('client_id','string',['limit'=>255,'default'=>'','comment'=>'授权客户端-标记: 字符串'])
            ->addColumn('client_secret','string',['limit'=>255,'default'=>'','comment'=>'授权客户-密匙: 字符串'])

            ->addColumn('token','text',['limit'=>MysqlAdapter::TEXT_REGULAR,'comment'=>'授权信息: 字符串'])
            ->addColumn('refresh_token','text',['limit'=>MysqlAdapter::TEXT_REGULAR, 'null'=>true, 'comment'=>'刷新授权信息: 字符串'])

            ->addColumn('start_time','datetime',[ 'comment'=>'开始时间' ])
            ->addColumn('expire_time','datetime',[ 'comment'=>'过期时间' ])

            ->addColumn('create_time','datetime',[ 'comment'=>'创建时间' ])
            ->addColumn('update_time','datetime',[ 'comment'=>'更新时间','null'=>true ])
            ->addColumn('delete_time','datetime',[ 'comment'=>'删除时间','null'=>true ])

            ->addIndex(['id'])
            ->addIndex(['scope_id'])->addIndex(['client_id'])
            ->addIndex(['scope_id','client_id'])
            ->addIndex(['user_mark'])

            ->create();

        $table
            ->changeColumn('id','integer',['identity'=>true,'signed'=>false,'limit'=>MysqlAdapter::INT_BIG,'comment'=>'主键ID'])
            ->update();

    }

    public function down(){
        parent::down();

        $this->table('oauth_tokens')->drop();
    }

}
