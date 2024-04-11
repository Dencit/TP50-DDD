<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OauthClients extends Migrator
{

    public function up(){
        parent::up();
        
        $table = $this->table('oauth_clients',[ 'engine'=>'InnoDb','charset'=> 'utf8mb4', 'collation'=> 'utf8mb4_general_ci','comment'=>'授权客户端表']);

        $table
            ->addColumn('scope_id','string',['limit'=>255,'default'=>'','comment'=>'授权范围-标记: 字符串'])
            ->addColumn('client','string',['limit'=>255,'default'=>'','comment'=>'授权客户端-描述: 字符串'])
            ->addColumn('client_id','string',['limit'=>255,'default'=>'','comment'=>'授权客户端-标记: 字符串'])
            ->addColumn('client_secret','string',['limit'=>255,'default'=>'','comment'=>'授权客户端-密匙: 字符串'])

            ->addColumn('create_time','datetime',[ 'comment'=>'创建时间' ])
            ->addColumn('update_time','datetime',[ 'comment'=>'更新时间','null'=>true ])
            ->addColumn('delete_time','datetime',[ 'comment'=>'删除时间','null'=>true ])

            ->addIndex(['id'])
            ->addIndex(['scope_id'])->addIndex(['client_id'])
            ->addIndex(['scope_id','client_id'])

            ->create();

        $table
            ->changeColumn('id','integer',['identity'=>true,'signed'=>false,'limit'=>MysqlAdapter::INT_BIG,'comment'=>'主键ID'])
            ->update();

    }

    public function down(){
        parent::down();

        $this->table('oauth_clients')->drop();
    }

}
