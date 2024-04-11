<?php

namespace extend\log;

use think\Log;

/**
 * 格式化日志
 * Class formatLog
 * @package extend\log
 */
class fLog
{
    public static function method($data, $method = '', $line = '')
    {
        $methodStr = $method;
        if (!empty($line)) {
            $methodStr .= ":" . $line;
        }
        if (!empty($methodStr)) {
            $methodStr = "[" . $methodStr . "]";
        }
        Log::alert("[ METHOD ] " . $methodStr . PHP_EOL . JsonTool::fString($data));
    }

    public static function methodError($data, $method = '', $line = '')
    {
        $methodStr = $method;
        if (!empty($line)) {
            $methodStr .= ":" . $line;
        }
        if (!empty($methodStr)) {
            $methodStr = "[" . $methodStr . "]";
        }
        Log::error("[ METHOD ] " . $methodStr . PHP_EOL . JsonTool::fString($data));
    }

}