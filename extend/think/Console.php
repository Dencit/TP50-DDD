<?php
// +----------------------------------------------------------------------
// | TopThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangyajun <448901948@qq.com>
// +----------------------------------------------------------------------

namespace extend\think;

use think\Config;
use think\console\Input;
use think\console\Output;
use think\Log;

class Console extends \think\Console
{
    /**
     * @var bool 是否捕获异常
     */
    private $catchExceptions = true;

    /**
     * @var bool 是否自动退出执行
     */
    private $autoExit = true;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $user = null)
    {
        parent::__construct($name, $version, $user);
    }

    /**
     * 初始化 Console
     * @param bool $run
     * @return Console|int|\think\Console
     * @throws \Exception
     */
    public static function init($run = true)
    {
        static $console;

        if (!$console) {
            $config = Config::get('console');
            // 实例化 console
            $console = new self($config['name'], $config['version'], $config['user']);
            // 读取指令集
            if (is_file(CONF_PATH . 'command' . EXT)) {
                $commands = include CONF_PATH . 'command' . EXT;
                if (is_array($commands)) {
                    foreach ($commands as $command) {
                        class_exists($command) &&
                        is_subclass_of($command, "\\think\\console\\Command") &&
                        $console->add(new $command());  // 注册指令
                    }
                }
            }
        }

        return $run ? $console->run() : $console;
    }

    /**
     * 执行当前的指令
     * @access public
     * @return int
     * @throws \Exception
     */
    public function run()
    {
        $input  = new Input();
        $output = new Output();

        $this->configureIO($input, $output);

        try {
            $exitCode = $this->doRun($input, $output);
        } catch (\Exception $e) {
            if (!$this->catchExceptions) throw $e;
            $output->renderException($e);
            $exitCode = $e->getCode();
            if (is_numeric($exitCode)) {
                $exitCode = ((int)$exitCode) ?: 1;
            } else {
                $exitCode = 1;
            }
            Log::error($e->getMessage());
        }

        //因为是异步执行,直接标记结束分割线. 另外依赖在"队列or指令"类内(COMMAND_START,QUEUE_START),添加"入点出点"分割日志来区分.
        //块日志-结束分割线
        $log  = '[CONSOLE_END]';
        $line = $log . PHP_EOL . '---------------------------------------------------------------';
        Log::alert($line);

        if ($this->autoExit) {
            if ($exitCode > 255) $exitCode = 255;
            exit($exitCode);
        }
        return $exitCode;
    }

}
