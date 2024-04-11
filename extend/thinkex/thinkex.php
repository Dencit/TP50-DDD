<?php

namespace extend\thinkex;

use think\Config;

class ThinkEx
{
    protected $r = "~r";
    protected $n = "~n";
    protected $t = "~t";
    protected $s = "~s";
    protected $opt;
    protected $moduleDomeName;
    protected $childDemoName;

    public function __construct($option)
    {
        $this->moduleDomeName = "demo";
        $this->childDemoName  = ucwords("Sample");
        $this->opt            = $option;
    }

    function DB($childName, $type = null)
    {
        $opt = $this->opt;

        $DB        = new PdoDB($opt);
        $childName = $this->childNameFilter($childName);

        switch ($type) {
            default:
                $res = $DB->query($type);
                break;
            case "getTableFields":
                $res = $DB->getTableFields($childName);
                break;
        }
        return $res;
    }


    function console($msg, $color = null)
    {
        switch ($color) {
            default:
                $first = "\033[0m";
                break;
            case "red":
                $first = "\033[31m";
                break;
            case "lemon":
                $first = "\033[32m";
                break;
            case "yellow":
                $first = "\033[33m";
                break;
            case "blue":
                $first = "\033[34m";
                break;
            case "purple":
                $first = "\033[35m";
                break;
            case "green":
                $first = "\033[36m";
                break;
        }
        flush();
        print($first . $msg . "\n\033[0m");
    }

