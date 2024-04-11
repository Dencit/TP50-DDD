<?php

namespace extend\behavior;

use extend\log\JsonTool;
use extend\log\xTrace;
use think\Log;
use think\Response;

class ResponseLog
{
    /**
     * @param $response Response
     */
    public function run(&$response)
    {
        if (!IS_CLI) {
            //链路日志
            $xTrace                = xTrace::instance();
            $downSpan              = $xTrace->pushDown(['span_name' => 'backend.php.tp50']);
            $getResponse           = $xTrace->getResponse($response);
            $logTrace['_RESPONSE'] = $getResponse;
            $logTrace['_TRACE']    = $downSpan;

            //返回给前端request_id,形成链路闭环.
            $data    = $response->getData();
            $request = ['request-id' => $downSpan['trace_id'] ?? ''];
            $data    = array_merge($request, $data);
            $response->data($data);

            Log::alert('[RESPONSE_END] [X-TRACE] ' . PHP_EOL . JsonTool::fString($logTrace));
        }

    }
}