<?php
namespace schedule;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;
use think\Queue;

class TaskTest extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('task_test')
            ->setDescription('easyTask 并发任务调试');
    }

    protected function execute(Input $input, Output $output)
    {
        Queue::push( QueueTestJob::class ,['name'=>'QueueTestJob'], 'QueueTestJob' );

        Log::info( 'task_test 执行时间 : '.microtime() );
        // 指令输出
        //$output->writeln('task_test ok');
    }

}
