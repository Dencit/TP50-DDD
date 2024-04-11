<?php
namespace schedule;
use cron\BaseTimer;

/**
 * 文档: https://www.kancloud.cn/a392223903/easytask/1666906
 * 执行命令:
 * php timer queue start        启动进程
 * php timer queue status       查看状态
 * php timer queue stop         停止,但会自动重启
 * php timer queue stop force   终止,彻底关闭
 * php timer queue reset        重启
 * ps -ef|grep tp50_timer   Linux 查看进程
 */
/**
 * notes: 独立调度控制台 - 不依赖框架Command注册
 * @author 陈鸿扬 | @date 2021/6/29 12:29
 * Class Timer
 * @package schedule
 */
class Timer extends BaseTimer
{
    protected $prefix = 'tp50_timer'; //设置项目名称 - 不同项目要区分名称

    public function execute(){
        //获取实例
        $task = $this->easyTask;
        //获取php控制台执行程序
        $phpFpm = $this->php;

        //#执行: addCommand[命令行,间隔执行秒数,创建进程数]
        //队列 只执行一次侦听 或 间隔执行

        //队列模板
        $task->addCommand(sprintf('%s think queue:work --daemon --queue QueueTestJob', $phpFpm), 'queue_queue_test_job', 10, 1);

        //注: 调度器调用命令行时, 是静默执行, 如果调用的是守护进程命令, 间隔n秒后重复执行, linux系统最多会创建3个进程,用于轮换过渡.
        //因此,为避免资源损耗,所以这里60秒重复一次, 预防进程自己挂掉.

        //框架默认队列
        $task->addCommand(sprintf('%s think queue:listen', $phpFpm), 'queue', 60, 1);


        //以下可用于 60秒以内的并发任务, 其它定时调度 可用CronTimer

        //任务模板
        $task->addCommand(sprintf('%s think task_test', $phpFpm), 'task_test', 10, 1);

    }

}