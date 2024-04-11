<?php
/**
 * notes: 链路追踪日志工具
 * @author 陈鸿扬 | @date 2022/5/13 10:53
 */

namespace extend\log;

use think\Log;
use think\Request;

class xTrace
{
    protected static $trace = []; //获取请求头的trace信息 - json文本

    protected static $this; //单例$this

    protected static $request = Request::class; //http/curl/rpc请求
    protected static $remoteIP = '0'; //发起者IP
    protected static $serverIP = '0'; //接收者IP

    protected static $fromSpan = [];
    protected static $nextSpan = [];
    protected static $downSpan = [];

    public function __construct()
    {
        self::$request = Request::instance(); //http/curl/rpc请求

        self::$remoteIP = self::$request->server('REMOTE_ADDR') . ':' . self::$request->server('REMOTE_PORT'); //发起者IP
        self::$serverIP = self::$request->server('SERVER_ADDR') . ':' . self::$request->server('SERVER_PORT'); //接收者IP

        self::$trace = self::getTraceData();
    }

    public static function instance()
    {
        if (!(self::$this instanceof self)) {
            self::$this = new self();
        }
        return self::$this;
    }

    /**
     * notes: 获取当前会话-发起者的trace信息 - 拷贝发起者的信息,提供给后续接收者使用
     * @param array|null $option - 扩展链路信息设置
     * @return array
     * @author 陈鸿扬 | @date 2022/5/13 20:13
     */
    public static function pushFrom(array $option = null)
    {
        self::instance();
        $request = self::$request;

        //获取请求头的trace信息
        $trace = self::$trace;

        $default = ['type' => 'from'];
        if (!empty($option)) {
            $option = array_merge($option, $default);
        } else {
            $option = $default;
        }

        //默认对应服务端请求
        $requestId = $request->header('request-id', '');
        if (!empty($requestId)) {
            //对应前端请求 - 如果前端头部携带request-id,则附加request_id
            $option['request_id'] = $requestId;
        }

        $fromSpan = self::fromSpan($trace, $option);

        self::$fromSpan = $fromSpan;
        return $fromSpan;
    }

    /**
     * notes: 获取当前会话-接收者的trace信息 - 线程外 curl远程接口时, 用这个生成信息, 附加到 header:trace={json文本}
     * @param array|null $option - 扩展链路信息设置
     * @return array
     * @author 陈鸿扬 | @date 2022/5/13 20:13
     */
    public static function pushDown(array $option = null)
    {
        self::instance();

        //没有预先获取 发起者请求信息时 补充执行.
        if (empty(self::$fromSpan)) {
            self::pushFrom($option);
        }

        //获取当前会话-发起者的trace信息
        $trace = self::$fromSpan;

        //有上一个接收者时, 接力trace信息
        if (!empty(self::$downSpan)) {
            $trace = self::$downSpan;
        }

        $default = ['type' => 'down'];
        if (!empty($option)) {
            $option = array_merge($option, $default);
        } else {
            $option = $default;
        }

        $downSpan = self::downSpan($trace, $option);

        self::$downSpan = $downSpan;
        return $downSpan;
    }

    /**
     * notes: 获取当前会话-接收者子线程的trace信息 - 线程内 curl远程接口时, 用这个生成信息, 附加到 header:trace={json文本}
     * @param array|null $option - 扩展链路信息设置
     * @return array
     * @author 陈鸿扬 | @date 2022/5/13 20:16
     */
    public static function pushNext(array $option = null)
    {
        self::instance();

        //获取当前会话-接收者的trace信息
        $trace = self::$downSpan;

        $default = ['type' => 'next'];
        if (!empty($option)) {
            $option = array_merge($option, $default);
        } else {
            $option = $default;
        }

        $nextSpan = self::downSpan($trace, $option);

        self::$nextSpan = $nextSpan;
        return $nextSpan;
    }

    /**
     * notes: curl请求日志
     * @param $url
     * @param array $headers
     * @param array $data
     * @param string $method
     */
    public static function curlRequest($url, $headers = [], $data = [], $method = 'POST')
    {
        //上一级的trace信息
        $downSpan = self::$downSpan;

        //索引数组头部 转字典头部
        $headers = self::headerToMap($headers);

        $curlRequest = [
            '_CURL'   => $url, //curl请求链接
            '_METHOD' => $method, //curl请求动作
            '_HEADER' => $headers, //curl请求头
            '_DATA'   => $data, //curl请求数据
        ];
        unset($curlRequest['_HEADER']['trace']);//排除链路信息

        $logTrace['_CURL_REQUEST'] = $curlRequest;
        $logTrace['_TRACE']        = $downSpan;
        Log::info('[CURL_REQUEST] [X-TRACE] ' . JsonTool::fString($logTrace));
    }

