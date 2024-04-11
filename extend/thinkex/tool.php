<?php
/**
 * notes:
 * @author 陈鸿扬 | @date 2021/4/23 18:07
 */

namespace extend\thinkex;

use extend\thinkex\MongoDB;


class tool
{
    protected $readPath;
    protected $putPath;
    protected $content;

    public function __construct($option){
        $this->option = $option;
    }


    public function makeFolder ($childPath){
        $fullPath = $this->option['md_path'].'\\'.$childPath;
        if( !file_exists($fullPath) ){
            mkdir(iconv("UTF-8", "GBK", $fullPath ),0755,true);
        }
        return $fullPath;
    }

    /**
     * notes: 读取 yapi db 数据 转换成标准 json
     * @author 陈鸿扬 | @date 2021/6/1 16:38
     */
    public function yapiProjRead($projectId){

        $MongoDB = new MongoDB($this->option);
        $inter = $MongoDB->database('yapi')->collection('interface');
        $where = ['project_id'=>$projectId ];
        $interRes = $inter->get($where);

        /*$interCase = $MongoDB->database('yapi')->collection('interface_case');
        $where = ['project_id'=>$projectId ];
        $interCaseRes = $interCase->get($where);*/

        //dd( array_column($interRes,'_id') );

        dd( json_decode(json_encode($interRes),true) );

        foreach ( $interRes as $k=>$v){
            var_dump( (array)$v['query_path'] );
            var_dump( (array)$v['req_query'] );
            var_dump( (array)$v['req_headers'] );
            die;
        }

    }

    /**
     * notes: 读取文件
     * @author 陈鸿扬 | @date 2021/4/23 18:29
     * @param $readPath
     * @return bool|string
     */
    public function fileRead($readPath){
        $fullPath = $this->option['md_path'].'\\'.$readPath;
        $content = file_get_contents($fullPath);
        $this->content = $content;
        return $content;
    }

    /**
     * notes: 获取文件内容
     * @author 陈鸿扬 | @date 2021/4/23 18:29
     * @return mixed
     */
    public function fileContent(){
        return $this->content;
    }

    /**
     * notes: 写入文件
     * @author 陈鸿扬 | @date 2021/4/23 18:29
     * @param $putPath
     * @param null $content 不传 则 用获取到的内容
     * @return bool|int
     */
    public function filePut($putPath,$content=null){
        $fullPath = $this->option['md_path'].'\\'.$putPath;
        if( empty($content) ){ $content=$this->content; }
        return file_put_contents($fullPath,$content);
    }

