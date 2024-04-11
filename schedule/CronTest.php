<?php

namespace schedule;

use domain\base\console\CommandBase;
use think\console\Input;
use think\console\Output;
use think\Log;
use think\Queue;

/**
 * notes: cron-expression 基本定时任务调试
 * @author 陈鸿扬 | @date 2021/6/23 10:06
 * Class CronTest
 * @package schedule
 */
class CronTest extends CommandBase
{
    protected function configure()
    {
        // 指令配置
        $this->setName('cron_test')
            ->setDescription('cron-expression 基本定时任务调试');
    }

    protected function execute(Input $input, Output $output)
    {
        //性能日志-入点
        $this->commandInLog();

        //测试
        Log::info('cron_test 测试执行时间 : ' . microtime());

        //触发队列-测试
        Queue::push(QueueTestJob::class, ['name' => 'QueueTestJob'], 'QueueTestJob');

        // 指令输出
        //$output->writeln('cron_test ok');

        //性能日志-出点
        $this->commandOutLog();
    }

}
