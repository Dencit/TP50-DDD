<?php

use think\migration\Seeder;

class OauthScopesSeed extends Seeder
{

    public function run()
    {
        $table = $this->table('oauth_scopes');
        //$table->reset();//清空

        $data = [
            ['scope' => '用户端授权-范围', 'scope_id' => 'user_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['scope' => '管理端授权-范围', 'scope_id' => 'admin_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['scope' => '系统端授权-范围', 'scope_id' => 'system_auth', 'create_time' => date('Y-m-d H:i:s') ],
        ];

        $scopeIds = array_column($data,'scope_id');
        //获取旧数据 - 去重
        $rows = $this->oldDataExist('oauth_scopes','scope_id',$scopeIds);
        foreach ($data as $ind=>$column ){
            $value = $column['scope_id'];
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