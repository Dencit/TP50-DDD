<?php
namespace extend\thinktest\base\behavior;

use extend\thinktest\base\api\BaseApiTest;
use PHPUnit\Framework\TestResult;
use think\Config;
use think\Db;

abstract class BaseBehavior extends BaseApiTest
{
    protected function tableSaveOrFailAll($tableName,$insertData){ $result= null;
        $updateTime = $this->updateTime();
        if ( !empty($insertData) && count($insertData)>0 ){
            foreach ($insertData as $k=>$v){
                if( !isset($v["update_time"]) ){ $insertData[$k]["update_time"]=$updateTime; }
                else{ $updateTime=$v["update_time"]; }
            }

            Db::name( $tableName )->insertAll($insertData);
            $result = Db::name($tableName)->where('update_time',$updateTime)->select();
        }
        return $result;
    }

    protected function updateTime(){
        $updateTime = date('Y-m-d H:i:s',strtotime( 'now' ));
        return $updateTime;
    }

    protected function betweenTime($startTime,$endTime){
        $betweenTime = date('Y-m-d H:i:s', mt_rand($startTime,$endTime));
        return $betweenTime;
    }
}