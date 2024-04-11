<?php
namespace extend\thinktest\base\api;

use extend\thinktest\base\lib\PdoDB;
use think\Config;
use think\Exception;

class ApiToDoc{

    protected $testPath;
    protected $testFile;

    protected $moduleName;
    protected $childName;
    protected $action;

    protected $method ='';
    protected $uri ='';
    protected $query = '';

    protected $header = [];
    protected $data = [];
    protected $response = [];

    protected $content; //待输出内容

    protected $dbOption; //当前数据库配置
    
    public function __construct($moduleName,$childName,$action='request',$dbOptStr='database',$testPath=null){
        //设置数据库配置
        $this->dbOption = $this->changeDbOption($dbOptStr);
        //#

        if( !empty($testPath) ){ $this->testPath =  $testPath; }
        else{  $this->testPath =  root_path().'tests'; }

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

    public function setMethod($method){
        $this->method = strtoupper($method);
        return $this;
    }

    public function setData($data,$header=null){
        $this->data = $data;
        $this->header = $header;
        return $this;
    }

    public function setUri($uri,$query=null){
        $this->uri = $uri;
        $this->query = $query;
        return $this;
    }

    public function setResponse($response){
        $this->response = $response;
        return $this;
    }

    public function write(){
        $WAVE = '~~~'.PHP_EOL;
        $TITLE_SPACE=PHP_EOL.PHP_EOL;
        $ARTICLE_SPACE=PHP_EOL;

        $this->content .= "####". strtoupper( $this->testFile ).$TITLE_SPACE;

        $this->content .= "###### URL".$TITLE_SPACE;
        $this->content .= $WAVE.$this->method.' : '.'{{base_url}}'.$this->pathQueryFilter($this->uri).PHP_EOL.$ARTICLE_SPACE;
        $this->content .= $this->pathQueryMatch($this->uri).PHP_EOL.$WAVE.$ARTICLE_SPACE;

        $this->content .= "###### QUERY".$TITLE_SPACE;
        $this->content .= $WAVE.$this->queryToStr($this->query).$WAVE.$ARTICLE_SPACE;

        $this->content .= "###### HEADER".$TITLE_SPACE;
        $this->content .= $WAVE.$this->arrToStrQuery($this->header).$WAVE.$ARTICLE_SPACE;

        $this->content .= '###### BODY'.$TITLE_SPACE;
        $this->content .= $WAVE.$this->arrToStrQuery($this->data).$WAVE.$ARTICLE_SPACE;

        $this->content .= '###### BODY_DESC'.$TITLE_SPACE;
        $this->content .= $this->arrToTableQuery($this->data).$ARTICLE_SPACE;

        $this->content .= '###### RESPONSE'.$TITLE_SPACE;
        //$this->content .= $WAVE.$this->objToJson($this->response).$WAVE.$ARTICLE_SPACE;
        $this->content .= $WAVE.json_encode($this->response,JSON_UNESCAPED_UNICODE).$ARTICLE_SPACE.$WAVE.$ARTICLE_SPACE;//

        $this->setFile( $this->content );

        return $this;
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

    public function pathQueryFilter($uri){
        $queryStr='';
        if($uri!=null){
            $queryStr =  preg_replace('/([\d]+)$/is','{id}',$uri);
        }
        return $queryStr;
    }


    public function pathQueryMatch($uri){
        $queryStr='';
        if($uri!=null){
            preg_match('/[\d]+$/is',$uri,$m);
            if( isset($m) ){
                foreach ($m as $k=>$v){
                    $queryStr.= '{id} : '.$v;
                }
            }
        }
        return $queryStr;
    }

    public function queryToStr($query){
        $queryStr='';
        if($query!=null){

            $str = trim($query,'/');
            preg_match('/\\?|\\&|[a-zA-Z]+|=/is',$query,$m);
            if ( isset( $m[0] ) ){
                $str = trim($str,'?'); $arr = explode('&',$str);
                foreach ($arr as $k=>$v){
                    $vArr = explode('=',$v);
                    $queryStr.= $vArr[0].' : '.$vArr[1].PHP_EOL;
                };
                return $queryStr;

            }else{
                $queryStr.='id : '.$str.PHP_EOL;
                return $queryStr;
            }
        }

        return $queryStr;
    }

    public function arrToStrQuery($arr){
        if ( is_array($arr) && !empty($arr) ){ $str = '';
            foreach ($arr as $k=>$v){
                $str.= $k.' : '.$v.PHP_EOL;
            }
            return $str;
        }
        return null;
    }

    public function arrToTableQuery($arr){
        $ARTICLE_SPACE=PHP_EOL;
        if( !empty($arr) ){

            $this->content .= "| 字段 | 类型 | 必须 | 默认值 | 说明 |".$ARTICLE_SPACE;
            $this->content .= "| --- | --- | --- | --- | --- |".$ARTICLE_SPACE;

            $childName = ($this->childName);
            $res = $this->DB($childName,"showFullColumns");
            if($res){   $arrKey = array_keys($arr);

                foreach ($res as $k=>$v) {
                    if( in_array( $v['Field'], $arrKey ) ){
                        $this->content .= "| ".$this->fieldFilter($v['Field'])
                            ." | ".$this->typeFilter($v['Type'])
                            ." | ".$this->nullFilter($v['Null'])
                            ." | ".$this->defaultFilter($v['Default'],$v['Key'])
                            ." | ".$this->commentFilter($v['Comment'],$v['Key'])
                            ." |"
                            .$ARTICLE_SPACE;
                    }
                }

            }

        }
        return null;
    }

    public function objToJson($obj){ $str='';

        if( !empty($obj) ){

            if( is_array($obj->data) ){
                $str.='{'.PHP_EOL;
                $str.='    "data":['.PHP_EOL;
                $str.= $this->objListToStr($obj->data);
                $str.='           ],'.PHP_EOL;

                if( isset( $obj->meta ) ){
                    $str.='    "meta":'.PHP_EOL;
                    $str.= $this->objToStr($obj->meta,'     ');
                }

                $str.=PHP_EOL.'}';
            }
            else{
                $str.='{'.PHP_EOL;
                $str.='    "data":'.PHP_EOL;
                $str.= $this->objToStr($obj->data,'     ');
                $str.=PHP_EOL.'}';
                //$str.=json_encode( $obj,JSON_UNESCAPED_UNICODE ).PHP_EOL;
            }
            $str .= PHP_EOL;

        }

        return $str;
    }

    public function objToStr($obj,$space=''){ $str='';
        $str.=$space.'      {'.PHP_EOL;
        foreach ($obj as  $k=>$v){
            if( is_int($v)){
                $str.=$space.'            "'.$k.'"'.':'.$v.','.PHP_EOL;
            }else{
                if( is_bool($v) ){ $str.=$space.'            "'.$k.'"'.':"'.(($v==1)?'true':'false').'",'.PHP_EOL; }
                else{
                    $str.=$space.'            "'.$k.'"'.':"'.(string)$v.'",'.PHP_EOL;
                }
            }
        }
        $str.=$space.'      }';
        return $str;
    }

    public function objListToStr($obj){ $str='';

        foreach ($obj as  $k=>$v){
            //$str.='     '.json_encode($v,JSON_UNESCAPED_UNICODE).','.PHP_EOL;
            $str.= $this->objToStr($v,'           ').','.PHP_EOL;
        }

        return $str;
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