<?php
/**
 * notes: 性能跟踪工具
 * @author 陈鸿扬 | @date 2021/4/1 9:57
 */

namespace extend\utils;

use think\Log;

class TraceTool
{
    //内存峰值
    protected static $memoryPeak;

    //in节点消耗内存
    protected static $in;
    //in节点时间
    protected static $inTime;
    //in节点日期
    protected static $inDateTime;
    //in节点内存消耗
    protected static $inMemoryUse;

    //out节点消耗内存
    protected static $out;
    //out节点时间
    protected static $outTime;
    //out节点日期
    protected static $outDateTime;
    //out节点内存消耗
    protected static $outMemoryUse;

    //日志描述
    protected static $desc = '';

    /**
     * notes: 获取节点消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:03
     * @param $node
     * @param string $desc
     */
    public static function memory($node, $desc = '')
    {
        switch ($node) {
            default:
                self::memoryIn($desc);
                break;
            case 'in' :
                self::memoryIn($desc);
                break;
            case 'out' :
                self::memoryOut($desc);
                break;
        }
    }

    /**
     * notes: 获取 in节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @param string $desc
     * @return string
     */
    public static function memoryIn($desc = '输出')
    {
        self::$desc       = $desc;
        self::$in         = memory_get_usage();
        self::$inTime     = number_format(microtime(true), 10, '.', '');
        self::$inDateTime = date('Y-m-d H:i:s') . ' | ' . self::$inTime . ' s';

        self::$inMemoryUse = round(self::$in / 1024 / 1024, 4) . ' MB';

        $info = "# 执行开始" . PHP_EOL .
            "> 任务名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL;

        echo $info . PHP_EOL;
        return $info;
    }


    /**
     * notes: 获取 out节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @return string
     */
    public static function memoryOut($desc = '输出')
    {
        if ($desc != '输出') {
            self::$desc = $desc;
        }
        self::$out = memory_get_usage();

        self::$outTime     = number_format(microtime(true), 10, '.', '');
        self::$outDateTime = date('Y-m-d H:i:s') . ' | ' . self::$outTime . ' s';
        self::$memoryPeak  = memory_get_peak_usage(true);

        self::$outMemoryUse = round(self::$out / 1024 / 1024, 4) . ' MB';
        $memoryPeakSet      = round((self::$memoryPeak) / 1024 / 1024, 4) . ' MB';
        $useMemory          = floatval($memoryPeakSet) - floatval(self::$inMemoryUse) . ' MB';

        $info = PHP_EOL . "# 执行完成" . PHP_EOL .
            "> 任务名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL .
            "> 结束时间: " . self::$outDateTime . " | 结束内存: " . self::$outMemoryUse . PHP_EOL .
            "> 消耗时间: " . (self::$outTime - self::$inTime) . " | 消耗内存: " . $useMemory . '' . PHP_EOL .
            "> 峰    值: " . $memoryPeakSet . '' . PHP_EOL;

        echo $info;
        return $info;
    }


    /**
     * notes: 获取 in节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @param string $desc
     * @return string
     */
    public static function queueInLog($desc = '输出')
    {
        self::$desc       = $desc;
        self::$in         = memory_get_usage();
        self::$inTime     = number_format(microtime(true), 10, '.', '');
        self::$inDateTime = date('Y-m-d H:i:s') . ' | ' . self::$inTime . ' s';

        self::$inMemoryUse = round(self::$in / 1024 / 1024, 4) . ' MB';

        //控制台,需要加日志分割线
        $line     = '---------------------------------------------------------------' . PHP_EOL;
        $name     = "[ " . date("Y-m-d H:i:s", time()) . ' - ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [QUEUE_START]' . PHP_EOL;
        $info     = "# 执行开始" . PHP_EOL .
            "> 队列名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL;
        $queueLog = PHP_EOL . $line . $name . $info;

        Log::info($queueLog);
        return $queueLog;
    }


