<?php
namespace extend\thinkex;

use PDO;
use PDOException;

class PdoDB{
    protected $dbh;
    protected $opt;

    public function __construct($opt){
        try { $this->opt=$opt;
            $connect=$opt['type'].':host='.$opt['hostname'].';dbname='.$opt['database'];
            $this->dbh = new PDO( $connect , $opt['username'] , $opt['password'] );
        }catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";die();
        }
    }
    public function __destruct(){ $this->dbh = null;}

    function getTableFields($childName){
        $result=[]; $childName=strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        $tableQuery = $this->opt['prefix'].$childName;
        $stmt = $this->dbh->query('SELECT * from '.$tableQuery.'s'.' limit 1 ');
        if ( !$stmt ){
            $stmt = $this->dbh->query('SELECT * from '.$tableQuery.' limit 1 ' );
        }else if ( !$stmt ){
            $tableQuery = $this->opt['prefix'].trim($childName,'s');
            $stmt = $this->dbh->query('SELECT * from '.$tableQuery.' limit 1 ' );
        }

        //检查是否查错 - 提示
        $methodExist = method_exists($stmt, "columnCount");
        if (!$methodExist) {
            $database = $this->opt['database'];
            $msg      = "Waring : " . $database . "." . $tableQuery . " | DataTable | is not Exists !";
            $this->console($msg, "yellow");
            die();
        }

        try {
            for($i=0; $i<$stmt->columnCount(); $i++) { $result[]=$stmt->getColumnMeta($i); }
        }catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";die();
        }

        return $result;
    }

    function showFullColumns($childName){
        $result=null; $childName=strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        $stmt = $this->dbh->query('SHOW FULL COLUMNS FROM '.$this->opt['prefix'].$childName.'s');
        if ( !$stmt ){
            $stmt = $this->dbh->query('SHOW FULL COLUMNS FROM '.$this->opt['prefix'].$childName );
        }else if ( !$stmt ){
            $stmt = $this->dbh->query('SHOW FULL COLUMNS FROM '.$this->opt['prefix'].trim($childName,'s') );
        }

        try {
            $result = $stmt->fetchAll();
        }catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";die();
        }

        return $result;
    }

    function showTableStatus($childName){
        $result=null; $childName=strtolower($childName);

        //无前缀处理
        if ($this->opt['prefix'] == '-') {
            $this->opt['prefix'] = '';
        }

        $tableName = $childName.'s'; $stmt = $this->dbh->query('SHOW TABLE STATUS LIKE \''.$this->opt['prefix'].$tableName.'\'');
        if ( !$stmt ){
            $tableName = $childName; $stmt = $this->dbh->query('SHOW TABLE STATUS LIKE \''.$this->opt['prefix'].$tableName.'\'' );
        }else if ( !$stmt ){
            $tableName = trim($childName,'s'); $stmt = $this->dbh->query('SHOW TABLE STATUS LIKE \''.$this->opt['prefix'].$tableName.'\'' );
        }

        try {
            $result = $stmt->fetch(); $result["TableName"]=$tableName;
        }catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";die();
        }

        return $result;
    }

    function query($queryStr){
        $stmt = $this->dbh->query($queryStr);
        return $stmt;
    }



    private function console($msg,$color=null){
        switch ($color){
            default: $first = "\033[0m"; break;
            case "red": $first = "\033[31m"; break;
            case "lemon": $first = "\033[32m"; break;
            case "yellow": $first = "\033[33m"; break;
            case "blue": $first = "\033[34m"; break;
            case "purple": $first = "\033[35m"; break;
            case "green": $first = "\033[36m"; break;
        }
        flush();
        print($first.$msg."\n\033[0m");
    }
}