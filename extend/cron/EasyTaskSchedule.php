<?php

namespace extend\cron;

use EasyTask\Task;

/**
 * notes: EasyTask 包装类
 * @author 陈鸿扬 | @date 2021/6/23 12:04
 * Class EasyTaskSchedule
 * @package extend\cron
 */
class EasyTaskSchedule
{
    protected $task; //实例

    protected $prefix; //项目名称

    public function __construct($prefix)
    {
        $this->prefix = $prefix;

        //实例化
        $task = new Task();
        //$task->setDaemon(true); //设置常驻内存 - 后台运行; 注意: supervisor维护时不需要,会获取不到进程.
        $task->setPrefix($prefix); //设置项目名称 - 不同项目要区分名称
        $task->setTimeZone('Asia/Shanghai'); //设置系统时区
        $task->setAutoRecover(true); // 设置子进程挂掉自动重启
        $task->setRunTimePath('runtime');//运行缓存或日志路径

        //设置实例
        $this->task = $task;
    }

    /**
     * 新增指令作为任务
     * @param string $command 指令
     * @param string $alas 任务别名
     * @param mixed $time 定时器间隔
     * @param int $used 定时器占用进程数
     * @return $this
     */
    public function addCommand($command, $alas, $time = 1, $used = 1)
    {
        $this->task->addCommand($command, $alas, $time, $used);
    }

    //执行
    public function run($command, $force)
    {
        $result = 'exist';
        if ($command == 'start') {
            $result = 'start';//重复执行时,需要返回信息

            $this->cleanProcess();//清理遗留进程

            $this->task->start();
        } elseif ($command == 'status') {
            $result = 'status'; //获取状态
            $this->task->status();
        } elseif ($command == 'stop') {
            $result = 'stop';
            $force  = ($force == 'force'); //是否强制停止
            if ($force) {

                $this->cleanProcess();//清理遗留进程

            }
            $this->task->stop($force);
        } elseif ($command == 'reset') {
            $result = 'reset'; //重启

            $this->cleanProcess();//清理遗留进程

            $this->task->stop(true);
            $this->task->start();
        }
        return $result;
    }

    /**
     * notes: 清理遗留进程 - 避免supervisor杀主进程,漏了子进程
     * @author 陈鸿扬 | @date 2021/7/2 18:55
     * @throws \Exception
     */
    protected function cleanProcess()
    {
        $opt = [CronConsole::rootFolder(), $this->prefix];
        CronConsole::killProcessByKeyword($opt);
        $opt[] = 'queue';
        CronConsole::killProcessByKeyword($opt);
    }


}