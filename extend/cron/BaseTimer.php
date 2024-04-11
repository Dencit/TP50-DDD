<?php
namespace cron;
use extend\cron\CronConsole;
use extend\cron\EasyTaskSchedule;

/**
 * notes: 独立调度控制台 - 基类 - 不依赖框架Command注册
 * @author 陈鸿扬 | @date 2021/6/29 12:29
 * Class Timer
 * @package schedule
 */
class BaseTimer
{
    protected $prefix = 'timer'; //设置项目名称 - 不同项目要区分名称

    protected $php='php'; //php控制台执行程序

    protected $easyTask; //实例

    protected $input; //输入

    public function __construct($argv=null){

        //获取php控制台执行程序
        $this->php = config('php_fpm') ?? 'php';
        //获取cli输入
        $this->input = $argv;

        //设置实例
        $this->easyTask=new EasyTaskSchedule($this->prefix);
    }

    //获取命令行参数
    protected function getArgumentByInd($ind=0){
        if($this->input){
            if(isset($this->input[$ind])){
                return $this->input[$ind];
            }
        }
        return '';
    }

    public function __destruct(){
        if( !empty($this->input) ){
            // 获取命令
            $command = $this->getArgumentByInd(2);
            $force = $this->getArgumentByInd(3);
            //执行
            $result = $this->easyTask->run($command,$force);
            $infoBase = 'timer';
            echo  sprintf('%s %s !',$infoBase,$result);
        }
    }
}