<?php
/**
 * note: 数据迁移
 * doc: https://book.cakephp.org/
 */
use think\migration\Migrator;
use Phinx\Db\Adapter\MysqlAdapter;

class Samples extends Migrator
{

    public function up(){
        parent::up();

        // create the table
        $table = $this->table('samples',[
            'id'        => 'id',
            'engine'    => 'InnoDb',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment'   => '模板表'
        ]);

        $table
            ->addColumn('name','string',['limit'=>50,'default'=>'','comment'=>'用户昵称'])
            ->addColumn('mobile','string',['limit'=>30,'default'=>'','comment'=>'绑定手机'])
            ->addColumn('photo','string',['limit'=>200,'default'=>'','comment'=>'用户头像'])
            ->addColumn('sex','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>'0','comment'=>'性别: 0未知, 1男, 2女'])
            ->addColumn('type','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'类型: 0未知, 1-否, 2-是'])
            ->addColumn('status','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'状态: 1-否, 2-是'])
            ->addColumn('create_time','datetime',[ 'comment'=>'创建时间' ])
            ->addColumn('update_time','datetime',[ 'comment'=>'更新时间','null'=>true ])
            ->addColumn('delete_time','datetime',[ 'comment'=>'删除时间','null'=>true ])

            ->addIndex(['id'])
            ->addIndex(['type'])
            ->addIndex(['status'])
            
            ->create();

        $table
            ->changeColumn('id','integer',['identity'=>true,'signed'=>false,'limit'=>MysqlAdapter::INT_BIG,'comment'=>'主键ID'])
            ->update();

    }

    public function down(){
        parent::down();

        $this->table('samples')->drop();
    }

}