    function readyToReplace(&$fileContent)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $fileContent = preg_replace("/\r+/is", $r, $fileContent);
        $fileContent = preg_replace("/\n+/is", $n, $fileContent);
        $fileContent = preg_replace("/\t+/is", $t, $fileContent);
        $fileContent = preg_replace("# #is", $s, $fileContent);
        return $fileContent;
    }

    function readyToContent(&$fileContent)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $fileContent = preg_replace("/(" . $r . ")/is", "\r", $fileContent);
        $fileContent = preg_replace("/(" . $n . ")/is", "\n", $fileContent);
        $fileContent = preg_replace("/(" . $t . ")/is", "\t", $fileContent);
        $fileContent = preg_replace("/(" . $s . ")/is", " ", $fileContent);
        return $fileContent;
    }

    function fileEditCopy($option, $callfunc)
    {
        $path    = $option['get_path'];
        $putPath = $option['put_path'];
        $content = file_get_contents($path);
        $this->readyToReplace($content);

        $moduleDomeName             = $this->moduleDomeName;
        $childDemoName              = $this->childDemoName;
        $option['module_demo_name'] = $moduleDomeName;
        $option['child_demo_name']  = $childDemoName;

        //非空才执行 - 可通过闭包 是否控制写入
        $content = $callfunc($option, $content);
        if (!empty($content)) {
            $this->readyToContent($content);
            file_put_contents($putPath, $content);
        }
        return true;
    }

    function fileEditChange($option, $callfunc)
    {
        $demoPath   = $option['get_path'];
        $changePath = $putPath = $option['put_path'];

        //模板内容
        $demoContent = file_get_contents($demoPath);
        $this->readyToReplace($demoContent);

        //待修改内容
        $changeContent = file_get_contents($changePath);
        $this->readyToReplace($changeContent);

        $moduleDomeName             = $this->moduleDomeName;
        $childDemoName              = $this->childDemoName;
        $option['module_demo_name'] = $moduleDomeName;
        $option['child_demo_name']  = $childDemoName;

        //非空才执行 - 可通过闭包 控制是否写入
        $putContent = $callfunc($option, $demoContent, $changeContent);
        if (!empty($putContent)) {
            $this->readyToContent($putContent);
            file_put_contents($putPath, $putContent);
        }
        return true;
    }

    function makeDemoContent(&$content, $option, $opt, $dbOn = null, $dbOptStr = null)
    {
        //修改默认数据库连接配置,用于跨库查询
        if ($dbOptStr) {
            $this->changeDbOption($dbOptStr, $opt);
        }
        //#
        $ContentReplace = new ContentReplace($option, $opt);
        //替换数据库配置 - 不需连数据库
        $ContentReplace->forDbOpt($dbOptStr, $content);
        //替换指定文本
        $ContentReplace->forNameHump($content);
        //替换注释标签
        $ContentReplace->forHiddenTag($content);
        if ($dbOn) {
            $ContentReplace->forFields($content); //替換字段Array
        }
        $ContentReplace->forCodeBlockTag($content); //指定输出代码块
        return $content;
    }

    function changeDemoContent(&$content, &$changeContent, $option, $opt = null, $dbOn = null, $dbOptStr = null)
    {
        //修改默认数据库连接配置,用于跨库查询
        if ($dbOptStr) {
            $this->changeDbOption($dbOptStr, $opt);
        }
        //#
        $ContentReplace = new ContentReplace($option, $opt);
        //替换数据库配置 - 不需连数据库
        $ContentReplace->forDbOpt($dbOptStr, $content);
        //替换指定文本
        $ContentReplace->forNameHump($content);
        //替换注释标签
        $ContentReplace->forHiddenTag($content);
        if ($dbOn) {
            $ContentReplace->forFields($content); //替換字段Array
        }
        $ContentReplace->changeForCodeBlockTag($content, $changeContent); //替换指定输出代码块
        $ContentReplace->forCodeBlockTag($changeContent); //指定输出代码块
        return $changeContent;
    }


    function forValidate($childName)
    {
        $r = $this->r;
        $n = $this->n;
        $t = $this->t;
        $s = $this->s;

        $res         = $this->DB($childName, "getTableFields");
        $repValidate = '';
        $space       = $r . $n . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                switch ($v['native_type']) {
                    default :
                        break;
                    case "LONGLONG":
                    case "LONG":
                    case"TINY":
                        $v['native_type'] = 'integer';
                        break;
                    case "VAR_STRING":
                    case "BLOB":
                        $v['native_type'] = 'alphaDash';
                        break;
                    case "DATETIME":
                    case "TIMESTAMP":
                        $v['native_type'] = 'date';
                        break;
                    case "NEWDECIMAL":
                        $v['native_type'] = 'number';
                        break;
                };
                switch ($v['name']) {
                    default :
                        break;
                    case "status":
                        $v['native_type'] = 'integer|in:1,2';
                        break;
                    case "type":
                        $v['native_type'] = 'integer|in:1,2';
                        break;
                    case "phone":
                        $v['native_type'] = 'number|max:11';
                        break;
                    case "sex":
                        $v['native_type'] = 'integer|in:0,1,2';
                        break;
                    case "ip":
                        $v['native_type'] = 'ip';
                        break;
                };
                if ($k < count($res) - 1) {
                    $repValidate .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                } else {
                    $repValidate .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                }
            }
        }
        return $space . $repValidate;

    }

    function forFillable($childName)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $res         = $this->DB($childName, "getTableFields");
        $repFillable = '';
        $space       = $r . $n . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                switch ($v['name']) {
                    case "id":
                    case "created_at":
                    case"updated_at":
                    case"deleted_at":
                        break;
                    default:
                        if ($k < count($res) - 1) {
                            $repFillable .= "\"" . $v['name'] . "\"," . $space;
                        } else {
                            $repFillable .= "\"" . $v['name'] . "\"," . $space;
                        }
                        break;
                }
            }
        }
        return $space . $repFillable;
    }

    function forGuarded($childName)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $res         = $this->DB($childName, "getTableFields");
        $repFillable = '';
        $space       = $r . $n . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                switch ($v['name']) {
                    case "id":
                    case "create_time":
                    case"update_time":
                        if ($k < count($res) - 1) {
                            $repFillable .= "\"" . $v['name'] . "\"," . $space;
                        } else {
                            $repFillable .= "\"" . $v['name'] . "\"," . $space;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $space . $repFillable;
    }

    function forTypes($childName)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $res         = $this->DB($childName, "getTableFields");
        $repFillable = '';
        $space       = $r . $n . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                //var_dump( $v['native_type'] );
                switch ($v['native_type']) {
                    default :
                        break;
                    case "LONG":
                        $v['native_type'] = 'int';
                        break;
                    case "LONGLONG":
                        $v['native_type'] = 'bigint';
                        break;
                    case"TINY":
                        $v['native_type'] = 'tinyint';
                        break;
                    case "VAR_STRING":
                    case "BLOB":
                        $v['native_type'] = 'varchar';
                        break;
                    case "DATETIME":
                    case "TIMESTAMP":
                        $v['native_type'] = 'datetime';
                        break;
                    case "NEWDECIMAL":
                        $v['native_type'] = 'numeric';
                        break;
                };
                switch ($v['name']) {
                    default :
                        break;
                    case "status":
                        $v['native_type'] = 'tinyint';
                        break;
                    case "type":
                        $v['native_type'] = 'tinyint';
                        break;
                };
                if ($k < count($res) - 1) {
                    $repFillable .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                } else {
                    $repFillable .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                }
            }
        }
        return $space . $repFillable;
    }

    function forRules($childName)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $res         = $this->DB($childName, "getTableFields");
        $repFillable = '';
        $space       = $r . $n . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                var_dump($v['native_type']);
                switch ($v['native_type']) {
                    default :
                        break;
                    case "LONGLONG":
                    case "LONG":
                    case"TINY":
                        $v['native_type'] = 'integer';
                        break;
                    case "VAR_STRING":
                    case "BLOB":
                        $v['native_type'] = 'string';
                        break;
                    case "DATETIME":
                    case "TIMESTAMP":
                        $v['native_type'] = 'date';
                        break;
                    case "NEWDECIMAL":
                        $v['native_type'] = 'numeric';
                        break;
                };
                switch ($v['name']) {
                    default :
                        break;
                    case "status":
                        $v['native_type'] = 'integer|in:1,2';
                        break;
                    case "type":
                        $v['native_type'] = 'integer|in:1,2';
                        break;
                };
                if ($k < count($res) - 1) {
                    $repFillable .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                } else {
                    $repFillable .= "\"" . $v['name'] . "\"=>\"" . $v['native_type'] . "\"," . $space;
                }
            }
        }
        return $space . $repFillable;
    }

    function forData($childName)
    {
        $r           = $this->r;
        $n           = $this->n;
        $t           = $this->t;
        $s           = $this->s;
        $res         = $this->DB($childName, "getTableFields");
        $repFillable = '';
        $space       = $r . $n . $t . $t . $t;
        if ($res) {
            foreach ($res as $k => $v) {
                //var_dump( $v['native_type'] );
                switch ($v['native_type']) {
                    default :
                        $v['native_type'] = '(string)';
                        break;
                    case "LONGLONG":
                    case "LONG":
                    case"TINY":
                        $v['native_type'] = '(int)';
                        break;
                    case "VAR_STRING":
                        $v['native_type'] = '(string)';
                        break;
                    case "DATETIME":
                    case "TIMESTAMP":
                        $v['native_type'] = '(string)';
                        break;
                    case "NEWDECIMAL":
                        $v['native_type'] = '(float)';
                        break;
                };
                if ($k < count($res) - 1) {
                    $repFillable .= "\"" . $v['name'] . "\"=>" . $v['native_type'] . "\$result->" . $v['name'] . "," . $space;
                } else {
                    $repFillable .= "\"" . $v['name'] . "\"=>" . $v['native_type'] . "\$result->" . $v['name'] . "," . $space;
                }
            }
        }
        return $space . $repFillable;
    }

