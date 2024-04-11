<?php

namespace schedule;

use extend\cron\BaseCronCommand;
use schedule\ModuleTask\Demo;
use think\console\Input;
use think\console\Output;

/**
 * 文档: https://github.com/mtdowling/cron-expression
 * 执行命令:
 * php think cron_timer  启动 - 可用crontab调度 1分钟执行一次
 */

/**
 * notes: cron-expression 基本定时任务
 * @author 陈鸿扬 | @date 2021/6/23 10:05
 * Class CronTimer
 * @package schedule
 */
class CronTimer extends BaseCronCommand
{

    protected function execute(Input $input, Output $output)
    {
        //初始化-必须
        $this->instance();

        $schedule = $this->schedule;

        $env = config('env');
        //获取php控制台执行程序
        $phpFpm = $this->php;

        //cron-expression 基本定时任务调试
        $schedule->command(sprintf('%s think cron_test', $phpFpm))->cron('* * * * *');

        //公共部分

        //测试环境 独占
        if ($env == 'dev') {

        }
        //预发布环境 独占
        if ($env == 'pre') {

        }
        //生产环境 独占
        if ($env == 'online') {

        }


//V# domain 领域模块任务 指令区域

        //任务调类-类名与模块名对应

        #示例模块
        new Demo($schedule, $phpFpm, $env);

//A# domain 领域模块任务 指令区域

        //执行与销毁-必须
        $this->destroy();
    }
}
