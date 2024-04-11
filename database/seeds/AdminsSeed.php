<?php

use think\migration\Seeder;

class AdminsSeed extends Seeder
{

    public function run(){

        $table = $this->table('admins');
        $table->reset();//清空

        $data = [
            ['id' => 1, 'user_id' => 0, 'role' => 'system',
                'name' => '超级管理员', 'mobile' => '18500010002', 'pass_word' => 'e10adc3949ba59abbe56e057f20f883e',
                'client_driver' => 'none', 'status' => 2, 'create_time' => date('Y-m-d H:i:s')],
        ];

        $ids = array_column($data,'id');
        //获取旧数据 - 去重
        $rows = $this->oldDataExist('admins','id',$ids);
        foreach ($data as $ind=>$column ){
            $value = $column['id'];
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