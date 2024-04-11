<?php

namespace schedule;

use domain\base\job\JobBase;
use think\Log;
use think\queue\Job;

/**
 * notes: 队列调试
 * @author 陈鸿扬 | @date 2021/6/23 10:08
 * Class QueueTestJob
 * @package schedule
 */
class QueueTestJob extends JobBase
{

    public function fire(Job $job, $data)
    {
        Log::info('QueueTestJob 执行时间 : ' . microtime());

        $job->delete();
    }

    // 选一个接口 触发队列
    // Queue::push( QueueTestJob::class ,['name'=>'QueueTestJob'], 'QueueTestJob' );

}