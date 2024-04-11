<?php
/**
 * notes:
 * @author 陈鸿扬 | @date 2021/4/10 23:55
 */

namespace extend\thinktest;


use think\App;
use think\Config;
use think\Error;
use think\Loader;

class BaseUnit
{

    private $app;

    //框架配置初始化
    public function init(){

        $DS = DIRECTORY_SEPARATOR;
        $DIR = realpath(__DIR__.$DS.'..'.$DS.'..').$DS;

        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        defined('APP_PATH') or define('APP_PATH', $DIR . 'application'.$DS);
        defined('THINK_VERSION') or define('THINK_VERSION', '5.0.24');
        defined('THINK_START_TIME') or define('THINK_START_TIME', microtime(true));
        defined('THINK_START_MEM') or define('THINK_START_MEM', memory_get_usage());
        defined('EXT') or define('EXT', '.php');
        defined('THINK_PATH') or define('THINK_PATH', $DIR .'thinkphp'.$DS );
        defined('LIB_PATH') or define('LIB_PATH', THINK_PATH . 'library' . $DS);
        defined('CORE_PATH') or define('CORE_PATH', LIB_PATH . 'think'.$DS);
        defined('TRAIT_PATH') or define('TRAIT_PATH', LIB_PATH . 'traits' . $DS);
        defined('ROOT_PATH') or define('ROOT_PATH', dirname(APP_PATH) . $DS);
        defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH . 'extend' . $DS);
        defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor_fake' . $DS);
        defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . $DS);
        defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'log' . $DS);
        defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . $DS);
        defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . $DS);
        defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
        defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
        defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
        // 环境常量
        defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
        defined('IS_WIN') or define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);

        // 载入Loader类
        require_once CORE_PATH . 'Loader.php';

        // 加载环境变量配置文件
        if (is_file(ROOT_PATH . '.env')) {
            $env = parse_ini_file(ROOT_PATH . '.env', true);
            foreach ($env as $key => $val) {
                $name = ENV_PREFIX . strtoupper($key);
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $item = $name . '_' . strtoupper($k);
                        putenv("$item=$v");
                    }
                } else {
                    putenv("$name=$val");
                }
            }
        }

        // 注册自动加载
        Loader::register();
        // 注册错误和异常处理机制
        Error::register();
        // 加载惯例配置文件
        Config::set(include_once THINK_PATH . 'convention' . EXT);

        $this->app=new App();
        return $this->app;
    }

}