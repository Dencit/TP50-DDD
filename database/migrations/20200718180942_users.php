<?php

use think\migration\Migrator;
use Phinx\Db\Adapter\MysqlAdapter;

class Users extends Migrator
{

    public function up(){
        parent::up();

        // create the table
        $table = $this->table('users',[ 'engine'=>'InnoDb','charset'=> 'utf8mb4', 'collation'=> 'utf8mb4_general_ci','comment'=>'用户表']);

        $table
            ->addColumn('role','string',['limit'=>255,'default'=>'user','comment'=>'用户角色: user,admin'])
            ->addColumn('nick_name','string',['limit'=>255,'default'=>'','comment'=>'用户昵称'])
            ->addColumn('avatar','string',['limit'=>255,'default'=>'','comment'=>'用户头像'])
            ->addColumn('sex','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'性别: 0未知, 1男, 2女'])
            ->addColumn('mobile','string',['limit'=>30,'default'=>'','comment'=>'绑定手机'])
            ->addColumn('pass_word','string',['limit'=>255,'null'=>true,'default'=>'','comment'=>'密码'])

            ->addColumn('client_driver','text',['limit'=>MysqlAdapter::TEXT_REGULAR,'comment'=>'客户端信息'])
            ->addColumn('client_type','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>' 客户端类型: 0未知, 1-WEB, 2-WEP, 3-APP '])

            ->addColumn('lat','decimal',['precision'=>10,'scale'=>6,'signed'=>true,'default'=>0.0,'comment'=>'坐标:纬度'])
            ->addColumn('lng','decimal',['precision'=>10,'scale'=>6,'signed'=>true,'default'=>0.0,'comment'=>'坐标:经度'])

            ->addColumn('status','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'状态: 0未知, 1-未启用, 2-已启用'])

            ->addColumn('on_line_time','datetime',[ 'comment'=>'登录时间', 'null'=>true])
            ->addColumn('off_line_time','datetime',[ 'comment'=>'登出时间','null'=>true ])
            ->addColumn('create_time','datetime',[ 'comment'=>'创建时间|注册时间' ])

            ->addColumn('update_time','datetime',[ 'comment'=>'更新时间','null'=>true ])
            ->addColumn('delete_time','datetime',[ 'comment'=>'删除时间','null'=>true ])

            ->addIndex(['id'])->addIndex(['role'])
            ->addIndex(['id','role'])
            ->addIndex('client_type')
            ->addIndex(['lat'])->addIndex(['lng'])
            ->addIndex(['lat','lng'])
            ->addIndex(['status'])

            ->create();

        $table
            ->changeColumn('id','integer',['identity'=>true,'signed'=>false,'limit'=>MysqlAdapter::INT_BIG,'comment'=>'主键ID'])
            ->update();

    }

    public function down(){
        parent::down();

        $this->table('users')->drop();
    }

}
