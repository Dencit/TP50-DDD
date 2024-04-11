<?php

namespace schedule\ModuleTask;

use extend\cron\CronSchedule;

/**
 * notes: 示例模块
 * Class Demo
 * @package schedule\ModuleTask
 */
class Demo
{
    public function __construct(CronSchedule &$schedule, $phpFpm, $env)
    {
        //cron-expression 基本定时任务调试
        //$schedule->command(sprintf('%s think cron_test', $phpFpm))->cron('* * * * *');
    }
}