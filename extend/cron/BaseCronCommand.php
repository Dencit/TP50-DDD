<?php

namespace extend\cron;

use think\console\Command;
use think\Log;

/**
 * notes: 基本定时任务 - 基类
 * @author 陈鸿扬 | @date 2021/6/23 9:24
 * Class BaseCronCommand
 * @package extend\cron
 */
class BaseCronCommand extends Command
{
    protected $php = 'php'; //php控制台执行程序

    protected $schedule; //实例

    //每个指令执行时,框架会对每个指令类都实例化一遍,构造和析构方法,不适合放日志,改成instance,destroy,在继承类中触发.
    //初始化-必须
    protected function instance()
    {
        //获取php控制台执行程序
        $this->php = config('php_fpm') ?? 'php';
        //传递配置
        $option = [
            'php_fpm'     => $this->php,
            'root_folder' => $this->rootFolder(),
        ];
        //设置实例
        if (empty($this->schedule)) {
            $this->schedule = new CronSchedule($option);
        }
    }

    //执行与销毁-必须
    protected function destroy()
    {
        if (!empty($this->schedule)) {
            //先记录日志 - 防止与其它进程日志重叠
            $this->schedule->runBeforeLog();
            $desc      = $this->getName() . " | " . $this->getDescription();
            $startLine = PHP_EOL . '---------------------------------------------------------------' . PHP_EOL;
            $start     = "[ " . date("Y-m-d H:i:s", time()) . ' | ' . (int)(microtime(true) * 1000 * 1000 * 1000) . " | " . getmypid() . " ]" . '[ info ] [ CRON_TIMER_START ]' . PHP_EOL;
            $info      = "# 执行完成" . PHP_EOL;
            $info      .= "> 任务名称: " . $desc . PHP_EOL;
            $currLog   = $this->schedule->currLog;
            $end       = '[ alert ] [ CRON_TIMER_END ]' . PHP_EOL;
            $endLine   = '---------------------------------------------------------------';
            $log       = $startLine . $start . $info . $currLog . $end . $endLine;
            Log::info($log);
            //再执行 - 防止与其它进程日志重叠
            $this->schedule->run();
        }
    }

    protected function configure()
    {
        // 指令配置
        $this->setName('cron_timer')->setDescription('cron-expression 基本定时任务');
    }

    /**
     * notes: 获取项目根目录
     * @author 陈鸿扬 | @date 2021/7/2 14:39
     * @param bool $path
     * @return bool|mixed|null|string|string[]
     */
    public function rootFolder($path = true)
    {
        $folder = root_path();
        if ($path) {
            $folder = str_replace("/", "\\/", $folder);
        } else {
            $folder = preg_replace("/.*\\/(\w+\S+)$/i", '$1', $folder);
        }
        return $folder;
    }

}