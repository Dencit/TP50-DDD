<?php

namespace extend\log;

class backTrace
{
    public static function run($filter = '', $debug = false)
    {
        //过滤展示内容
        $filterDef = ["anchor_file", "anchor_param", "anchor_args"];
        if ($filter == 'line') {
            $filterDef = ["anchor_file", "anchor_line", "anchor_param", "anchor_args"];
        }
        if ($filter == 'param') {
            $filterDef = ["anchor_file", "anchor_param"];
        }
        if ($filter == 'args') {
            $filterDef = ["anchor_file", "anchor_param", "anchor_args"];
        }
        if ($filter == 'context') {
            $filterDef = ["anchor_file","anchor_param", "anchor_args", "anchor_context"];
        }
        if ($filter == 'all') {
            $filterDef = ["anchor_file", "anchor_line", "anchor_param", "anchor_args", "anchor_context"];
        }
        $filter = $filterDef;

        //调用链路
        $traceArr = debug_backtrace(1, 1000);
        //初始参数
        $finalArr               = [];
        $finalArr['trace_code'] = [];
        if ($debug == true) {
            $finalArr['trace'] = [];
        }
        //时机处理 - 注意链路是颠倒的
        $start = false;
        foreach ($traceArr as $ind => $row) {
            //结束时机处理 - 注意链路是颠倒的
            if (isset($row['file'])) {
                preg_match("/(\\\\think\\\\App)/i", $row['file'], $m);
                if (!empty($m[0]) && $row['function'] == 'invokeArgs'
                ) {
                    $start = false;
                }
            }

            //# 链路处理 - class/function/type/args
            if ($start) {
                //新结构
                $newRow = [
                    "anchor_file"    => '',
                    "anchor_line"    => '',
                    "anchor_args"    => '',
                    "anchor_param"   => '',
                    "anchor_context" => '',
                ];
                if (isset($row['file']) && isset($row['class']) && isset($row['function'])) {
                    $newRow['anchor_file']    = $row['file'] . ":" . $row['line'];
                    $content                  = self::getCodeInfo($row['file'], $row['line'] - 2, $row['line'] + 2, $row['line']);
                    $newRow['anchor_line']    = $content;
                    $newRow['anchor_param']   = $row['class'] . $row['type'] . $row['function'];
                    $nextContent              = self::getMethodCode($row["class"], $row['function']);
                    $newRow['anchor_context'] = $nextContent;
                } else if (isset($row['file']) && !isset($row['class']) && isset($row['function'])) {
                    $newRow['anchor_file']  = $row['file'] . ":" . $row['line'];
                    $content                = self::getCodeInfo($row['file'], $row['line'] - 2, $row['line'] + 2, $row['line']);
                    $newRow['anchor_line']  = $content;
                    $newRow['anchor_param'] = $row['function'];
                } else if (isset($row['file']) && !isset($row['class']) && !isset($row['function'])) {
                    $newRow['anchor_file'] = $row['file'] . ":" . $row['line'];
                    $content               = self::getCodeInfo($row['file'], $row['line'] - 2, $row['line'] + 2, $row['line']);
                    $newRow['anchor_line'] = $content;
                } else if (!isset($row['file']) && isset($row['class']) && isset($row['function'])) {
                    $newRow['anchor_param']   = $row['class'] . $row['type'] . $row['function'];
                    $nextContent              = self::getMethodCode($row["class"], $row['function']);
                    $newRow['anchor_context'] = $nextContent;
                } else if (!isset($row['file'])) {
                    $newRow['anchor_param'] = $row['class'] . $row['type'] . $row['function'];
                }
                $newRow['anchor_args'] = $row['args'];
                //参数处理
                self::argsFilter($row, $newRow);
                //输出过滤
                array_walk($newRow, function ($item, $key) use (&$newRow, $filter) {
                    if (!in_array($key, $filter)) {
                        unset($newRow["$key"]);
                    }
                });
                //输出
                if ($debug == true) {
                    array_unshift($finalArr['trace'], $row);
                }
                array_unshift($finalArr['trace_code'], $newRow);
                //调试用
//                if($row['function']=="bt"){
//                    dd($row,$newRow);
//                }
//                if($ind==10){
//                    dd($row,$newRow);
//                }
            }
            //#


            //开始时机处理 - 注意链路是颠倒的
            if (isset($row['file'])) {
                preg_match("/(\\\\application\\\\common)/i", $row['file'], $m);
                if (!empty($m[0]) && $row['function'] == 'run'
                ) {
                    $start = true;
                }
            }
        }

        return $finalArr;
    }


