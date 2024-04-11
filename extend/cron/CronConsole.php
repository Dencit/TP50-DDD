<?php

namespace extend\cron;

use think\Log;

/**
 * notes: 调度器控制的台-输出格式化
 * @author 陈鸿扬 | @date 2021/6/29 9:32
 */
class CronConsole
{

    /**
     * notes: 控制台输出文本格式化 - 打包输出
     * @author 陈鸿扬 | @date 2021/6/29 10:17
     * @param array $group map数组
     * @param bool $compact 紧凑格式 默认否
     * @return string
     */
    public static function group(array $group, $compact = false)
    {
        $str = "\n";
        foreach ($group as $name => $data) {
            $str .= "# " . $name . " >";
            if (gettype($data) == "string") {
                $str .= " " . $data . "\n";
            }
            if (gettype($data) == "array") {
                $string = implode("\n  ", $data);
                $str    .= "\n  " . $string;
            }
            if (!$compact) {
                $str .= "\n";
            }
        }
        return $str;
    }

    /**
     * notes: 控制台输出文本格式化 - 逐条输出
     * @author 陈鸿扬 | @date 2021/6/29 10:16
     * @param string $name 键
     * @param string $data 值
     * @param bool $compact 紧凑格式 默认否
     * @return string
     */
    public static function single(string $name, string $data, $compact = false)
    {
        $str = "\n";
        $str .= "# " . $name . " >";
        if (gettype($data) == "string") {
            $str .= " " . $data . "\n";
        }
        if (gettype($data) == "array") {
            $string = implode("\n   ", $data);
            $str    .= "   " . $string;
        }
        if (!$compact) {
            $str .= "\n";
        }
        return $str;
    }

    /**
     * notes:平台判断
     * @author 陈鸿扬 | @date 2021/6/29 9:18
     * @return bool
     */
    public static function isWin()
    {
        $isWin = (DIRECTORY_SEPARATOR == '\\') ? true : false;
        return $isWin;
    }

    /**
     * notes: 执行命令 - 堵塞型
     * @author 陈鸿扬 | @date 2021/7/2 12:43
     * @param $cmdString
     * @return string
     * @throws \Exception
     */
    public static function cmd(&$cmdString)
    {
        //非windows系统 - cronTab命令在系统根目录执行, 执行命令前,必须进入项目根目录
        if (!self::isWin()) {
            $cmdString = 'cd ' . root_path() . ' && ' . $cmdString . ' > /dev/null 2>&1';
        } else {
            $cmdString = $cmdString . ' 2>&1';
        }
        try {
            $result = shell_exec($cmdString);
        } catch (\Exception $e) {
            Log::error($e->getTrace());
            throw $e;
        }
        return $result;
    }

    /**
     * notes: 执行命令 - 独立线程版
     * @author 陈鸿扬 | @date 2021/7/2 12:43
     * @param $cmdString
     * @return int
     * @throws \Exception
     */
    public static function pcmd(&$cmdString)
    {
        //非windows系统 - cronTab命令在系统根目录执行, 执行命令前,必须进入项目根目录
        if (!self::isWin()) {
            $cmdString = 'cd ' . root_path() . ' && ' . $cmdString . ' &';
        } else {
            $cmdString = 'start ' . $cmdString;
        }
        try {
            $result = pclose(popen($cmdString, "w"));
        } catch (\Exception $e) {
            Log::error($e->getTrace());
            throw $e;
        }
        return $result;
    }

    /**
     * notes: 通过进程关键字删除进程
     * @author 陈鸿扬 | @date 2021/7/2 12:42
     * @param $arr
     * @return string
     * @throws \Exception
     */
    public static function killProcessByKeyword($arr)
    {
        //拼接grep 关键字
        $grepStr = '';
        foreach ($arr as $ind => $val) {
            $grepStr .= '|grep ' . $val;
        }

        if (!self::isWin()) {
            $cmdString = "ps -ef" . $grepStr . "|awk '{print $2}'|xargs kill -9";
        } else {
            //windows暂时不处理
            $cmdString = "ps -ef" . $grepStr . "|awk '{print $2}'|xargs kill -9";
        }
        var_dump($cmdString);

        try {
            $result = shell_exec($cmdString);
            self::single("CMD", $cmdString);
        } catch (\Exception $e) {
            Log::error($e->getTrace());
            throw $e;
        }
        return $result;
    }

    /**
     * notes: 获取项目根目录
     * @author 陈鸿扬 | @date 2021/7/2 14:39
     * @param bool $path
     * @return bool|mixed|null|string|string[]
     */
    public static function rootFolder($path = true)
    {
        $folder = root_path();
        if ($path) {
            $folder = str_replace("/", "\\/", $folder);
        } else {
            $folder = preg_replace("/.*\\/(\w+\S+)$/i", '$1', $folder);
        }
        return $folder;
    }

}