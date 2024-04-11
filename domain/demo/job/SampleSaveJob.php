<?php

namespace domain\demo\job;

use domain\base\job\JobBase;
use domain\demo\port\logic\SampleLogic;
use think\Log;
use think\queue\Job;

/**
 * notes: 领域层-队列 - php think queue:listen --queue SampleCreateJob
 * 说明: 执行的业务逻辑统一封装 放在 对应的业务类中,这里不写具体业务代码.
 */
class SampleSaveJob extends JobBase
{
    /*
     * 检查数据 判断是否放弃执行
     */
    private function checkData($data)
    {
        //检查数据 判断是否放行
        return true;
    }

    /*
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doJob($requestInput)
    {
        Log::info($requestInput);

        //业务逻辑控制
        $Logic  = new SampleLogic();
        $result = $Logic->sampleSave($requestInput);
        if ($result) {
            return true;
        }

        return false;
    }

    /*
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        //验证逻辑
        $checkData = $this->checkData($data);
        if (!$checkData) {
            $job->delete();
            return;
        }
        //执行操作
        $isJobDone = $this->doJob($data);
        //执行成功 就删除
        if ($isJobDone) {
            $job->delete();
            Log::info('SampleSaveJob has been done and deleted');
        } else {
            //失败重试3次后 就删除
            if ($job->attempts() > 3) {
                $job->delete();
                Log::info('SampleSaveJob has been retried more than 3 times!');
            }
        }
    }
}