<?php
/* Created by User: soma Worker:陈鸿扬  Date: 2018/3/18  Time: 18:54 */

namespace app;

use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ErrorException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Config;
use think\Log;
use think\Request;
use Throwable;

class ExceptionHandle extends Handle
{

    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
//        //块日志-结束分割线
//        $log  = "[ " . date("Y-m-d H:i:s", time()) . " | " . getmypid() . " ] " . '[APP_END] ' . PHP_EOL;
//        $line = $log . '---------------------------------------------------------------';
//        Log::alert($line);
    }

    public function render(Exception $e)
    {

        $msg = [
            "error"   => $e->getMessage(),
            "message" => $e->getMessage(),
            "code"    => $e->getCode()
        ];

        // 参数验证错误
        if ($e instanceof ValidateException) {
            $msg['error'] = $e->getError();
            $msg          = $this->isDebugMsg($e, $msg);
            return json($msg, 422);
        }
        //http-ajax请求异常
        if ($e instanceof HttpException && request()->isAjax()) {
            $msg['code'] = $e->getStatusCode();
            $msg         = $this->isDebugMsg($e, $msg);
            return json($msg, 400);
        }
        //http请求异常
        if ($e instanceof HttpException) {
            $msg['code'] = $e->getStatusCode();
            $msg         = $this->isDebugMsg($e, $msg);
            return json($msg, 400);
        }
        //抛错误
        if ($e instanceof ErrorException) {
            $msg = $this->isDebugMsg($e, $msg);
            return json($msg, 400);
        }
        //抛异常
        if ($e instanceof Exception) {
            $msg = $this->isDebugMsg($e, $msg);
            return json($msg, 400);
        }
        //#

        //详细的异常日志
        $this->setErrorLog($e);

        //其他错误 默认数据结构
        $msg = $this->isDebugMsg($e, $msg);
        return json($msg, 400);

        // 其他错误交给系统处理
        //return parent::render($e);
    }


    public function isDebugMsg($e, $msg)
    {
        $debug = Config::get('app_debug');

        //防止非utf8编码的描述文本
        $msg['message'] = utf8_encode($msg['message']);
        $msg['error']   = utf8_encode($msg['error']);

        //生产环境,不显示异常细节信息.
        if ($debug) {
            $msg['file'] = $e->getFile();
            $msg['line'] = $e->getLine();
            $trace       = $e->getTrace();
            if (isset($trace[0])) {
                $msg['trace'][] = $trace[0];
            }
            if (isset($trace[1])) {
                $msg['trace'][] = $trace[1];
            }
            if (isset($trace[2])) {
                $msg['trace'][] = $trace[2];
            }
        }

        //生产环境 过滤 sql 错误信息
        if (!$debug) {
            $this->sqlExceptionFilter($msg);
        }

        return $msg;
    }

    //生产环境 过滤 sql 错误信息
    public function sqlExceptionFilter(&$msg)
    {

        //例子: 生产模式时 执行替换
        //将 "SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed:"
        //替换成 "SQLSTATE[HY000] [2002]"
        if (isset($msg['message'])) {
            preg_match('/SQLSTATE(\[.*\])/is', $msg['message'], $m);
            if (!empty($m[0])) {
                $msg['message'] = $m[0];
                $msg['error']   = $m[0];
            }
        }

        return $msg;
    }

    //详细的异常日志
    public function setErrorLog($e)
    {
        //获取请求
        $request = Request::instance();
        $params  = [
            'url'    => $request->domain() . $request->url(),
            'method' => $request->method(),
            'header' => $request->header()
        ];
        switch ($request->method()) {
            case 'GET':
                $params['get'] = $request->get();
                break;
            case 'POST':
                $params['post'] = $request->post();
                break;
            case 'PUT':
                $params['put'] = $request->put();
                break;
            case 'DELETE':
                $params['delete'] = $request->delete();
                break;
            case 'PATCH':
                $params['patch'] = $request->patch();
                break;
            default:
                $params[strtolower($request->method())] = $request->put();
                break;
        }
        $requestInfo = 'Request info : ' . json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\r";
        $errorFile   = "Exception file: " . $e->getFile() . ";line[{$e->getLine()}]";
        Log::log("$requestInfo$errorFile");
    }

}