<?php

use think\migration\Seeder;

class OauthClientsSeed extends Seeder
{

    function createSecret($length = 8){
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function run()
    {
        $table = $this->table('oauth_clients');
        //$table->reset();//清空

        $data = [
            ['client' => 'H5端', 'client_id' => 'h5_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'user_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['client' => '微信端', 'client_id' => 'wechat_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'user_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['client' => '后台管理端', 'client_id' => 'admin_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'admin_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['client' => '后台系统管理端', 'client_id' => 'system_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'system_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['client' => '安卓端', 'client_id' => 'android_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'user_auth', 'create_time' => date('Y-m-d H:i:s') ],
            ['client' => 'IOS端', 'client_id' => 'ios_client', 'client_secret' => $this->createSecret(64),
                'scope_id' => 'user_auth', 'create_time' => date('Y-m-d H:i:s') ],
        ];


        $ids = array_column($data,'client_id');
        //获取旧数据 - 去重
        $rows = $this->oldDataExist('oauth_clients','client_id',$ids);
        foreach ($data as $ind=>$column ){
            $value = $column['client_id'];
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