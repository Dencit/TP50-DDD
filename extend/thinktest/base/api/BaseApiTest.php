<?php
namespace extend\thinktest\base\api;

use extend\thinktest\BaseUnit;
use extend\thinktest\base\lib\Request;
use PHPUnit\Framework\TestCase;
use extend\thinktest\base\cache\CacheRedis;
use Symfony\Component\VarDumper\VarDumper;
use think\Config;
use think\Db;
use think\Exception;

abstract class BaseApiTest extends TestCase
{
    protected $baseHost;

    private $app;
    private $request;
    private $response;

    protected $dbOptStr; //当前数据库配置名
    protected $dbOption; //当前数据库配置

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $unit = new BaseUnit();
        $this->app = $unit->init();
        $this->app->initCommon();

        parent::__construct($name, $data, $dataName);
    }

    public function changeDbOption($dbOptStr='database'){
        //设置数据库配置
        $dbOption =  Config::get("$dbOptStr");
        if(!$dbOption){ $msg="Exception : ChangeDbOption | is fail !"; throw new Exception($msg); }
        //#
        return $dbOption;
    }

    public function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    public function makeSignData($data){
        $base = [
            'timestamp'=> time(),
            'nonce'=> bin2hex(random_bytes(16)),
            'appkey'=>'',
            'appsecret'=>'',
            //'sign'=>'',
        ];
        ksort($base);
        $params = $this->ToUrlParams($base);
        $base['sign'] = md5($params);
        $data = array_merge($data,$base);
        return $data;
    }


    public function getUserToken(){
        $uri = '/user/login';
        $url = $this->baseHost.$uri;
        $data=[
            'mobile'=>'18588891945', 'pass_word'=>'123456',
            'scope_id'=>'user_auth', 'client_id'=>'h5_client','client_driver'=>'fire_fox','client_type'=>1,
            'lat'=>0.123,'lng'=>0.123
        ];
        $header=[ 'source'=>'h5', ];
        //var_dump('getAdminToken');//
        //$result = $this->httpRequest('put',$url,$data,$header,$response);//网络请求
        $result=$this->put($uri,$data,$header,$response);//模拟请求

        return $result;
    }

    public function getAdminToken(){
        $uri = '/admin/login';
        $url = $this->baseHost.$uri;
        $data=[
            'mobile'=>'18500010002', 'pass_word'=>'123456',
            'scope_id'=>'system_auth', 'client_id'=>'system_client','client_driver'=>'fire_fox','client_type'=>1,
            'lat'=>0.123,'lng'=>0.123
        ];
        $header=[ 'source'=>'h5', ];
        //var_dump('getAdminToken');//
        //$result = $this->httpRequest('put',$url,$data,$header,$response);//网络请求
        $result=$this->put($uri,$data,$header,$response);//模拟请求

        return $result;
    }

    public function getFormToken($token){
        $uri = '/get-form-token';
        $url = $this->baseHost.$uri;
        $data = [];
        $header = [ 'token'=> $token ];
        //var_dump('getFormToken');//
        //$result = $this->httpRequest('get',$url,$data,$header,$response);//网络请求
        $result=$this->get($uri,$data,$header,$response);//模拟请求
        return $result;
    }



    /*
     * 根据命令行关键字 - 清除 缓存id & 数据库痕迹
     */
    protected function tableCleanByArgv($name,$action=null,$dbOptStr='database'){
        //自定义 命令行参数
        $actions = ['clean','stay'];
        $argv = $_SERVER['argv'];

        //设置当前数据库配置名
        $this->dbOptStr=$dbOptStr;
        //#

        if( isset($argv[2]) && in_array($argv[2],$actions) ){
            switch ($argv[2]){
                default: break; //不清除 缓存ID和数据
                case 'clean':  $this->tableCleanCache($name); break; //清除 所有 缓存ID和数据
                case 'stay': $this->tableCleanCache($name,true); break; //清除 缓存ID 但 保留数据
            }
        }
        //自定义 传参
        else if(!empty($action)){
            switch ($action){
                default: break; //不清除 缓存ID和数据
                case 'clean': $this->tableCleanCache($name); break; //清除 所有 缓存ID和数据
                case 'stay': $this->tableCleanCache($name,true); break; //清除 缓存ID 但 保留数据
            }
        }
    }

    /**
     * 清除 缓存id & 数据库痕迹
     * 说明: 不使用时, tablePushTempId()使 redis缓存数据id递增; 使用时,就连同历史数据清除
     * @param $name
     * @param $stayData [ true ,清除缓存id 但 保留数据 ]
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function tableCleanCache($name,$stayData=false):void{
        $haveIds = $this->tableGetTempIds($name);
        if($haveIds){
            //测试完 清除缓存入库数据
            $this->tableCleanTempIds($name);
            if( !$stayData ){ //是否保留数据
                //数据库清理逻辑
                $this->tableClean($name,$haveIds);
            }
        }
    }

    /**
     * 缓存入库数据id,待测试完清除
     * @param $name
     * @param $id
     * @return null|void
     */
    public function tablePushTempId($name,$id){ $result = null;
        //缓存入库数据id,待测试完清除
        $dataIds = []; $have = $this->getTempData($name); if ( !empty( $have ) ){ $dataIds = $have; };
        array_push( $dataIds , $id );
        $result = $this->setTempData( $dataIds ,$name);
        return $result;
    }
    public function tableMergeTempIds($name,$ids){ $result = null;
        //缓存入库数据id,待测试完清除
        $dataIds = []; $have = $this->getTempData($name); if ( !empty( $have ) ){ $dataIds = $have; };
        $dataIds = array_keys(array_flip($dataIds)+array_flip($ids)); //去重合并
        $result = $this->setTempData( $dataIds ,$name);
        return $result;
    }
    public function tableGetTempIds($name){
        return $this->getTempData($name);
    }
    public function tableCleanTempIds($name){ $result = null;
        $haveIds = $this->tableGetTempIds($name);
        if ( !empty( $haveIds ) ){ $result =$this->removeTempData($name);}
        return $result;
    }

    /**
     * 数据库清理逻辑 - 包括回滚自增id
     * @param $name
     * @param $ids
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function tableClean($name,$ids){

        $result = Db::name($name)->delete($ids);
        $num = $ids[0];

        //设置当前数据库配置
        $dbOption = $this->changeDbOption($this->dbOptStr);
        //#
        $prefix= $dbOption['prefix'];
        $tableName = $prefix.$name;

        $sql = "alter table ".$tableName." auto_increment = ".$num;
        Db::query($sql);

        return $result;
    }

    public function setTempData($data,$mine= "temp_data"){
        $ApiCacheRedis =  new CacheRedis(9);
        $keyName = implode("_", (explode('\\',__CLASS__)) ).'_';
        $ApiCacheRedis->setDataByMineKey($keyName,json_encode($data),$mine);
    }
    public function getTempData($mine= "temp_data"){
        $ApiCacheRedis =  new CacheRedis(9);
        $keyName = implode("_", (explode('\\',__CLASS__)) ).'_';
        return json_decode( $ApiCacheRedis->getDataByMineKey($keyName,$mine) );
    }
    public function removeTempData($mine= "temp_data"){
        $ApiCacheRedis =  new CacheRedis(9);
        $keyName = implode("_", (explode('\\',__CLASS__)) ).'_';
        return json_decode( $ApiCacheRedis->delDataByMineKey($keyName,$mine) );
    }


    //网络请求

    public function httpRequest($method,$url, $data = null, $header = null, &$res=null){

        $header = $this->makeSignData($header);//组合签名

        $res = $this->http($method,$url, $data, $header);
        $result = json_decode( $res, true );
        if(isset($result['data']['trace'])){  unset($result['data']['trace']); }
        return $result;
    }
    public function http( $method='post', $url, $data = null, $header = null){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($curl, CURLOPT_HEADER, 0);//设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//设置获取的信息以文件流的形式返回，而不是直接输出。

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_ENCODING, '');

        if(!empty($header)){
            $curlHeader=[];
            foreach ($header as $k => $v) {
                $curlHeader[] = $k . ": " . $v;
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeader);
        }

        // 判断要执行的 CURL 的请求方式
        $method=strtoupper( $method );
        switch ( $method ) {
            case 'GET':
                curl_setopt( $curl, CURLOPT_HTTPGET, true ); // 设置请求方式为 GET
                break;
            case 'POST':
                curl_setopt( $curl, CURLOPT_POST, true ); // 设置请求方式为 POST
                curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query($data) );// 设置请求体，提交数据包
                break;
            case 'PUT':
                curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );// 设置请求方式为 PUT
                curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query($data) );//设置请求体，提交数据包
                break;
            case 'DELETE':
                curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );// 设置请求方式为 DELETE
                break;
            default:
                echo "不存在请求方式";
                die();
        }

        $output = curl_exec($curl);
        $curlInfo=curl_getinfo($curl);

        curl_close($curl);
        return $output;
    }



//模拟请求

    /*
     * notes: 发起get 请求
     * @author 陈鸿扬 | @date 2021/1/13 18:43
     */
    public function get(string $url, array $params = [], $header = [], &$response){
        $method = 'GET';
        $request = Request::create($url, $method, $params, $header);
        $this->request=$request;
        return $this->exec($response);

    }

    /*
     * notes: 发起post请求
     * @author 陈鸿扬 | @date 2021/1/13 18:43
     */
    public function post(string $url, array $params = [], array $header = [], &$response){
        $method = 'POST';
        $request = Request::create($url, $method, $params, $header);
        $this->request=$request;
        return $this->exec($response);
    }

    /*
     * notes: 发起put请求
     * @author 陈鸿扬 | @date 2021/1/13 18:44
     */
    public function put(string $url, array $params = [], array $header = [], &$response){
        $method = 'PUT';
        $request = Request::create($url, $method, $params, $header);
        $this->request=$request;
        return $this->exec($response);
    }

    /*
     * notes: 发起patch请求
     * @author 陈鸿扬 | @date 2021/1/13 18:44
     */
    public function patch(string $url, array $params = [], array $header = [], &$response){
        $method = 'PATCH';
        $request = Request::create($url, $method, $params, $header);
        $this->request=$request;
        return $this->exec($response);
    }

    /*
     * notes: 发起delete请求
     * @author 陈鸿扬 | @date 2021/1/13 18:45
     */
    public function delete(string $url, array $params = [], $header = [], &$response){
        $method = 'DELETE';
        $request = Request::create($url, $method, $params, $header);
        $this->request=$request;
        return $this->exec($response);
    }

    /*
     * notes: 执行HTTP应用并响应
     * @author 陈鸿扬 | @date 2021/1/13 18:45
     */
    protected function exec(&$response){
        $http=$this->app;
        $this->response=$http->run($this->request);

        $response = $this->response;
        $result = $response->getData();

        //打印预览
        VarDumper::dump($result);

        return $result;
    }


}