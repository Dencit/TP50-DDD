<?php
namespace extend\thinkex;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use think\Exception;

class MongoDB
{
    protected $opt;
    protected $client;
    protected $db;
    protected $collection;

    public function __construct($opt){
        $this->opt=$opt;
        try {
            if(empty($this->client)){
                $this->client = new Client('mongodb://127.0.0.1/',$opt['mongodb']);
            }
        }catch (\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";die();
        }
    }
    public function __destruct(){ $this->client = null;}

    //获取文档集
    public function database($daName){
        $this->db = $this->client->selectDatabase($daName);
        return $this;
    }

    //获取数据集
    public function collection($colName){
        $this->collection = $this->db->selectCollection($colName);
        return $this;
    }

    //获取多个数据
    public function get( $mapArr ){
        $select = $this->collection->find( $mapArr );
        return $select->toArray();
    }

    //获取单个数据
    public function find($mapArr){
        $select = $this->collection->findOne( $mapArr );
        return $select;
    }

    //通过 oid string 查询
    public function findOid($oid){
        //oid string 转 oid object
        $oid = $this->oidTrans($oid);
        //
        $select = $this->collection->findOne( ['_id'=>$oid] );
        if(!empty($select)){
            $result = (array)$select->getArrayCopy();
            $result['_id'] = ((array)$result['_id'])['oid'];
            return $result;
        }
        return null;
    }

    //oid string 转 oid object
    public function oidTrans($strOid){
        $oid = new ObjectId($strOid);
        return $oid;
    }

}