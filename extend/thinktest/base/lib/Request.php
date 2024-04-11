<?php
namespace extend\thinktest\base\lib;

class Request
{
    /**
     * 创建一个URL请求
     * @access public
     * @param string    $uri URL地址
     * @param string    $method 请求类型
     * @param array     $params 请求参数
     * @param array     $header 请求头
     * @param array     $cookie
     * @param array     $files
     * @return \think\Request
     * @throws \think\Exception
     */
    public static function create($uri, $method = 'GET', $params = [],$header=[] ,$cookie = [], $files = [])
    {
        $request = \think\Request::create($uri, $method, $params);

        $request->header = array_change_key_case($header);
        $request->server = $_SERVER;
        $data=$params;

        $uriArr = explode('?',$uri);
        $data['s']=$uriArr[0];

        $map = [];
        if(isset($uriArr[1])){
            $queryList = explode('&',$uriArr[1]);
            if( count($queryList)>0 ){
                foreach ($queryList as $ind=>$val){ $valArr = explode('=',$val);
                    $map[$valArr[0]] = $valArr[1];
                }
            }
        }
        if( !empty($map) ){
            $data= array_merge($data,$map);
            $params= array_merge($params,$map);
        }

        $request->method($method);
        if($method=="GET"){
            $request->get     = $data;
        }
        if($method=="POST"){
            $request->post    = $params;
        }
        if($method=="DELETE"){
            $request->post    = $params;
        }

        $request->param   = $params;
        $request->put     = $params;
        $request->request = $data;
        $request->cookie  = $cookie;
        $request->file    = $files ;
        $request->pathinfo=trim($uriArr[0],'/');

        //print_r($request);

        return $request;
    }

}
