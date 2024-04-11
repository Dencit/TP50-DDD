<?php
namespace extend\thinktest\base\api;

use extend\thinktest\base\lib\PdoDB;
use think\Config;
use think\Exception;

class TableBatchToDoc
{
    protected $mdPath;
    protected $mdFile;

    protected $tableName;

    protected $content; //待输出内容

    protected $opt;

    protected $dbOption; //当前数据库配置

    public function __construct($option,$action="table",$dbOptStr=null){
        $this->opt = $option;
        $this->action = $action;

        //设置数据库配置
        if( empty($dbOptStr) ){ $dbOptStr = 'database'; }
        $this->dbOption = $this->changeDbOption($dbOptStr);
        //#
    }

    public function changeDbOption($dbOptStr='database'){
        //设置数据库配置
        $mysql =  Config::get("$dbOptStr");
        //dsn文本转配置数组
        if( is_string($mysql) ){ $mysql = self::dsnTurnArray($mysql); }

        $dbOption = $mysql;
        if(!$mysql){ $msg="Exception : ChangeDbOption | is fail !"; $this->console($msg,"red"); die; }
        //#
        return $dbOption;
    }

    //dsn文本转配置数组
    //例子文本: mysql://root:password@@localhost:3306/database_name#utf8"
    public static function dsnTurnArray($string,$prefix=''){
        $type = self::pregMerge("/^([\d\w\s]+):\/\/.*$/i",$string);
        $host = self::pregMerge("/^.*[\d\w\s\S]+\@([\d\w\s\S]+):[\d]+.*$/i",$string);
        $port = self::pregMerge("/^.*:([\d\w\s]+)\/.*$/i",$string);
        $database = self::pregMerge("/^.*\/([\d\w\s\S]+)\#.*$/i",$string);
        $username = self::pregMerge("/^\w+:\/\/([\d\w\s]+):.*$/i",$string);
        $password = self::pregMerge("/^.*\/\/[\w]+:([\d\w\s\S]+)\@.*$/i",$string);
        $option['type']=$type;
        $option['hostname']=$host;
        $option['hostport']=$port;
        $option['database']=$database;
        $option['prefix']=$prefix;
        $option['username']=$username;
        $option['password']=$password;
        return $option;
    }

    //正则获取指定两个字符之间字符串
    public static function pregMerge($regular,$str){
        preg_match($regular,$str,$m);
        if( isset($m[1]) ){ $m = $m[1]; }
        else{ $m=''; }
        return $m;
    }

    public function makeFolderByArr ($mdPath,$folders=[],$message=null){

        if(empty($folders)){ $mdPathArr=['table'];}
        else{ $mdPathArr=$folders; }

        foreach ($mdPathArr as $k=>$v){
            $mdPath = $mdPath."\\".$v;

            $isExistFile = file_exists($mdPath);
            if($isExistFile){
                if($message){
                    $msg="Exception : MakeFolder | ".$v." is exists !"; $this->console($msg,"red");
                }
            }
            else{
                $res = mkdir(iconv("UTF-8", "GBK", $mdPath ),0755,true);
                if($res){
                    if($message){
                        $msg="Created : MakeFolder | make ".$v." folder OK"; $this->console($msg,"yellow");
                    }
                }
            }
            usleep(100);
        }

    }

    public function batchWrite($tableName=null,$mdPath=null,$menu=true){

        if( !empty($mdPath) ){ $this->mdPath =  $mdPath; }
        else{ $this->mdPath =  root_path().'markdwon'; }

        $dbNamePath = '\\'.$this->dbOption['database'];

        if( !empty($tableName) && $tableName != '*' ){
            $prefix = $this->dbOption['prefix'];

            //表名统一需要加前缀, 不再自动补充.
            //$tableName = $prefix.$tableName;

            $this->mdFile = $tableName;
            $this->tableName = $tableName;

            $prefixFolder = '\\'. trim($prefix,'_');
            if($menu){ $folderStr = $this->makeFolderStr($tableName,1); }
            else{ $folderStr=''; }
            $finalMdPath = $mdPath.$dbNamePath.$prefixFolder.$folderStr;

            $this->write(false)->setFile($finalMdPath);

            $msg = "Created : Table ".$this->tableName.' | OK';
            $this->console($msg , "yellow");

        }else{
            $allTableName = $this->getAllTableName();
            foreach ($allTableName as $ind=>$tableName ){
                $this->mdFile = $tableName;
                $this->tableName = $tableName;

                if($menu){ $folderStr = $this->makeFolderStr($tableName,2); }
                else{ $folderStr = $this->makeFolderStr($tableName,1); }
                $finalMdPath = $mdPath.$dbNamePath.$folderStr;

                $this->write(false)->setFile($finalMdPath);

                $msg = "Created : Table ".$this->tableName.' | OK';
                $this->console($msg , "yellow");
                usleep(10000);
            }
        }

    }