    /**
     * notes: 获取请求信息
     * @param $url
     * @param array $data
     * @param array $headers
     * @param string $method
     * @return array
     */
    public static function makeCurlRequest($url, $data = [], $headers = [], $method = 'POST')
    {
        $request = self::$request;

        $_REQUEST = [
            //请求方
            '_IP'      => $request->ip(),
            '_REFERER' => $request->domain() . $request->url(), //这里拿的是当前接口的路径,作为请求源.
            //被请求方
            '_METHOD'  => $method,
            '_URL'     => $url,
            '_HEADER'  => $headers,
            '_BODY'    => $data,
        ];
        unset($_REQUEST['_HEADER']['trace']);//排除链路信息
        return $_REQUEST;
    }

    /**
     * notes: 获取请求详细信息
     * @return array
     * @author 陈鸿扬 | @date 2022/5/13 20:17
     */
    public static function getRequest()
    {
        $request = self::$request;

        $content = $request->getContent();
        if (!$content && $request->contentType() === 'multipart/form-data') {
            $content = json_encode($request->post());
        }

        $_REQUEST = [
            '_URL'       => $request->domain() . $request->url(),
            '_REFERER'   => $request->server('HTTP_REFERER', ''),
            '_METHOD'    => $request->method(),
            '_IP'        => $request->ip(),
            '_HEADER'    => $request->header(),
            '_QUERY'     => $request->server('REQUEST_URI', ''),
            '_BODY'      => $request->request() ?? '',
            '_FROM_BODY' => $request->post(),
            '_CONTENT'   => $content,
            //'_SERVER'      => $request->server(),
        ];

        //请求数据字节长度,超过1024*256个字符(256k),则省略.
        $maxNum = 1024 * 256;
        $byte   = mb_strlen(serialize((array)$request->param()), '8bit');
        if ($byte > $maxNum) {
            $_REQUEST['_FROM_BODY'] = 'too big data! passed.';
            $_REQUEST['_BODY']      = 'too big data! passed.';
            $_REQUEST['_CONTENT']   = 'too big data! passed.';
        }

        unset($_REQUEST['_HEADER']['trace']);//排除链路信息
        return $_REQUEST;
    }

    /**
     * notes: 获取返回详细信息
     * @param $response
     * @return array
     * @author 陈鸿扬 | @date 2022/5/13 20:18
     */
    public static function getResponse($response)
    {
        $_RESPONSE = [
            '_HEADER'      => $response->getheader(),
            '_STATUS_CODE' => $response->getCode(),
            '_DATA'        => $response->getdata(),
            //'_CONTENT' => $response->getcontent()
        ];

        //返回数据字节长度,超过1024*256个字符(256k),则省略.
        $maxNum = 1024 * 256;
        $byte   = mb_strlen(serialize((array)$response->getdata()), '8bit');
        if ($byte > $maxNum) {
            $_RESPONSE['_DATA'] = 'too big data! passed.';
        }

        unset($_RESPONSE['_HEADER']['trace']);//排除链路信息
        return $_RESPONSE;
    }

    /**
     * notes: curl返回日志
     * @param $url
     * @param $headers
     * @param array $data
     * @param int $statusCode
     */
    public static function curlResponse($url, $headers = [], $data = [], $statusCode = 0)
    {
        //上一级的trace信息
        $downSpan = self::$downSpan;

        //索引数组头部 转字典头部
        $headers = self::headerToMap($headers);

        $getResponse = [
            '_CURL'        => $url, //curl请求链接
            '_STATUS_CODE' => $statusCode, //curl返回状态码
            '_HEADER'      => $headers, //curl返回头
            '_DATA'        => $data, //curl返回数据
        ];

        //返回数据字节长度,超过1024*256个字符(256k),则省略.
        $maxNum = 1024 * 256;
        $byte = mb_strlen(serialize((array)$data), '8bit');
        if ($byte > $maxNum) {
            $getResponse['_DATA'] = 'too big data! passed.';
        }

        $logTrace['_CURL_RESPONSE'] = $getResponse;
        $logTrace['_TRACE']         = $downSpan;
        Log::alert('[CURL_RESPONSE] [X-TRACE] ' . JsonTool::fString($logTrace));
    }


//内部公共函数区域