    /**
     * 获取指定行内容
     * @param $fileName - 文件路径
     * @param $startLine - 起始行位置
     * @param $endLine - 结束行位置
     * @param $anchorLine - 锚点行位置
     * @return array
     */
    public static function getCodeInfo($fileName, $startLine, $endLine, $anchorLine = 0)
    {
        $returnArr = []; // 初始化返回
        $i         = 1; // 行数
        //
        $handle = @fopen($fileName, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if ($i >= $startLine && $i <= $endLine) {
                    //转换非标准换行符
                    $buffer = preg_replace("/\\\n\\\n/i", PHP_EOL, $buffer);
                    //标准换行符-清除
                    $buffer = preg_replace("/" . PHP_EOL . "/i", '', $buffer);
                    //遗留非标准换行符-清除
                    $buffer = preg_replace("/\\\n/i", '', $buffer);
                    //填充空格-保持长度一致
                    $buffer = str_pad($buffer, 120, ' ');
                    //加锚点标识
                    if ($i == $anchorLine) {
                        $buffer = preg_replace("/^\s{4}/i", '', $buffer);
                        $buffer = "==> " . $buffer;
                    }
                    $returnArr[$i] = $buffer;
                }
                $i++;
            }
            fclose($handle);
        }
        return $returnArr;
    }

    public static function getMethodCode($class, $method)
    {
        //排除闭包函数
        preg_match("/\\{closure\\}/i", $method, $match);
        //不处理闭包函数
        if (!empty($class) && empty($match[0])) {
            $method    = new \ReflectionMethod($class, $method);
            $fileName  = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine   = $method->getEndLine();
            //
            $codeInfo = self::getCodeInfo($fileName, $startLine, $endLine);
        } else {
            $codeInfo = ["{closure}"];
        }
        return $codeInfo;
    }

    //
    public static function argsFilter($row, &$newRow)
    {
        if (!empty($row['args'])) {
            $paramMethods = [];

            foreach ($row['args'] as $index => $group) {

                //获取参数类型
                $groupChild = [];
                $method     = gettype($group);
                switch ($method) {
                    case "boolean":
                    case "integer":
                    case "string":
                        $paramMethods[] = $group;
                        break;
                    case "object":
                    case "array":
                        $groupChild = $group;
                        break;
                }

                //批处理
                if (!empty($groupChild)) {
                    $methodParamStr = "";
                    foreach ($groupChild as $ind => $item) {
                        if (gettype($item) == "string" && gettype($ind) == "integer") {
                            $methodParamStr .= ", '" . $item . "'";
                        }
                        if (gettype($item) == "string" && gettype($ind) == "string") {
                            $methodParamStr .= ", '" . $ind . "'=>'" . $item . "'";
                        }
                        if (gettype($item) == "array" && gettype($ind) == "integer") {
                            $methodParamStr .= ", ['" . implode("','", $item) . "']";
                        }
                        if (gettype($item) == "array" && gettype($ind) == "string") {
                            //省略下一级
                            $childKey = array_keys($item)[0] ?? [];
                            if (gettype($childKey) == 'string') {
                                $item = ['...'];
                            }
                            $methodParamStr .= ", '" . $ind . "'=>['" . implode("','", $item) . "']";
                        }
                        if (gettype($item) == "object" && gettype($ind) == "integer") {
                            $methodParamStr .= ", " . get_class($item) . "{}" . "";
                        }
                        if (gettype($item) == "object" && gettype($ind) == "string") {
                            $methodParamStr .= ", " . get_class($item) . "{}" . "";
                        }
                    }
                    //$paramMethods[] = $method . str_replace("'", "", json_encode($methodParamStr));
                    if ($method == "array") {
                        $paramMethods[] = "[" . trim($methodParamStr, " ,") . "]";
                    } else {
                        $paramMethods[] = $method . "{" . $methodParamStr . "}";
                    }
                }
            }

            $newRow['anchor_param'] .= "( " . implode(",", $paramMethods) . " )";

        } else {
            $newRow['anchor_param'] .= "()";
        }
    }

}