    protected function makeFolderStr($tableName,$num=1){
        $fileFolderArr = explode('_',$tableName);
        $folderStr = '';
        foreach ($fileFolderArr as $ind=>$str){
            //只获取第2级目录
            if($ind >= $num ){ break;}
            else{
                $folderStr .= '\\'.$str;
            }
        }
        return $folderStr;
    }

    protected function write($prefix=true){
        $this->content = '';

        $WAVE = '~~~'.PHP_EOL;
        $TITLE_SPACE=PHP_EOL.PHP_EOL;
        $ARTICLE_SPACE=PHP_EOL;

        $tableName = $this->tableName;

        $this->content .= "#### ". strtoupper($this->tableName) .$TITLE_SPACE;

        $res = $this->DB($tableName,"showTableStatus",$prefix);
        if ($res){ $this->content .= "> ".$res['TableName'].' | '.$res['Comment'].$TITLE_SPACE; }

        $this->content .= "| 字段 | 类型 | 必须 | 默认值 | 说明 |".$ARTICLE_SPACE;
        $this->content .= "| --- | --- | --- | --- | --- |".$ARTICLE_SPACE;
        $res = $this->DB($tableName,"showFullColumns",$prefix);

        if($res){
            foreach ($res as $k=>$v) {
                $this->content .= "| ".$this->fieldFilter($v['Field'])
                    ." | ".$this->typeFilter($v['Type'])
                    ." | ".$this->nullFilter($v['Null'])
                    ." | ".$this->defaultFilter($v['Default'],$v['Key'])
                    ." | ".$this->commentFilter($v['Comment'],$v['Key'])
                    ." |"
                    .$ARTICLE_SPACE;
            }
        }

        return $this;
    }

    public function fieldFilter($field){
        return $field;
    }
    public function typeFilter($type){
        preg_match('/^([A-Z a-z]+)\\W([0-9]+|\\d+\\W+\\d+)\\.*/is',$type,$mType);
        preg_match('/^([A-Z a-z]+)$/is',$type,$nType);

        if( !empty($mType[1]) ){
            switch ($mType[1]){
                default : return $type; break;
                case "int": case "bigint": case"tinyint":   return "int ".$mType[2];   break;
                case "char": case "varchar":  return "string ".$mType[2];  break;
                case "text": case "longtext":   return "text ".$mType[2];  break;
                case "decimal": return "decimal ".$mType[2]; break;
            }
        }

        if( !empty($nType[1]) ){
            switch ($nType[1]){
                default : return $type; break;
                case "datetime":  return "date_time";  break;
                case "timestamp": return "date_time"; break;
            }
        }

        return $type;
    }
    public function nullFilter($nullStr){
        switch ($nullStr){
            default : return ''; break;
            case "NO": return "yes";   break;
            case "YES": return "no";   break;
        }
    }
    public function defaultFilter($default,$key=null){
        if( $default == null ){ $default = ""; }
        if( $key=="PRI" ){ $default = "auto_increment"; }

        return $default;
    }
    public function commentFilter($comment,$key=null){
        if( empty($comment) && $key=="PRI" ){ $comment = "主键"; }

        return $comment;
    }

    public function setFile( $mdPath ){

        $docPath = $mdPath;
        $filePath = $mdPath.'\\'.$this->mdFile.'.md';

        $isExist = file_exists($docPath);
        if($isExist){ }
        else{ mkdir(iconv("UTF-8", "GBK", $docPath ),0755,true); }

        file_put_contents($filePath,$this->content);

        return $mdPath;
    }

    function DB($tableName,$type=null,$prefix=true){
        $opt = $this->dbOption;//设置数据库配置
        if($opt){
            //是否使用前缀
            if($prefix==false){ $opt['prefix'] = ''; }
            //#

            $DB = new PdoDB($opt);
            switch ($type){
                default: $res=$DB->query($type); break;
                case "getTableFields": $res=$DB->getTableFields($tableName); break;
                case "showFullColumns": $res=$DB->showFullColumns($tableName); break;
                case "showTableStatus": $res=$DB->showTableStatus($tableName); break;
            }
            return  $res;
        }


        return null;
    }

    //获取
    protected function getAllTableName(){
        $opt = $this->dbOption;//设置数据库配置

        $sql = " select table_name from information_schema.tables where table_schema='".$opt['database']."'";
        $DB = new PdoDB($opt);

        $res = $DB->query($sql)->fetchAll();
        $tableNameArr = array_column($res,'TABLE_NAME');

        return $tableNameArr;
    }


    function console($msg,$color=null){
        switch ($color){
            default: $first = "\033[0m"; break;
            case "red": $first = "\033[31m"; break;
            case "yellow": $first = "\033[33m"; break;
        }
        flush();
        print($first.$msg."\n\033[0m");
    }
}