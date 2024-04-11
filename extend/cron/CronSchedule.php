<?php

namespace extend\cron;

use Cron\CronExpression;

/**
 * notes: CronExpression 包装类
 * @author 陈鸿扬 | @date 2021/6/23 12:04
 * Class CronSchedule
 * @package extend\cron
 */
class CronSchedule
{
    protected $phpFpm; //php_fpm 路径指令
    protected $rootFolder; //项目目录

    protected $taskGroup; //待执行命令
    protected $expiresGroup; //防重复执行命令

    protected $currCommand; //当前命令
    protected $currCron; //当前命令

    public $currLog = ''; //堆日志

    //初始化
    public function __construct(array $option = [])
    {
        if (isset($option['php_fpm'])) {
            $this->phpFpm = $option['php_fpm'];
        }
        if (isset($option['root_folder'])) {
            $this->rootFolder = $option['root_folder'];
        }
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

    //收集 cmd 命令
    public function command($cmdString)
    {
        $this->currCommand                    = $cmdString;
        $this->taskGroup["$cmdString"]['cmd'] = rtrim($cmdString);
        return $this;
    }

    //收集 crond 时间
    public function cron($cronString)
    {
        $this->currCron                                = $cronString;
        $this->taskGroup["$this->currCommand"]['cron'] = $cronString;
        return $this;
    }

    //防止重复执行配置
    public function withoutOverlapping($expiresAt = 1440 * 60)
    {
        $this->taskGroup["$this->currCommand"]['expires_at'] = $expiresAt;
        $this->expiresGroup["$this->currCommand"]            = $this->taskGroup["$this->currCommand"];
        return $this;
    }

    /**
     * notes: 获取命令行相关进程信息
     * @param $folder - 项目根目录-绝对路径
     * @param $cmd - 调度命令文本
     * @return bool - 是否存在相同进程: true-存在,false-不存在
     * @author 陈鸿扬 | @date 2022/5/5 19:22
     */
    public function getProcessByCmd($cmd, $folder)
    {
        //拼接grep 关键字
        $grepStr = '';
        $grepStr .= " |grep '" . $folder . "'";
        $grepStr .= " |grep '" . $cmd . "'";

        if (!self::isWin()) {
            $cmdString = "ps -ef" . $grepStr . "";
            $result    = shell_exec($cmdString);

            return $this->cmdOsFilter($result, $cmd, $folder);
        } else {
            //windows暂时不处理
            $cmdString = "ps -ef" . $grepStr . "";
            $result    = null;
            return false;
        }
    }

    /**
     * notes: OS平台命令行返回信息清洗
     * @param $result - 进程返回信息文本
     * @param $cmd - 调度命令文本
     * @param $folder - 项目根目录-绝对路径
     * @return bool - 是否存在相同进程: true-存在,false-不存在
     * @author 陈鸿扬 | @date 2022/5/5 19:24
     */
    protected function cmdOsFilter($result, $cmd, $folder)
    {
        if (!empty($result)) {

            $group = explode(PHP_EOL, trim($result, ' ' . PHP_EOL . ' '));
            $temp  = [];
            array_walk($group, function ($item, $index) use ($folder, &$temp) {
                //清洗命令行数据
                $item = trim(preg_replace("/\s+/", ' ', $item), ' ');
                //排除自身
                preg_match("/grep/", $item, $match);
                if (!isset($match[0])) {
                    //清除项目路径
                    //截取目录后内容
                    $item = preg_replace("/.*" . $folder . "(.*$)/i", '$1', $item);
                    //清理其它符号和空格
                    $item = str_replace("&", '', $item);
                    $item = trim($item, ' ');
                    //
                    $temp[] = $item;
                }
            });

            if (in_array($cmd, $temp)) {
                //var_dump('cmdOsFilter',$cmd,$temp);//
                return true;
            }
        }
        return false;
    }

    //最终执行
    public function runBeforeLog()
    {
        if (!empty($this->taskGroup)) {
            foreach ($this->taskGroup as $key => $group) {
                $status = "running";
                //对防重复执行设置做判断 - 重复则跳过
                if (isset($group['expires_at'])) {
                    $expiresCmd = $this->getProcessByCmd($group['cmd'], $this->rootFolder);
                    if ($expiresCmd) {
                        $status        = 'pass';
                        $output        = 'Process already exists';
                        $currLog       = $this->makeLog($group, $status, $output);
                        $this->currLog .= $currLog;
                        //输出控制台
                        echo $currLog;
                        //上次执行行还未结束,本次跳过.
                        continue;
                    }
                }

                //首次执行
                if (isset($group['cron'])) {
                    //获取执行区间 - 分钟单位
                    $cron  = CronExpression::factory($group['cron']);
                    $isDue = $cron->isDue();
                    if ($isDue) {
                        $currLog       = $this->makeLog($group, $status);
                        $this->currLog .= $currLog;
                        //输出控制台
                        echo $currLog;
                    }
                }
            }
        }
    }

    //最终执行
    public function run()
    {
        if (!empty($this->taskGroup)) {
            foreach ($this->taskGroup as $key => $group) {
                //对防重复执行设置做判断 - 重复则跳过
                if (isset($group['expires_at'])) {
                    $expiresCmd = $this->getProcessByCmd($group['cmd'], $this->rootFolder);
                    if ($expiresCmd) {
                        //上次执行行还未结束,本次跳过.
                        continue;
                    }
                }
                //首次执行
                if (isset($group['cron'])) {
                    //获取执行区间 - 分钟单位
                    $cron  = CronExpression::factory($group['cron']);
                    $isDue = $cron->isDue();
                    if ($isDue) {
                        (new CronConsole())->pcmd($group['cmd']);
                    }
                }
            }
        }
        $this->taskGroup = null;
    }

    //获取日志
    public function makeLog($group, $status = '', $output = '')
    {
        $currLog = [
            'name'   => 'cron_schedule',
            'time'   => date('Y-m-d H:i:s', time()),
            'cmd'    => $group['cmd'],
            'cron'   => $group['cron'],
            'status' => $status,
            'output' => (string)$output
        ];
        //转成控制台格式 文本
        $currLog = CronConsole::group($currLog, true);
        return $currLog;
    }

    //以下为单独使用的场景

    /**
     * notes: 判断cronTab 设置时间 在不在当前执行时间内
     * @author 陈鸿扬 | @date 2021/6/27 19:59
     * @param $cronExp
     * @return bool
     */
    public static function checkTimeOn($cronExp)
    {
        $cron = CronExpression::factory($cronExp);
        return $cron->isDue();
    }

}