<?php

namespace extend\behavior;

use think\Log;

class BlockLine
{
    private static $done = 0; //当前会话周期 执行次数

    //起始线-便于块日志抓取
    public function run(&$params)
    {
        if (!IS_CLI) {
            //应用,不需要加日志分割线
            $log = '[APP_START]';
        } else {
            //控制台,需要加日志分割线
            $line = PHP_EOL . '---------------------------------------------------------------';
            $name = "[ " . date("Y-m-d H:i:s", time()) . ' - ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [CONSOLE_START]';
            $log  = $line . PHP_EOL . $name;
        }

        //AppInit 首次初始化 才记录
        if (self::$done == 0) {
            Log::info($log);
            self::$done += 1;
        }

    }
}