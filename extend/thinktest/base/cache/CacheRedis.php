<?php
namespace extend\thinktest\base\cache;

use think\Cache;

class CacheRedis
{
    protected $Redis;
    protected $sign;

    public function __construct($table_num=0)
    {
        $this->sign = $this->hashSign(0);
        $this->Redis = Cache::store('redis')->handler();

        if( $table_num==0 ){
            return $this->Redis;
        }else{
            //控制器数据缓存 统一放 第2个库
            return $this->Redis->select($table_num);
        }
    }

    public function getData($key){
        if(request()->get('_time')==1){
            return false;
        }else{
            $key=config("cache.stores.redis.prefix").$key.':';

            return $this->Redis->get($key);
        }
    }
    public function getDataByMineKey($key,$mine=null){
        if(request()->get('_time')==1){
            return false;
        }else{
            $key=config("cache.stores.redis.prefix").$key.':'.$this->mineKey($mine);
            return $this->Redis->get($key);
        }
    }


    public function setData($key,$value,$expire=null){
        $key=config("cache.stores.redis.prefix").$key.':';

        if( $expire ){ return $this->Redis->set($key,$value,$expire); }
        return $this->Redis->set($key,$value);
    }
    public function setDataByMineKey($key,$value,$mine=null,$expire=null){
        $key=config("cache.stores.redis.prefix").$key.':'.$this->mineKey($mine);

        if( $expire ){ return $this->Redis->set($key,$value,$expire); }
        return $this->Redis->set($key,$value);
    }


    public function delData($key){
        $key=config("cache.stores.redis.prefix").$key.':';
        return $this->Redis->del($key);
    }
    public function delDataByMineKey($key,$mine=null){
        $key=config("cache.stores.redis.prefix").$key.':'.$this->mineKey($mine);
        return $this->Redis->del($key);
    }

    public function mineKey($key=null){
        if( $key!=null ){
            return 'mine_key_'.$key;
        }else{
            return '';
        }
    }
    public function hashSign($type=null){
        $normalData =  request()->get();
        unset($normalData['_time']); //排除实时字段
        $queryStr=""; //待拼接字符串
        ksort($normalData);
        foreach ($normalData as $k=>$v){
            if( !empty($v) ){ $queryStr.= $k."=".$v."&";}
        }
        $queryStr = trim($queryStr,"&");
        if($type){
            $newShaSign = hash("sha256", $queryStr);
        }else{
            $newShaSign = $queryStr;
        }
        return $newShaSign;
    }

}