    /**
     * notes: 获取 out节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @return string
     */
    public static function queueOutLog($desc = '输出')
    {
        if ($desc != '输出') {
            self::$desc = $desc;
        }
        self::$out = memory_get_usage();

        self::$outTime     = number_format(microtime(true), 10, '.', '');
        self::$outDateTime = date('Y-m-d H:i:s') . ' | ' . self::$outTime . ' s';
        self::$memoryPeak  = memory_get_peak_usage(true);

        self::$outMemoryUse = round(self::$out / 1024 / 1024, 4) . ' MB';
        $memoryPeakSet      = round((self::$memoryPeak) / 1024 / 1024, 4) . ' MB';
        $useMemory          = floatval($memoryPeakSet) - floatval(self::$inMemoryUse) . ' MB';

        $info = PHP_EOL . "# 执行完成" . PHP_EOL .
            "> 队列名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL .
            "> 结束时间: " . self::$outDateTime . " | 结束内存: " . self::$outMemoryUse . PHP_EOL .
            "> 消耗时间: " . (self::$outTime - self::$inTime) . " | 消耗内存: " . $useMemory . '' . PHP_EOL .
            "> 峰    值: " . $memoryPeakSet . '' . PHP_EOL;
        //控制台,结束也要加日志分割线,防止异步进程日志进来.
        $name     = "[ " . date("Y-m-d H:i:s", time()) . ' - ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [ QUEUE_END ]' . PHP_EOL;
        $line     = '---------------------------------------------------------------';
        $queueLog = $info . PHP_EOL . $name . $line;
        Log::alert($queueLog);
        return $queueLog;
    }


    /**
     * notes: 获取 in节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @param string $desc
     * @return string
     */
    public static function commandInLog($desc = '输出')
    {
        self::$desc       = $desc;
        self::$in         = memory_get_usage();
        self::$inTime     = number_format(microtime(true), 10, '.', '');
        self::$inDateTime = date('Y-m-d H:i:s') . ' | ' . self::$inTime . ' s';

        self::$inMemoryUse = round(self::$in / 1024 / 1024, 4) . ' MB';

        //控制台,需要加日志分割线
        $line = '---------------------------------------------------------------';
        $name = "[ " . date("Y-m-d H:i:s", time()) . ' - ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [COMMAND_START]';
        $info = "# 执行开始" . PHP_EOL .
            "> 任务名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL;
        $info = PHP_EOL . $line . PHP_EOL . $name . PHP_EOL . $info;

        Log::info($info);
        return $info;
    }


    /**
     * notes: 获取 out节点 消耗内存
     * @author 陈鸿扬 | @date 2021/4/1 10:04
     * @return string
     */
    public static function commandOutLog($desc = '输出')
    {
        if ($desc != '输出') {
            self::$desc = $desc;
        }
        self::$out = memory_get_usage();

        self::$outTime     = number_format(microtime(true), 10, '.', '');
        self::$outDateTime = date('Y-m-d H:i:s') . ' | ' . self::$outTime . ' s';
        self::$memoryPeak  = memory_get_peak_usage(true);

        self::$outMemoryUse = round(self::$out / 1024 / 1024, 4) . ' MB';
        $memoryPeakSet      = round((self::$memoryPeak) / 1024 / 1024, 4) . ' MB';
        $useMemory          = floatval($memoryPeakSet) - floatval(self::$inMemoryUse) . ' MB';

        $info = "# 执行完成" . PHP_EOL .
            "> 任务名称: " . self::$desc . PHP_EOL .
            "> 开始时间: " . self::$inDateTime . " | 开始内存: " . self::$inMemoryUse . PHP_EOL .
            "> 结束时间: " . self::$outDateTime . " | 结束内存: " . self::$outMemoryUse . PHP_EOL .
            "> 消耗时间: " . (self::$outTime - self::$inTime) . " | 消耗内存: " . $useMemory . '' . PHP_EOL .
            "> 峰    值: " . $memoryPeakSet . PHP_EOL;
        //控制台,结束也要加日志分割线,防止异步进程日志进来.
        $name = "[ " . date("Y-m-d H:i:s", time()) . ' | ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [COMMAND_END]' . PHP_EOL;
        $line = '---------------------------------------------------------------';
        $info = $info . PHP_EOL . $name . $line;

        Log::info(PHP_EOL . $info);
        return $info;
    }

}
