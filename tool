#!/usr/bin/env php
<?php
namespace think;

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
// 加载基础文件
require __DIR__ . '/thinkphp/base.php';
// 执行应用
App::initCommon();

$mysql = Config::get()['database'];

$opt= [
    'debug'=>false,//默认关闭
    'type' => 'mysql',
    'hostname' => $mysql["hostname"], 'hostport' => $mysql["hostport"],
    'database' => $mysql["database"], 'prefix' => $mysql["prefix"],
    'username' => $mysql["username"], 'password' => $mysql["password"],
    'root_path'=>root_path().'', //代码生成目录
    'app_path'=> base_path().'', //代码生成目录
    'md_path'=>root_path().'markdwon', //markdwon生成目录
    'mongodb'=>[
        'username' => 'root',
        'password' => 'root',
        'ssl' => false,
        'authSource' => 'admin'
    ]
];
//#########################################################################


/**
 * 命令流程
 *
 * php tool table:md qj_test_childs               生成 指定数据表 结构
 * php tool table:md qj_test_childs database      生成 指定数据表 结构 - 指定库
 * php tool table:md *                            生成 所有数据表 结构
 * php tool table:md * database                   生成 所有数据表 结构 - 指定库
 *
 * php tool yapi:psm  yapi/api.json  postman/api.json         yapi转psm 单文件
 * php tool yapi:psms  yapi/api.json  postman/api             yapi转psm 多文件
 *
 * php tool yapi-db:psm  13  postman/ypdb-api.json      yapi db数据 转psm 单文件
 * php tool yapi-db:psms  13  postman/ypdb-api          yapi db数据 转psm 多文件
 *
 */

require_once "extend/thinkex/MongoDB.php";
require_once "extend/thinkex/PdoDB.php";
require_once "extend/thinkex/ContentReplace.php";
require_once "extend/thinktest/base/api/TableBatchToDoc.php";
use extend\thinkex\tool;
use extend\thinktest\base\api\TableBatchToDoc;

$mdPath = $opt['md_path'];

//控制台逻辑
switch ($argv[1]) {
    default : return false; break;
    case "table:md":
        $dbOptStr = null; if(isset($argv[3])){ $dbOptStr = $argv[3]; }
        $TableBatchToDoc = new TableBatchToDoc($opt,'table',$dbOptStr);

        $folders=['table'];
        $TableBatchToDoc->makeFolderByArr($mdPath,$folders);

        $tbPath = $mdPath.'\\table';
        if( isset($argv[2]) ){
             $TableBatchToDoc->batchWrite($argv[2],$tbPath,false);
        }else{
            $TableBatchToDoc->batchWrite(null,$tbPath,false);
        }

        break;
    case 'yapi:psm':
        $tool = new tool($opt);

        if( isset($argv[2]) && isset($argv[3]) ){ $readPath = $argv[2]; $putPath = $argv[3];

            $content = $tool->fileRead($readPath);
            $yapiArr = json_decode($content,true);

            $psmArr = $tool->yapiTurnAll($yapiArr);

            $content = json_encode($psmArr,JSON_UNESCAPED_UNICODE);
            $content = str_replace('\/','/',$content);

            $tool->filePut( $putPath,$content );
        }

        break;
    case 'yapi:psms':
        $tool = new tool($opt);

        if( isset($argv[2]) && isset($argv[3]) ){ $readPath = $argv[2]; $putPath = $argv[3];
            $content = $tool->fileRead($readPath);
            $yapiArr = json_decode($content,true);
            $tool->yapiTurSingle($yapiArr,$putPath);
        }

        break;

    case 'yapi-db:psm':
        $tool = new tool($opt);

        if( isset($argv[2]) && isset($argv[3]) ){ $projectId = (int)$argv[2]; $putPath = $argv[3];

            $content = $tool->yapiProjRead($projectId);
            $yapiArr = json_decode($content,true);

            $psmArr = $tool->yapiTurnAll($yapiArr);

            $content = json_encode($psmArr,JSON_UNESCAPED_UNICODE);
            $content = str_replace('\/','/',$content);

            $tool->filePut( $putPath,$content );
        }

        break;

    case 'yapi-db:psms':
        $tool = new tool($opt);

        if( isset($argv[2]) && isset($argv[3]) ){ $projectId = (int)$argv[2]; $putPath = $argv[3];
            $content = $tool->yapiProjRead($projectId);
            $yapiArr = json_decode($content,true);
            $tool->yapiTurSingle($yapiArr,$putPath);
        }

        break;

    case 'mongo:yp-psm':
        $tool = new tool($opt);

        /*if( isset($argv[2]) && isset($argv[3]) ){ $projectId = (int)$argv[2]; $putPath = $argv[3];

            $MongoDB = new MongoDB($opt);
            $collection = $MongoDB->database('yapi')->collection('interface_case');
            $where = ['project_id'=>$projectId ];
            $res = $collection->get($where);

            foreach ($res as $k=>$v){
                var_dump($v['_id']);//die;
            }

        }*/

        break;
}