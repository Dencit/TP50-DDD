<?php
namespace extend\thinktest\base\api;

use extend\thinktest\base\lib\PdoDB;
use think\Config;
use think\Exception;

class TableToDoc
{
    protected $testPath;
    protected $testFile;

    protected $moduleName;
    protected $childName;

    protected $content; //待输出内容

    protected $dbOption; //当前数据库配置
    
    public function __construct($moduleName,$childName,$action="table",$dbOptStr='database',$testPath=null){
        //设置数据库配置
        $this->dbOption = $this->changeDbOption($dbOptStr);
        //#

        if( !empty($testPath) ){ $this->testPath =  $testPath; }
        else{ $this->testPath =  root_path().'tests'; }

        $this->testFile = $this->childNameFilter($childName).'-['.$action.']';

        $this->moduleName = $moduleName;
        $this->childName = $childName;
        $this->action = $action;
    }

    public function changeDbOption($dbOptStr='database'){
        //设置数据库配置
        $dbOption =  Config::get("$dbOptStr");
        if(!$dbOption){ $msg="Exception : ChangeDbOption | is fail !"; throw new Exception($msg); }
        //#
        return $dbOption;
    }

    public function write(){

        $WAVE = '~~~'.PHP_EOL;
        $TITLE_SPACE=PHP_EOL.PHP_EOL;
        $ARTICLE_SPACE=PHP_EOL;

        $childName = ($this->childName);

        $this->content .= "#### ". strtoupper( $this->testFile ).$TITLE_SPACE;

        $res = $this->DB($childName,"showTableStatus");
        if ($res){ $this->content .= "> ".$res['TableName'].' | '.$res['Comment'].$TITLE_SPACE; }

        $this->content .= "| 字段 | 类型 | 必须 | 默认值 | 说明 |".$ARTICLE_SPACE;
        $this->content .= "| --- | --- | --- | --- | --- |".$ARTICLE_SPACE;
        $res = $this->DB($childName,"showFullColumns");

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

        $this->setFile( $this->content );

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
    public function setFile( $content ){
        $moduleName = $this->moduleName;
        $docPath = $this->testPath.'\\'.$moduleName.'\\doc';
        $childPath = $this->testPath.'\\'.$moduleName.'\\doc'.'\\'.str_replace($moduleName.'_','',$this->testFile).'.md';

        $isExist = file_exists($docPath);
        if($isExist){ }
        else{ mkdir(iconv("UTF-8", "GBK", $docPath ),0755,true); }

        file_put_contents($childPath,$content);

        return $childPath;
    }

    function DB($childName,$type=null){
        $opt = $this->dbOption;//设置数据库配置
        if($opt){
            $DB = new PdoDB($opt);
            $childName = $this->childNameFilter($childName);

            switch ($type){
                default: $res=$DB->query($type); break;
                case "getTableFields": $res=$DB->getTableFields($childName); break;
                case "showFullColumns": $res=$DB->showFullColumns($childName); break;
                case "showTableStatus": $res=$DB->showTableStatus($childName); break;
            }
            return  $res;
        }

        return null;
    }

    //“_”拆分$childName
    function childNameFilter($childName){
        $newChildName='';
        for ($i=1;$i<10;$i++){
            preg_match("/([A-Z]{1}[a-z 0-9]+){".$i."}/",$childName,$m);
            if( !empty($m[1]) ){
                $newChildName.=$m[1].'_';
            }else{ $newChildName = trim( $newChildName,"_" );
                break;
            }
        }

        if( empty($newChildName) ){ $newChildName = $childName; }

        return strtolower($newChildName);
    }

}