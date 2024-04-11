<?php
namespace extend\cron;
use think\console\Command;
use think\Log;

/**
 * notes: 队列监控 - 基类
 * @author 陈鸿扬 | @date 2021/6/22 13:12
 * Class BaseQueueMonitorCommand
 * @package extend\cron
 */
class BaseQueueMonitorCommand extends Command
{
    protected $php='php'; //php控制台执行程序

    public function __construct($name = null){  parent::__construct($name);
        //获取php控制台执行程序
        $this->php = config('php_fpm') ?? 'php';
    }

    protected function configure()
    {
        //指令配置
        $this->setName('queue_monitor')->setDescription('队列监控');
    }

    //获取php进程
    protected function phpProcess($php='php',$workFolder=null){
        //是否Win平台
        if(CronConsole::isWin()){
            $cmd="wmic process where caption='".$this->exeFormat($php).".exe' get commandline";
            $content = $this->winCmdFilter($cmd,$php);
        }else{
            $cmd="ps -aux|grep \"".$this->exeFormat($php);
            if( !empty($workFolder) ){ $cmd.= "\"|grep \"".$workFolder."\""; }
            $content = $this->osCmdFilter($cmd,$php,$workFolder);
        }
        $group = ['CMD' => $cmd, 'PROCESS' => $content];
        $info = CronConsole::group($group,true);
        Log::info($info);
        //输出控制台
        echo $info;
        return $content;
    }
    //win系统命令清洗
    private function winCmdFilter($cmd,$php='php',$workFolder=null){
        $content = shell_exec($cmd);
        //转数组
        $content = explode(PHP_EOL, $content);
        $content = array_filter($content); unset($content[0]);
        $content = array_values($content);
        //清洗结果
        $newContent = [];
        foreach ($content as $ind=>$val){
            $val = $this->cmdFormat($val);
            //文本清洗
            $val = str_replace($php.'.exe ',$php.' ',$val);
            $newContent[]=$val;
        }
        return $newContent;
    }
    //os系统命令清洗
    private function osCmdFilter($cmd,$php='php',$workFolder=null){
        $content = shell_exec($cmd);
        //todo::test
        //$content = file_get_contents(root_path().'extend/cron/BaseQueueMonitorCommand.text');//
        //#
        //转数组
        $content = explode(PHP_EOL, $content);
        $content = array_filter($content);
        $content = array_values($content);
        //清洗结果
        $newContent = [];
        $php = $this->exeFormat($php);
        foreach ($content as $ind=>$val){
            //匹配执行程序
            preg_match('/'.$php.'.*$/i',$val,$exe);
            //如果指定项目目录 + 没有匹配到目录 = 忽略当前匹配结果
            if( $workFolder ){
                //匹配项目目录
                preg_match("/".$workFolder."/i",$val,$folder);
                if( !empty($folder[0]) && !empty($exe[0]) ){
                    //替换掉目录 - 统一格式
                    $exe[0]=preg_replace("/(".$php.").*".$workFolder."\\//","$1 ",$exe[0]);
                    $newContent[]=$exe[0];
                }
            }else{
                if( !empty($exe[0]) ){ $newContent[]=$exe[0]; }
            }

        }
        //数据去重
        $newContent=array_unique($newContent);
        return $newContent;
    }

    //检查指定命令的线程
    protected function phpProcessMatch($phpCmd,$phpProcess){
        $phpCmd = $this->cmdFormat($phpCmd);

        //排除整条命令中的php路径
        $phpCmd = str_replace($this->php,$this->exeFormat($this->php),$phpCmd);

        $match = false;
        if( in_array($phpCmd,$phpProcess) ){ $match = true; }
        if(!$match){
            $info = $phpCmd.' | fail !';
            $info =  CronConsole::single('QUEUE FAIL',$info,true);
            Log::error($info);
        }else{
            $info = $phpCmd.' | running !';
            $info = CronConsole::single('QUEUE RUNNING',$info,true);
            Log::info($info);
        }
        //输出控制台
        echo $info;
    }
    //指令格式化-防止超过1个空格
    protected function cmdFormat($cmd){
        $cmd = preg_replace("/([\s]+)/i"," ",$cmd);
        $cmd = trim($cmd,' ');
        return $cmd;
    }
    //执行程序名格式化 - 排除路径
    protected function exeFormat($exe){
        $exe = preg_replace("/.*\\/(\w+\S+)$/i",'$1',$exe);
        return $exe;
    }
    //正则匹配格式化 - 主要处理路径斜杠
    protected function matchFormat($exe){
        $exe = str_replace("/","\\/",$exe);
        return $exe;
    }



}