    //发起者的trace信息 - 前端不传时 根据前端IP 生成一个
    protected static function fromSpan($prevTrace, array $option = null)
    {
        //基本参数
        $span     = [];
        $uuid     = self::uuid();
        $traceId  = $uuid;
        $parentId = '0';
        $spanId   = self::uuid();
        $type     = 'from';

        //$prevTrace
        if (isset($prevTrace['trace_id'])) {
            $traceId = $prevTrace['trace_id'];
        }
        if (isset($prevTrace['parent_id']) && $prevTrace['parent_id'] != 0) {
            $parentId = $prevTrace['span_id'];
        }
        if (isset($prevTrace['span_id'])) {
            $spanId = $prevTrace['span_id'];
        }
        if (isset($prevTrace['type'])) {
            $type = $prevTrace['type'];
        }

        //$option
        if (isset($option['type'])) {
            $type = $option['type'];
        }
        //$option - 如果前端头部携带request-id,则修改trace_id
        if (!empty($option['request_id'])) {
            $traceId = $option['request_id'];
        }

        $span['trace_id']  = $traceId;
        $span['parent_id'] = $parentId;
        $span['span_id']   = $spanId;
        $span['type']      = $type;

        //扩展参数
        $spanOption = [];
        $spanName   = $spanId;
        $spanIP     = self::$remoteIP;
        $spanStart  = self::mSecTime();
        $spanEnd    = self::mSecTime();
        //$prevTrace
        if (isset($prevTrace['option']['span_name'])) {
            $spanName = $prevTrace['option']['span_name'];
        }
        if (isset($prevTrace['option']['span_ip'])) {
            $spanIP = $prevTrace['option']['span_ip'];
        }
        if (isset($prevTrace['option']['span_end'])) {
            $spanStart = $prevTrace['option']['span_end'];
        }
        //$option
        if (isset($option['span_name'])) {
            $spanName = $option['span_name'];
        }
        if (isset($option['span_ip'])) {
            $spanIP = $option['span_ip'];
        }
        $spanOption['span_name']     = $spanName;
        $spanOption['span_ip']       = $spanIP;
        $spanOption['span_start']    = $spanStart;
        $spanOption['span_end']      = $spanEnd;
        $spanOption['span_duration'] = ($spanEnd - $spanStart) / 1000;

        //组合数据
        $span['option'] = $spanOption;

        return $span;
    }

    //根据前一个 trace 生成下一个 trace
    protected static function downSpan($prevTrace, array $option = null)
    {
        //基本参数
        $span     = [];
        $uuid     = self::uuid();
        $traceId  = $uuid;
        $spanId   = $uuid;
        $parentId = '0';
        $type     = 'down';
        //$prevTrace
        if (isset($prevTrace['trace_id'])) {
            $traceId = $prevTrace['trace_id'];
        }
        if (isset($prevTrace['span_id'])) {
            $parentId = $prevTrace['span_id'];
        }
        if (isset($prevTrace['type'])) {
            $type = $prevTrace['type'];
        }
        //$option
        if (isset($option['type'])) {
            $type = $option['type'];
        }
        $span['trace_id']  = $traceId;
        $span['parent_id'] = $parentId;
        $span['span_id']   = $spanId;
        $span['type']      = $type;

        //扩展参数
        $spanOption = [];
        $spanName   = $spanId;
        $spanIP     = self::$serverIP;
        $spanStart  = self::mSecTime();
        $spanEnd    = self::mSecTime();
        //$prevTrace
        if (isset($prevTrace['option']['span_end'])) {
            $spanStart = $prevTrace['option']['span_end'];
        }
        //$option
        if (isset($option['span_name'])) {
            $spanName = $option['span_name'];
        }
        if (isset($option['span_ip'])) {
            $spanIP = $option['span_ip'];
        }
        $spanOption['span_name']     = $spanName;
        $spanOption['span_ip']       = $spanIP;
        $spanOption['span_start']    = $spanStart;
        $spanOption['span_end']      = $spanEnd;
        $spanOption['span_duration'] = ($spanEnd - $spanStart) / 1000;

        //组合数据
        $span['option'] = $spanOption;

        return $span;
    }

    protected static function uuid($prefix = "")
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars, 0, 8) . '_'
            . substr($chars, 8, 4) . '_'
            . substr($chars, 12, 4) . '_'
            . substr($chars, 16, 4) . '_'
            . substr($chars, 20, 12);
        return $prefix . $uuid;
    }

    protected static function mSecTime()
    {
        $microTimeArr = explode(' ', microtime());
        $mSec         = $microTimeArr[0];
        $sec          = $microTimeArr[1];
        $mSecTime     = sprintf('%.0f', (floatval($mSec) + floatval($sec)) * 1000);
        return $mSecTime;
    }

    protected static function getTraceData($trace = 'trace')
    {
        $traceStr  = self::getHeaderParam($trace, '');
        $traceData = json_decode(urldecode($traceStr), true);
        return $traceData;
    }

    protected static function getHeaderParam($key, $def = '')
    {
        $value = self::$request->header($key, $def);
        if (empty($value)) {
            if (isset($_GET["$key"])) {
                $value = (string)$_GET["$key"];
            }
        }
        return $value;
    }

    protected static function getServerParam($key, $def = '')
    {
        $value = self::$request->server($key, $def);
        return $value;
    }

    //索引数组头部 转字典头部
    protected static function headerToMap($headers = [])
    {
        //字典头部
        $headersTemp = [];
        if (!empty($headers)) {
            $headerKeys = array_keys($headers);

            if (isset($headerKeys[0]) && gettype($headerKeys[0]) == 'integer') {
                array_walk($headers, function ($item) use (&$headersTemp) {
                    $itemArr = explode(":", $item);
                    $key     = $itemArr[0] ?? null;
                    $value   = $itemArr[1] ?? '';
                    if (!empty($key)) {
                        $headersTemp[$key] = $value;
                    }
                });
                $headers = $headersTemp;
            }

        }
        return $headers;
    }

//#

}