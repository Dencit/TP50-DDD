<?php

namespace domain\demo\console;

use domain\base\console\CommandBase;
use domain\demo\srv\SampleSrv;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\Log;

/**
 * notes: 领域层-指令类
 * desc: 执行的业务逻辑统一抽象到 同名业务类中,这里不写具体业务代码.
 */
class SampleCmd extends CommandBase
{
    protected static $name = 'sample_cmd'; //命令名
    protected static $desc = 'sample cmd'; //命令说明

    protected function configure()
    {
        // 指令配置
        $this
            ->setName(self::$name)
            ->setDescription(self::$desc)
            //->addArgument('work', Argument::OPTIONAL, '固定值: work ') // 验证参数类型: Argument::REQUIRED; Argument::OPTIONAL; Argument::IS_ARRAY
            ->addOption('param', null, Option::VALUE_REQUIRED, '参数: --param 0 ');
    }

    protected function execute(Input $input, Output $output)
    {
        //性能日志-入点
        $this->commandInLog();

        //获取命令参数
        //$work = $input->getArgument('work') ?: null;
        $param = $input->getOption('param') ?: null;

        $SampleSrv = new SampleSrv();
        try {
            //命令行 业务逻辑
            $result = $SampleSrv->sampleCmd($param);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }

        // 指令输出
        if ($result) {
            Log::info('sample cmd ok');
            $output->writeln('sample cmd ok');
        } else {
            Log::notice('sample cmd end');
            $output->writeln('sample cmd end');
        }

        //性能日志-出点
        $this->commandOutLog();
    }

}
