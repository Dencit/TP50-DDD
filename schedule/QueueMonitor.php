<?php
namespace schedule;

use extend\cron\BaseQueueMonitorCommand;
use think\console\Input;
use think\console\Output;

/**
 * 执行命令: php think queue_monitor  启动
 */

/**
 * notes: 队列监控 - 用crontab调度 1分钟执行一次
 * @author 陈鸿扬 | @date 2021/6/21 14:39
 * Class QueueMonitor
 * @package schedule
 */
class QueueMonitor extends BaseQueueMonitorCommand
{
    protected function execute(Input $input, Output $output){
        //获取php控制台执行程序
        $phpFpm = $this->php;

        //获取php所有线程
        $phpProcess = $this->phpProcess($phpFpm,'tp50');

        //队列模板
        $this->phpProcessMatch(sprintf('%s think queue:work --daemon  --queue QueueTestJob', $phpFpm),$phpProcess);

        //框架默认队列
        $this->phpProcessMatch(sprintf('%s think queue:listen', $phpFpm),$phpProcess);


        //指令输出
        $output->writeln('queue_monitor ok');
    }

}