    /**
     * notes: yapi-json 转换
     * @author 陈鸿扬 | @date 2021/4/26 9:32
     * @param $yapiArr
     * @return array
     */
    public function yapiTurnAll($yapiArr){
        $psmArr = [
            "info"=>[
                "name"=>'-API接口-'.time(),
                "schema"=>"https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            ]
        ];
        $item = [];
        foreach ($yapiArr as $ind1=>$lv1 ){
            $item[$ind1] = $this->yapiTurnFilter($lv1);
        }
        $psmArr['item']=$item;
        return $psmArr;
    }

    /**
     * notes: yapi-json 子转换
     * @author 陈鸿扬 | @date 2021/5/27 15:31
     * @param $lv1
     * @return array
     */
    public function yapiTurnFilter($lv1){
        $item = [];
        $item['name'] =  $lv1['name'];
        $item['item'] = [];
        foreach ($lv1['list'] as $in=>$list ){
            $item['item'][$in]['name'] = $list['title'];

            if (isset($list['markdown']) && !empty($list['markdown'])) {
                $item['item'][$in]['event'][0]=$this->reqMarkdownTurn($list['markdown']);
            }

            $item['item'][$in]['request']['method'] = $list['method'];
            $item['item'][$in]['request']['header'] = $this->reqHeadersTurn($list['req_headers']);

            if(isset($list['req_body_type'])){
                $reqBodyType = $list['req_body_type'];
                switch ($reqBodyType){
                    case 'form' :
                        //包含file类型字段 需要换postman body格式
                        $types = array_column($list['req_body_form'],'type');
                        if( in_array('file',$types) ){
                            $item['item'][$in]['request']['body'] = $this->reqBodyFormTurn($list['req_body_form']);
                        }else{
                            $item['item'][$in]['request']['body'] = $this->reqBodyXFormTurn($list['req_body_form']);
                        }
                        break;
                    case 'json' :
                        if(isset($list['req_body_other'])){
                            $item['item'][$in]['request']['body'] = $this->reqBodyOtherTurn($list['req_body_other']);
                        }
                        break;
                }
            }

            $item['item'][$in]['request']['url']['host'][0] = '{{base_url}}';
            $item['item'][$in]['request']['url']['raw'] = $this->queryFullPathTurn($list['query_path']);
            $item['item'][$in]['request']['url']['path'] = $this->queryPathTurn($list['query_path']);

            if(isset($list['req_params'])){
                $item['item'][$in]['request']['url']['variable'] = $this->reqParamsTurn($list['req_params']);
            }

            if(!empty($list['req_query'])){
                $item['item'][$in]['request']['url']['query']= $this->reqQueryTurn($list['req_query']);
            }

//                $resBodyType = $list['res_body_type'];
//                switch ($resBodyType){
//                    case 'json' :
//                        $item['item'][$in]['response'] = $this->resBodyTurn($list['res_body']);
//                        break;
//                }

        }
        return $item;
    }

    public function yapiTurSingle($yapiArr,$putPath){
        foreach ($yapiArr as $ind1=>$lv1 ){
            $this->yapiTurSingleFilter($lv1,$putPath);
        }
        return $yapiArr;
    }

    public function yapiTurSingleFilter($lv1,$putPath){

        $item = [];
        $item['name'] =  str_replace('\/','/',$lv1['name']);
        $item['item'] = [];

        $childPath = trim($putPath,'/').'\\'.$item['name'].'\\';
        self::makeFolder($childPath);

        foreach ($lv1['list'] as $in=>$list ){
            $in = 0;
            $item['item'][$in]['name'] = $list['title'];

            if (isset($list['markdown']) && !empty($list['markdown'])) {
                $item['item'][$in]['event'][0]=$this->reqMarkdownTurn($list['markdown']);
            }

            $item['item'][$in]['request']['method'] = $list['method'];
            $item['item'][$in]['request']['header'] = $this->reqHeadersTurn($list['req_headers']);

            if(isset($list['req_body_type'])){
                $reqBodyType = $list['req_body_type'];
                switch ($reqBodyType){
                    case 'form' :
                        //包含file类型字段 需要换postman body格式
                        $types = array_column($list['req_body_form'],'type');
                        if( in_array('file',$types) ){
                            $item['item'][$in]['request']['body'] = $this->reqBodyFormTurn($list['req_body_form']);
                        }else{
                            $item['item'][$in]['request']['body'] = $this->reqBodyXFormTurn($list['req_body_form']);
                        }
                        break;
                    case 'json' :
                        if(isset($list['req_body_other'])){
                            $item['item'][$in]['request']['body'] = $this->reqBodyOtherTurn($list['req_body_other']);
                        }
                        break;
                }
            }

            $item['item'][$in]['request']['url']['host'][0] = '{{base_url}}';
            $item['item'][$in]['request']['url']['raw'] = $this->queryFullPathTurn($list['query_path']);
            $item['item'][$in]['request']['url']['path'] = $this->queryPathTurn($list['query_path']);

            if(isset($list['req_params'])){
                $item['item'][$in]['request']['url']['variable'] = $this->reqParamsTurn($list['req_params']);
            }

            if(!empty($list['req_query'])){
                $item['item'][$in]['request']['url']['query']= $this->reqQueryTurn($list['req_query']);
            }

            $psmArr = [
                "info"=>[
                    "name"=> preg_replace("/^[\w\s\d]+\//i",'',$putPath),
                    "schema"=>"https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
                ]
            ];
            $psmArr['item'][0]=$item;
            $content = json_encode($psmArr,JSON_UNESCAPED_UNICODE);
            $content = str_replace('\/','/',$content);


            $fileName = preg_replace('/[\\\|\\|\\.|\\/|]/i','_',$list['title']).'.json';
            $fullPath = $childPath.$fileName;
            self::filePut($fullPath,$content);
            usleep(10000);
        }
        return $item;
    }


    /**
     * notes: 转换 yapi-json markdown
     * @author 陈鸿扬 | @date 2021/5/11 13:51
     * @param $mdText
     * @return mixed
     */
    public function reqMarkdownTurn($mdText){
        $trueText = strip_tags($mdText);
        $trueText = htmlspecialchars_decode($trueText);
        $trueText = preg_replace('/[\\n]/i', '\r[|]', $trueText);
        $trueTextArr = explode('\r[|]', $trueText);
        array_unshift($trueTextArr,'/*'); array_push($trueTextArr,'*/');
        $event['listen'] = 'test';
        $event['script']['exec'] = $trueTextArr;
        $event['script']['type'] = 'text/javascript';
        return $event;
    }

    /**
     * notes: 转换 yapi-json req_headers
     * @author 陈鸿扬 | @date 2021/4/25 10:36
     * @param $reqHeaders
     * @return array
     */
    public function reqHeadersTurn($reqHeaders){

        //默认header
        $normal = [
            ["key" => "appkey", "value" => "{{appkey}}", "type" => "text", "description" => "应用key"],
            ["key" => "timestamp", "value" => "{{timestamp}}", "type" => "text", "description" => "时间戳"],
            ["key" => "nonce", "value" => "{{nonce}}", "type" => "text", "description" => "随机数"],
            ["key" => "sign", "value" => "{{sign}}", "type" => "text", "description" => "签名"],
            ["key" => "token", "value" => "{{token}}", "type" => "text", "description" => "授权密匙"],
            ["key" => "stresstest", "value" => "{{stresstest}}", "type" => "text", "description" => "测试密匙"],
            ["key" => "version", "value" => "{{version}}", "type" => "text", "description" => "版本号"],

            ["key" => "flstoken", "value" => "{{flstoken}}", "type" => "text", "description" => "福利社-授权密匙 - 备选"],//
            ["key" => "authorize", "value" => "{{authorize}}", "type" => "text", "description" => "authorize-token - 备选"],//
            //["key" => "PHPSESSID", "value" => "{{session}}", "type" => "text", "description" => "session-id  - 备选"],//
        ];
        $normalKeys=array_column($normal,'key');

        $newReqHeaders = [];
        foreach ($reqHeaders as $ind=>$item){

            //忽略Content-Type
            if( $item['name']=='Content-Type' ){ continue; }

            //忽略与默认header同名参数
            if( in_array(strtolower($item['name']) ,$normalKeys) ){ continue; }

            $newReqHeaders[$ind]['key']=$item['name'];
            if(!empty($item['example'])){
                $this->blankCompactFilter($item['example']); //精简空格 去除换行
                $newReqHeaders[$ind]['value']= $this->defValueFilter($item['name'],'null');
            }else{
                if(isset($item['value'])){
                    $this->blankCompactFilter($item['value']); //精简空格 去除换行
                    $newReqHeaders[$ind]['value']= $this->defValueFilter($item['name'],'null');
                }
            }

            $newReqHeaders[$ind]['type']='text';
            if( isset($item['desc'])){
                $this->blankCompactFilter($item['desc']); //精简空格 去除换行
                $newReqHeaders[$ind]['description']=$item['desc'];
            }
        }
        $newReqHeaders = array_values($newReqHeaders);
        $newReqHeaders = array_merge($normal,$newReqHeaders);
        return $newReqHeaders;
    }

    //转换 yapi-json req_body_form - to postman x-from
    public function reqBodyXFormTurn($reqBodyForm){
        $newReqBodyForm['mode']='urlencoded';
        $newReqBodyForm['urlencoded']=[];
        foreach ($reqBodyForm as $ind=>$item){
            $newReqBodyForm['urlencoded'][$ind]['key']=$item['name'];
            if(isset($item['example'])){
                $this->blankCompactFilter($item['example']); //精简空格 去除换行
                $newReqBodyForm['urlencoded'][$ind]['value']=$item['example'];
            }

            //默认值null
            if( empty($item['example'])){
                $newReqBodyForm['urlencoded'][$ind]['value']= $this->defValueFilter($item['name'],'null');
            }

            $newReqBodyForm['urlencoded'][$ind]['type']='text';
            if( isset($item['desc'])){
                $this->blankCompactFilter($item['desc']); //精简空格 去除换行
                $newReqBodyForm['urlencoded'][$ind]['description']=$item['desc'];
            }
        }
        return $newReqBodyForm;
    }

    //转换 yapi-json req_body_form - to postman form
    public function reqBodyFormTurn($reqBodyForm){
        $newReqBodyForm['mode']='formdata';
        $newReqBodyForm['formdata']=[];
        foreach ($reqBodyForm as $ind=>$item){
            $newReqBodyForm['formdata'][$ind]['key']=$item['name'];
            if(isset($item['example'])){
                $this->blankCompactFilter($item['example']); //精简空格 去除换行
                $newReqBodyForm['formdata'][$ind]['value']=$item['example'];
            }

            //默认值null
            if( empty($item['example'])){
                $newReqBodyForm['formdata'][$ind]['value']= $this->defValueFilter($item['name'],'null');
            }

            //区分表单中file类型字段
            if(  isset($item['type']) && $item['type']=='file' ){
                $newReqBodyForm['formdata'][$ind]['type']='file';
                $newReqBodyForm['formdata'][$ind]['src']=[];
            }else{
                $newReqBodyForm['formdata'][$ind]['type']='text';
            }

            if( isset($item['desc'])){
                $this->blankCompactFilter($item['desc']); //精简空格 去除换行
                $newReqBodyForm['formdata'][$ind]['description']=$item['desc'];
            }




        }
        return $newReqBodyForm;
    }

    //转换 yapi-json req_body_other
    public function reqBodyOtherTurn($reqBodyOther){
        //$reqBodyOther = json_encode(json_decode($reqBodyOther));
        $newReqBodyOther['mode']='raw';
        $newReqBodyOther['raw']=$reqBodyOther;
        $newReqBodyOther['options']['raw']['language']='json';
        return $newReqBodyOther;
    }

    public  function queryFullPathTurn($queryPath){
        $newQueryPath = explode('/',trim($queryPath['path'],'/'));
        foreach ($newQueryPath as $ind => &$value) { $this->pathValueFilter($value); }
        $queryPath['path'] = implode('/',$newQueryPath);

        $newQueryFullPath = '{{base_url}}'.$queryPath['path'];
        return $newQueryFullPath;
    }

    //转换 yapi-json query_path
    public  function queryPathTurn($queryPath){
        $newQueryPath = explode('/',trim($queryPath['path'],'/'));
        foreach ($newQueryPath as $ind => &$value) { $this->pathValueFilter($value); }

        return $newQueryPath;
    }

    //转换 path 中 {id} 为 :id
    public function pathValueFilter(&$pathValue){
        //转换 path 中 {id} 为 :id
        preg_match('/.*({[\w\s\d]+}).*/is',$pathValue,$m);
        if(isset($m[1])){
            $pathValue = preg_replace('/(.*){([\w\s\d]+)}(.*)/is','$1:$2$3',$pathValue);
        }
        //转换 path 中 :s5 为 ''
        preg_match('/.*(:[\w]+[\d]+).*/is',$pathValue,$n);
        if(isset($n[1])){
            $pathValue = preg_replace('/(.*)(:[\w]+[\d]+)(.*)/is','$1$3',$pathValue);
        }
        return $pathValue;
    }

    //转换 yapi-json res_params
    public function reqParamsTurn($reqParams){
        //if(!empty($reqParams)){ dd($reqParams); }//

        $newReqParams = [];
        foreach ($reqParams as $ind=>$item){
            if( isset($item['name'])){
                $newReqParams[$ind]['key']=$item['name'];
            }

            if( isset($item['example'])){
                $this->queryStrValueFilter($item['example'],$item['name']);
                $this->blankCompactFilter($item['example']); //精简空格 去除换行
                $newReqParams[$ind]['value']=$item['example'];
            }

            //默认值1 , 避免路由异常
            if( empty($item['example'])){
                $newReqParams[$ind]['value']=$this->defValueFilter($item['name'],'1');
            }

            if( isset($item['desc'])){
                $this->blankCompactFilter($item['desc']); //精简空格 去除换行
                $newReqParams[$ind]['description']=$item['desc'];
            }
        }
        return $newReqParams;
    }

    //转换 yapi-json res_query
    public function reqQueryTurn($reqQuery){
        $newReqQuery=[];
        foreach ($reqQuery as $ind=>$item){
            if( isset($item['name'])){
                $newReqQuery[$ind]['key']=$item['name'];
            }

            if( isset($item['example'])){
                $this->queryStrValueFilter($item['example'],$item['name']);
                $this->blankCompactFilter($item['example']); //精简空格 去除换行
                $newReqQuery[$ind]['value']=$item['example'];
            }

            //默认值null , 避免路由异常
            if( empty($item['example'])){
                $newReqQuery[$ind]['value']= $this->defValueFilter($item['name'],'null');
            }

            if( isset($item['desc'])){
                $this->blankCompactFilter($item['desc']); //精简空格 去除换行
                $newReqQuery[$ind]['description']=$item['desc'];
            }
        }
        return $newReqQuery;
    }

    //默认值处理 - 针对空字段
    public function defValueFilter($name,$default='null'){
        $value = $default;
        switch ($name){
            case 'page': $value = '1'; break; //翻页
            case 'pagesize': $value = '20'; break; //页数
            case 'pageSize': $value = '20'; break; //页数
            case 'page_size': $value = '20'; break; //页数
            case 'row': $value = '20'; break; //页数
            case 'sort': $value = 'desc'; break; //排序
            case 'order': $value = 'desc'; break; //排序
            case 'keyword': $value = '关键字'; break; //关键字
            case 'keywords': $value = '关键字'; break; //关键字
            case 'recommand': $value = '1'; break; //0-不推荐1-推荐
            case 'recommend': $value = '1'; break; //0-不推荐1-推荐
            case 'cate_id': $value = '1'; break; //分类id
            case 'userid': $value = '{{user_id}}'; break; //用户id
            case 'user_id': $value = '{{user_id}}'; break; //用户id
            case 'mobile': $value = '{{mobile}}'; break; //手机
            case 'account': $value ='{{mobile}}'; break; //手机
            case 'positionid': $value = '101126504'; break; //职位id
            case 'position_id': $value = '101126504'; break; //职位id
            case 'positiontype': $value = '6'; break; //职位类型
            case 'position_type': $value = '6'; break; //职位类型 1兼职；2实习；3双向；4公益；5校招（未使用）；6全职；7（未使用）；8暑期实习
            case 'id': $value = '1'; break; //id
            case 'num_id': $value = '1'; break; //id
            case 'num_iid': $value = '1'; break; //id
            case 'password': $value ="{{password}}"; break; //密码
            case 'mobilecode': $value = '+86'; break; //手机区域码
            case 'mobile_code': $value = '+86'; break; //手机区域码
            case 'code': $value = '123456'; break; //验证码
            case 'url': $value = 'http://host.com'; break; //地址
            case 'level': $value = '1'; break; //层 1,23...
            case 'pid': $value = '0'; break; //父id
            case 'location': $value = '113.3612,23.12468'; break; //lng
            case 'lng': $value = '113.3612'; break; //lng
            case 'lat': $value = '23.12468'; break; //lat
            case 'longitude': $value = '113.3612'; break; //lng
            case 'latitude': $value = '23.12468'; break; //lat
            case 'cityid': $value = '2152'; break; //城市id
            case 'city_id': $value = '1'; break; //城市id
            case 'areaid': $value = '2162'; break; //地区id
            case 'area_id': $value = '2162'; break; //地区id
            case 'rangeid': $value = '2162'; break; //地区id
            case 'companyid': $value = '1'; break; //企业id
            case 'company_id': $value = '1'; break; //企业id
            case 'type': $value = '1'; break; //类型
            case 'status': $value = '1'; break; //状态
            case 'order_id': $value = '4088'; break; //订单id
            case 'source': $value = '1'; break; //来源
            case 'email': $value = 'name@mail.com'; break; //来源
            case 'token': $value = '{{token}}'; break; //api token
            case 'accesstoken': $value = '{{token}}'; break; //api token
            case 'access_token': $value = '{{token}}'; break; //api token
            case 'wxtoken': $value = '{{wx_token}}'; break; //微信token
            case 'wx_token': $value = '{{wx_token}}'; break; //微信token
        }
        return $value;
    }

    //转换 yapi-json res_body
    public function resBodyTurn($resBody){
        //$resBody = json_encode(json_decode($resBody));
        $newResBody['mode']='raw';
        $newResBody['raw']=$resBody;
        $newResBody['options']['raw']['language']='json';
        return $newResBody;
    }

    //过滤 value值 为 query 字符串的文本, 如: page=1&pagesize=10&tyep=0 这些.
    public function queryStrValueFilter(&$string,$filterName){
        $exp = '/^.*['.$filterName.']=(.*$)/i';
        preg_match($exp,$string,$m);
        if( isset($m[1]) ){ $string = $m[1]; }
        return $string;
    }

    //精简空格 去除换行
    public function blankCompactFilter(&$string){
        $string= preg_replace('/[\s]+/i',' ',$string);
        $string = str_replace(PHP_EOL, '', $string);
        $string = $this->makeSemiAngle($string);
        return $string;
    }

    /**
     * notes: 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     * @author 陈鸿扬 | @date 2021/4/27 18:17
     * @param $str //待转换字串
     * @return string //处理后字串
     */
    public function makeSemiAngle($str)
    {
        $arr = ['０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
            '‘' => '[', '\'' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
            '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
            '”' => '"', '\'' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
            '　' => ' '];
        return strtr($str, $arr);
    }

}