//#########################################################

    //“_”拆分$childName
    function childNameFilter($childName)
    {
        $newChildName = '';
        for ($i = 1; $i < 10; $i++) {
            preg_match("/([A-Z]{1}[a-z 0-9]+){" . $i . "}/", $childName, $m);
            if (!empty($m[1])) {
                $newChildName .= $m[1] . '_';
            } else {
                $newChildName = preg_replace("/_$/", '', $newChildName);
                break;
            }
        }
        return $newChildName;
    }

    function childNameFirstFilter($childName)
    {
        $newChildName = '';
        $first        = 0;
        for ($i = 1; $i < 10; $i++) {
            preg_match("/([A-Z]{1}[a-z 0-9]+){" . $i . "}/", $childName, $m);
            if (!empty($m[1])) {
                $first++;
                if ($first < 2) {
                    $newChildName .= strtolower($m[1]);
                } else {
                    $newChildName .= $m[1];
                }
            }
        }
        return $newChildName;
    }

    function setDocFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\" . "doc.md",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\" . "doc.md",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetDocFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                //$ContentReplace = new ContentReplace($option); $ContentReplace ->forNameLower($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetDocFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setControllerFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\port\\controller\\" . ucwords($childDemoName) . "Controller.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\port\\controller\\" . ucwords($childName) . "Controller.php",
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $this->fileEditChange($option, function ($option, $demoContent, $changeContent) use ($opt, $dbOn, $dbOptStr) {
                $this->changeDemoContent($demoContent, $changeContent, $option, $opt, $dbOn, $dbOptStr);
                return $changeContent;
            });
            $msg = "Changed : Module " . $moduleName . ' | SetControllerFile | OK';
            $this->console($msg, "green");
            //$msg="Exception : ".$moduleName." | SetControllerFile | is exists !"; $this->console($msg,"red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                $this->makeDemoContent($content, $option, $opt, $dbOn, $dbOptStr);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetControllerFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setCmdFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\console\\" . ucwords($childDemoName) . "Cmd.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\console\\" . ucwords($childName) . "Cmd.php",
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetCmdFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option, $opt);
                $ContentReplace->forNameNormal($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetCmdFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setSaveJobFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\job\\" . ucwords($childDemoName) . "SaveJob.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\job\\" . ucwords($childName) . "SaveJob.php",
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | setSaveJobFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn) {
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换指定文本
                $ContentReplace->forNameHump($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetControllerFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setRequestFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\port\\request\\" . ucwords($childDemoName) . "Request.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\port\\request\\" . ucwords($childName) . "Request.php"
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetRequestFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                //修改默认数据库连接配置,用于跨库查询
                if ($dbOptStr) {
                    $this->changeDbOption($dbOptStr, $opt);
                }
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换数据库配置 - 不需连数据库
                $ContentReplace->forDbOpt($dbOptStr, $content);
                //替换指定文本
                $ContentReplace->forNameNormal($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forValidate($content);
                    $ContentReplace->forMessage($content);
                }
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetRequestFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setRepositoryFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\repository\\" . ucwords($childDemoName) . "Repo.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\repository\\" . ucwords($childName) . "Repo.php"
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetRepositoryFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                //修改默认数据库连接配置,用于跨库查询
                if ($dbOptStr) {
                    $this->changeDbOption($dbOptStr, $opt);
                }
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换数据库配置 - 不需连数据库
                $ContentReplace->forDbOpt($dbOptStr, $content);
                //替换指定文本
                $ContentReplace->forNameNormal($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forGuarded($content);
                    $ContentReplace->forTypes($content);
                    $ContentReplace->forRules($content);
                }
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetRepositoryFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setModelFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\model\\" . ucwords($childDemoName) . "Model.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\model\\" . ucwords($childName) . "Model.php"
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetModelFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                //修改默认数据库连接配置,用于跨库查询
                if ($dbOptStr) {
                    $this->changeDbOption($dbOptStr, $opt);
                }
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换数据库配置 - 不需连数据库
                $ContentReplace->forDbOpt($dbOptStr, $content);
                //替换指定文本
                $ContentReplace->forNameNormal($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forGuarded($content);
                    $ContentReplace->forTypes($content);
                    $ContentReplace->forRules($content);
                }
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetModelFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setTransFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\port\\trans\\" . ucwords($childDemoName) . "Trans.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\port\\trans\\" . ucwords($childName) . "Trans.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetTransFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                //修改默认数据库连接配置,用于跨库查询
                if ($dbOptStr) {
                    $this->changeDbOption($dbOptStr, $opt);
                }
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换数据库配置 - 不需连数据库
                $ContentReplace->forDbOpt($dbOptStr, $content);
                //替换指定文本
                $ContentReplace->forNameLower($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forData($content);
                }
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetTransFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setSrvFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\srv\\" . ucwords($childDemoName) . "Srv.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\srv\\" . ucwords($childName) . "Srv.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $this->fileEditChange($option, function ($option, $demoContent, $changeContent) use ($opt, $dbOn, $dbOptStr) {
                $this->changeDemoContent($demoContent, $changeContent, $option, $opt, $dbOn, $dbOptStr);
                return $changeContent;
            });
            $msg = "Changed : Module " . $moduleName . ' | SetSrvFile | OK';
            $this->console($msg, "green");
            //$msg="Exception : ".$moduleName." | SetSrvFile | is exists !"; $this->console($msg,"red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                $this->makeDemoContent($content, $option, $opt, $dbOn, $dbOptStr);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetSrvFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setLogicFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\port\\logic\\" . ucwords($childDemoName) . "Logic.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\port\\logic\\" . ucwords($childName) . "Logic.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $this->fileEditChange($option, function ($option, $demoContent, $changeContent) use ($opt, $dbOn, $dbOptStr) {
                $this->changeDemoContent($demoContent, $changeContent, $option, $opt, $dbOn, $dbOptStr);
                return $changeContent;
            });
            $msg = "Changed : Module " . $moduleName . ' | SetLogicFile | OK';
            $this->console($msg, "green");
            //$msg="Exception : ".$moduleName." | SetLogicFile | is exists !"; $this->console($msg,"red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                $this->makeDemoContent($content, $option, $opt, $dbOn, $dbOptStr);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetLogicFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setEnumFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\enum\\" . ucwords($childDemoName) . "Enum.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\enum\\" . ucwords($childName) . "Enum.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetEnumFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option);
                $ContentReplace->forNameLower($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetEnumFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setErrorFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\error\\" . ucwords($moduleDomeName) . "RootError.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\error\\" . ucwords($moduleName) . "RootError.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetRootErrorFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option);
                $ContentReplace->forNameNormal($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetRootErrorFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setAttributeFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\Resources\\lang\\zh-CN\\" . strtolower($childDemoName) . "-attribute.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\Resources\\lang\\zh-CN\\" . strtolower($this->childNameFilter($childName)) . "-attribute.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetAttributeFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option);
                $ContentReplace->forNameNormal($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetAttributeFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setMessageFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\Resources\\lang\\zh-CN\\" . strtolower($childDemoName) . "-message.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\Resources\\lang\\zh-CN\\" . strtolower($this->childNameFilter($childName)) . "-message.php",
        ];
        $isExistFile    = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetMessageFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option);
                $ContentReplace->forNameLower($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetMessageFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setRoute($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $root           = $this->opt['root_path'];
        $domainPath     = $this->opt['domain_path'];

        //默认-配置路径
        $currGetPath = $root . "\\route\\" . ($moduleDomeName) . ".php";
        $currPutPath = $root . "\\route\\" . ($moduleName) . ".php";

        //兼容多应用模式-需要在配置里注明
        $auto_multi_app = $this->opt['auto_multi_app'];
        if ($auto_multi_app) {
            //多应用模式-配置路径
            $currGetPath = $domainPath . "\\" . $moduleDomeName . "\\route\\" . ($moduleDomeName) . ".php";
            $currPutPath = $domainPath . "\\" . $moduleName . "\\route\\" . ($moduleName) . ".php";
        }

        //配置路径
        $option = [
            "module_name" => $moduleName, "child_name" => $childName,
            "get_path"    => $currGetPath, "put_path" => $currPutPath,
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetRouteFile | route is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) {
                //替换指定文本
                $ContentReplace = new ContentReplace($option);
                $ContentReplace->forRouteName($content);
                return $content;
            });
            $msg = "Created : Module " . $moduleName . ' | SetRouteFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function changeModelFields($moduleName, $childName, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleName . "\\model\\" . ucwords($childName) . "Model.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\model\\" . ucwords($childName) . "Model.php",
        ];
        $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOptStr) {
            //修改默认数据库连接配置,用于跨库查询
            if ($dbOptStr) {
                $this->changeDbOption($dbOptStr, $opt);
            }
            //#
            $ContentReplace = new ContentReplace($option, $opt);
            //替换数据库配置 - 不需连数据库
            $ContentReplace->forDbOpt($dbOptStr, $content);
            //替换指定文本
            $ContentReplace->forNameNormal($content);
            //替换标签区域文本
            $ContentReplace->forGuarded($content);
            $ContentReplace->forTypes($content);
            return $content;
        });
        $msg = "Updated : " . $moduleName;
        $this->console($msg, "yellow");
        return true;
    }

    function changeTransformerFields($moduleName, $childName, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $domainPath     = $this->opt['domain_path'];
        $option         = [
            "get_path"    => $domainPath . "\\" . $moduleDomeName . "\\port\\trans\\" . ucwords($childDemoName) . "Trans.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $domainPath . "\\" . $moduleName . "\\port\\trans\\" . ucwords($childName) . "Trans.php",
        ];
        $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOptStr) {
            //修改默认数据库连接配置,用于跨库查询
            if ($dbOptStr) {
                $this->changeDbOption($dbOptStr, $opt);
            }
            //#
            $ContentReplace = new ContentReplace($option, $opt);
            //替换数据库配置 - 不需连数据库
            $ContentReplace->forDbOpt($dbOptStr, $content);
            //替换指定文本
            $ContentReplace->forNameNormal($content);
            //替换标签区域文本
            $ContentReplace->forGuarded($content);
            $ContentReplace->forTypes($content);
            return $content;
        });
        $msg = "Updated : " . $moduleName;
        $this->console($msg, "yellow");
        return true;
    }

    function makeFolder($moduleName, $message = null)
    {
        $childPathArr = ['config', 'console', 'port\\controller', 'error', 'enum', 'port\\request', 'job', 'model', 'srv', 'port\\logic', 'port\\trans'];

        $domainPath = $this->opt['domain_path'];
        $modulePath = $domainPath . "\\" . $moduleName;

        foreach ($childPathArr as $k => $v) {
            $childPath = $modulePath . "\\" . $v;

            $isExistFile = file_exists($childPath);
            if ($isExistFile) {
                if ($message) {
                    $msg = "Exception : Module " . $moduleName . " | MakeFolder | " . $v . " is exists !";
                    $this->console($msg, "red");
                }
            } else {
                $res = mkdir(iconv("UTF-8", "GBK", $childPath), 0755, true);
                if ($res) {
                    if ($message) {
                        $msg = "Created : Module " . $moduleName . " | MakeFolder | make " . $v . " folder OK";
                        $this->console($msg, "yellow");
                    }
                }
            }
            usleep(100);
        }

    }

    function makeFolderByArr($moduleName, $folders = [], $message = null)
    {
        if (empty($folders)) {
            $childPathArr = ['config', 'console', 'port\\controller', 'error', 'enum', 'port\\request', 'job', 'model', 'srv', 'port\\logic', 'port\\trans'];
        } else {
            $childPathArr = $folders;
        }

        $domainPath = $this->opt['domain_path'];
        $modulePath = $domainPath . "\\" . $moduleName;

        foreach ($childPathArr as $k => $v) {
            $childPath = $modulePath . "\\" . $v;

            $isExistFile = file_exists($childPath);
            if ($isExistFile) {
                if ($message) {
                    $msg = "Exception : Module " . $moduleName . " | MakeFolder | " . $v . " is exists !";
                    $this->console($msg, "red");
                }
            } else {
                $res = mkdir(iconv("UTF-8", "GBK", $childPath), 0755, true);
                if ($res) {
                    if ($message) {
                        $msg = "Created : Module " . $moduleName . " | MakeFolder | make " . $v . " folder OK";
                        $this->console($msg, "yellow");
                    }
                }
            }
            usleep(100);
        }

    }

    function makeTestFolder($moduleName, $message = null)
    {

        $childPathArr = [
            'port\\controller',
            //'behavior',
            'doc'
        ];

        $modulePath = $this->opt['test_path'] . "\\" . $moduleName;

        foreach ($childPathArr as $k => $v) {
            $childPath   = $modulePath . "\\" . $v;
            $isExistFile = file_exists($childPath);
            if ($isExistFile) {
                if ($message) {
                    $msg = "Exception : TestModule " . $moduleName . " | MakeFolder | " . $v . " is exists !";
                    $this->console($msg, "red");
                }
            } else {
                $res = mkdir(iconv("UTF-8", "GBK", $childPath), 0755, true);
                if ($res) {
                    if ($message) {
                        $msg = "Created : TestModule " . $moduleName . " | MakeFolder | make " . $v . " folder OK";
                        $this->console($msg, "yellow");
                    }
                }
            }
            usleep(100);
        }

    }

    function setTestFile($moduleName, $childName, $dbOn = null, $dbOptStr = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $testPath       = $this->opt['test_path'];
        $option         = [
            "get_path"    => $testPath . "\\" . $moduleDomeName . "\\controller\\" . ucwords($childDemoName) . "Test.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $testPath . "\\" . $moduleName . "\\controller\\" . ucwords($childName) . "Test.php"
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetUrlTestFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn, $dbOptStr) {
                //修改默认数据库连接配置,用于跨库查询
                if ($dbOptStr) {
                    $this->changeDbOption($dbOptStr, $opt);
                }
                //#
                $ContentReplace = new ContentReplace($option, $opt);
                //替换数据库配置 - 不需连数据库
                $ContentReplace->forDbOpt($dbOptStr, $content);
                //替换指定文本
                $ContentReplace->forNameHumpAndLower($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forTestInData($content);
                    $ContentReplace->forTestUpData($content);
                }
                return $content;
            });
            $msg = "Created : TestModule " . $moduleName . ' | SetUrlTestFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }

    function setTestBehaviorFile($moduleName, $childName, $dbOn = null)
    {
        $moduleDomeName = $this->moduleDomeName;
        $childDemoName  = $this->childDemoName;
        $opt            = $this->opt;
        $testPath       = $this->opt['test_path'];
        $option         = [
            "get_path"    => $testPath . "\\" . $moduleDomeName . "\\behavior\\" . ucwords($childDemoName) . "Behavior.php",
            "module_name" => $moduleName,
            "child_name"  => $childName,
            "put_path"    => $testPath . "\\" . $moduleName . "\\behavior\\" . ucwords($childName) . "Behavior.php"
        ];

        $isExistFile = file_exists($option['put_path']);
        if ($isExistFile) {
            $msg = "Exception : " . $moduleName . " | SetTestFile | is exists !";
            $this->console($msg, "red");
        } else {
            $this->fileEditCopy($option, function ($option, $content) use ($opt, $dbOn) {
                $ContentReplace = new ContentReplace($option, $opt);
                //替换指定文本
                $ContentReplace->forNameHumpAndLower($content);
                //替换注释标签
                $ContentReplace->forHiddenTag($content);
                if ($dbOn) {
                    //替换标签区域
                    $ContentReplace->forTestInList($content);
                }
                return $content;
            });
            $msg = "Created : TestModule " . $moduleName . ' | SetTestFile | OK';
            $this->console($msg, "yellow");
            return true;
        }
        return false;
    }


    //修改默认数据库连接,用于跨库查询
    function changeDbOption($dbOption, &$option = null)
    {
        $optStr = '' . $dbOption;
        $mysql  = Config::get()["$optStr"];

        if (!empty($mysql) && gettype($mysql) == 'string') {
            $mysql = $this->dbStrToArr($mysql);
        }
        if (!$mysql) {
            $msg = "Exception : ChangeDbOption | is fail !";
            $this->console($msg, "red");
            die;
        }

        $option['type']     = $mysql["type"];
        $option['hostname'] = $mysql["hostname"];
        $option['hostport'] = $mysql["hostport"];
        $option['database'] = $mysql["database"];
        if (!empty($mysql['prefix'])) {
            $option['prefix'] = $mysql["prefix"];
        }
        $option['username'] = $mysql["username"];
        $option['password'] = $mysql["password"];

        return $option;
    }


    //数据库配置文本转数组
    function dbStrToArr(string $str)
    {
        $mysql = null;
        preg_match('/^(\w+|\d+):\/\/(\w+|\d+):(\w+|\d+)@(\d+\S+):(\d+)\/(\w+|\d+|\D+)#(\w+|\d+)/is', $str, $match);
        if (isset($match[1])) {
            $mysql['type']     = $match[1] ?? '';
            $mysql['username'] = $match[2] ?? '';
            $mysql['password'] = $match[3] ?? '';
            $mysql['hostname'] = $match[4] ?? '';
            $mysql['hostport'] = $match[5] ?? '';
            $mysql['database'] = $match[6] ?? '';
        }
        //dd($str,$mysql);
        return $mysql;
    }


    function setCodeBlockCurr($codeBlockStr)
    {
        $codeBlockArr = explode(',', $codeBlockStr);
        //获取交集
        $codeBlockOpt                 = $this->opt['code_block'];
        $this->opt['code_block_curr'] = array_intersect($codeBlockOpt, $codeBlockArr);
    }

    function getCodeBlockCurr()
    {
        return $this->opt['code_block_curr'];
    }

    function getCodeBlock()
    {
        return $this->opt['code_block'];
    }

}