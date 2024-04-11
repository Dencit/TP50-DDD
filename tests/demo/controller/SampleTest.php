<?php
namespace tests\demo\controller;

use extend\thinktest\base\api\ApiToDoc;
use extend\thinktest\base\api\BaseApiTest;
use PHPUnit\Framework\TestResult;
use extend\thinktest\base\api\TableToDoc;
use think\Config;
use think\Db;

class SampleTest extends BaseApiTest{

    protected $mainTable = 'samples';
    protected $baseHost;
    protected $stack;

    protected $userToken;
    protected $adminToken;
    protected $formToken;

    //基境初始化
    public static function setUpBeforeClass():void{
    }
    protected function setUp():void{
        $this->stack = [];
        $this->baseHost = Config::get('app_host');

        //$this->userToken = $this->getUserToken()->data->token;
        //$this->adminToken = $this->getAdminToken()->data->token;
        //$this->formToken = $this->getFormToken( $this->adminToken )->data->__token__;
    }
    public static function tearDownAfterClass():void{
    }
    protected function tearDown():void{
        $this->stack = [];
        $this->baseHost = null;

        $this->userToken = null;
        $this->adminToken = null;
        $this->formToken = null;
    }
    //#

    //测试用例
    /**
     * 生产者
     */
    //{@hidden
    public function testSampleUrlCreate(){

        $uri = '/demo/sample/add';
        $url = $this->baseHost.$uri;

        //$formToken = $this->formToken;
        $data = [
            //@in_data
            "name" => "create新增",
            "nick_name" => "",
            "mobile" => "",
            "photo" => "",
            "sex" => 0,
            "type" => 0,
            "status" => 0,
            //@in_data
        ];
        $header=[
            'token'=> $this->userToken
        ];
        //$result = $this->httpRequest('post',$url,$data,$header,$res); //网络请求
        $result=$this->post($uri,$data,$header,$response); //模拟请求

        //生成文档
        $doc = new ApiToDoc("demo","sample","create");
        $doc->setMethod('post')->setUri($uri)->setData($data,$header)->setResponse($result)->write();

        if( !empty($result) ){
            $result = Db::name( $this->mainTable )->where( 'create_time','<>','null' )->order('id','desc')->find();
            $this->assertArrayHasKey('id',$result);
            //缓存入库数据id,待测试完清除
            $this->tablePushTempId( $this->mainTable, $result['id'] );
            return $result;
        }

        return null;
    }
    //@hidden}

    /**
     * 消费者 依赖 depends
     * @depends testSampleUrlCreate
     * @param $data
     */
    //{@hidden
    public function testSampleUrlUpdate($data){

        $uri = '/demo/sample'.'/'.$data['id']; $query = '';
        $url = $this->baseHost.$uri.$query;
        //$formToken = $this->formToken;
        $data = [
            //@up_data
            "name" => "update更新",
            "nick_name" => "",
            "mobile" => "",
            "photo" => "",
            "sex" => 0,
            "type" => 0,
            "status" => 0,
            //@up_data
        ];
        $header=[
            'token'=> $this->userToken
        ];
        //$result = $this->httpRequest('put',$url,$data,$header,$res);//网络请求
        $result = $this->put($uri.$query, $data, $header, $response); //模拟请求


        //生成文档
        $doc = new ApiToDoc("demo","sample","update");
        $doc->setMethod('put')->setUri($uri,$query)->setData($data,$header)->setResponse($result)->write();

        $this->assertTrue( !empty($result) );
    }
    //@hidden}

    /**
     * 消费者 依赖 depends
     * @depends testSampleUrlCreate
     * @param $data
     * @return array|null|\think\Model
     */
    //{@hidden
    public function testSampleUrlDetail($data){

        $uri = '/demo/sample/list'; $query='?_time=1';
        $url = $this->baseHost.$uri;

        $data = [];
        $header=[
            'token'=> $this->userToken
        ];
        //$result = $this->httpRequest('get',$url,$data,$header);//网络请求
        $result=$this->get($uri.$query,$data,$header,$response); //模拟请求

        //生成文档
        $doc = new ApiToDoc("demo","sample","detail");
        $doc->setMethod('get')->setUri($uri,$query)->setData($data,$header)->setResponse($result)->write();

        $this->assertTrue( !empty($result) );

        return $result;
    }
    //@hidden}

    /**
     * 生产者
     */
    //{@hidden
    public function testSampleUrlCollect(){

        $uri = '/demo/sample/list'; $query='?_time=1';
        $url = $this->baseHost.$uri;

        $data = [];
        $header=[
            'token'=> $this->userToken
        ];
        //$result = $this->httpRequest('get',$url,$data,$header);//网络请求
        $result=$this->get($uri.$query,$data,$header,$response); //模拟请求

        //生成文档
        $doc = new ApiToDoc("demo","sample","collect");
        $doc->setMethod('get')->setUri($uri,$query)->setData($data,$header)->setResponse($result)->write();

        $this->assertTrue( !empty($result) );

        return $result;
    }
    //@hidden}

    /**
     * 消费者 依赖 depends
     * @depends testSampleUrlCreate
     * @param $data
     * @return mixed
     */
    //{@hidden
    public function testSampleUrlDelete($data){

        $uri = '/demo/sample'.'/'.$data['id']; $query = '';
        $url = $this->baseHost.$uri.$query;

        $data = [];
        $header=[
            'token'=> $this->userToken
        ];
        //$result = $this->httpRequest('delete',$url,$data,$header);//网络请求
        $result=$this->delete($uri.$query,$data,$header,$response); //模拟请求

        //生成文档
        $doc = new ApiToDoc("demo","sample","delete");
        $doc->setMethod('delete')->setUri($uri,$query)->setData($data,$header)->setResponse($result)->write();

        $this->assertTrue( $result['data'] );
        return $result;
    }
    //@hidden}

    /**
     * 结束测试
     */
    public function testEnd(){
        $this->tableCleanByArgv($this->mainTable,'clean');

        //生成文档
        ( new TableToDoc("demo","sample","table") )-> write();

        $this->assertTrue(true);
    }

    //抽象类继承 必须实现方法
    public function count(): int{
        return parent::count();
    }
    public function toString(): string{
        return parent::toString();
    }
    public function run(TestResult $result = null): TestResult{
        return parent::run($result);
    }

}