<?php

use think\migration\Seeder;

class OauthRolesSeed extends Seeder
{

    public function run()
    {
        $table = $this->table('oauth_roles');
        //$table->reset();//清空

        $data = [
            ['role' => '用户角色', 'role_id' => 'user', 'create_time' => date('Y-m-d H:i:s') ],
            ['role' => '普通管理员角色', 'role_id' => 'admin', 'create_time' => date('Y-m-d H:i:s') ],
            ['role' => '运营管理员角色', 'role_id' => 'operate', 'create_time' => date('Y-m-d H:i:s') ],
            ['role' => '会计管理员角色', 'role_id' => 'accountant', 'create_time' => date('Y-m-d H:i:s') ],
            ['role' => '系统管理员角色', 'role_id' => 'system', 'create_time' => date('Y-m-d H:i:s') ]
        ];

        $ids = array_column($data,'role_id');
        //获取旧数据 - 去重
        $rows = $this->oldDataExist('oauth_roles','role_id',$ids);
        foreach ($data as $ind=>$column ){
            $value = $column['role_id'];
            if( in_array($value,$rows) ){ unset($data[$ind]); }
        }

        if(!empty($data)){
            $data = array_values($data);
            $table->insert($data)->saveData();
        }

        return true;
    }

    //获取旧数据 - 去重
    protected function oldDataExist($name,$id,$values){
        $valueStr=''; foreach ($values as $k=>$v){ $valueStr.='\''.$v.'\','; }
        $valueStr = trim($valueStr,',');
        //
        $tableName= config('database.prefix').$name;
        $query =
            'SELECT '.$id.' FROM '.$tableName.
            ' where `'.$id.'` In ('.$valueStr.')'
        ;
        //
        $stmt = $this->query($query);
        $rows = $stmt->fetchAll();
        if($rows){ $rows = array_column($rows,$id); return $rows; }
        return [];
    }

}