<?php
/**
 * notes: Json处理工具
 * @author 陈鸿扬 | @date 2022/5/12 18:50
 */
namespace extend\log;

class JsonTool
{
    //数组转Json格式化文本 - 高效版,只处理3层
    public static function fString(array $data)
    {
        //第1层
        $fString = '';
        array_walk($data, function ($item, $key) use (&$fString) {

            //第2层
            if (gettype($item) == 'array') {
                $fString .= '  "' . $key . '":{' . PHP_EOL;
                array_walk($item, function ($item1, $key1) use (&$fString) {

                    //第3层
                    if (gettype($item1) == 'array') {
                        $fString .= '    "' . $key1 . '":{' . PHP_EOL;
                        array_walk($item1, function ($item2, $key2) use (&$fString) {
                            $fString .= '      "' . $key2 . '":' . json_encode($item2, JSON_UNESCAPED_UNICODE) . ',' . PHP_EOL;
                        });
                        $fString = rtrim($fString, "," . PHP_EOL);
                        $fString .= PHP_EOL . '    },' . PHP_EOL;
                    } else {
                        $fString .= '    "' . $key1 . '":' . json_encode($item1, JSON_UNESCAPED_UNICODE) . ',' . PHP_EOL;
                    }
                    //#

                });
                $fString = rtrim($fString, "," . PHP_EOL);
                $fString .= PHP_EOL . '  },' . PHP_EOL;

            } else {
                $fString .= '  "' .$key. ":" . json_encode($item, JSON_UNESCAPED_UNICODE) . "," . PHP_EOL;
            }
            //#

        });
        if (!empty($fString)) {
            $fString = rtrim($fString, "," . PHP_EOL);
            $fString = '{' . PHP_EOL . $fString . PHP_EOL . "}";
        }
        //#

        //恢复json_encode转义的字符
        $fString = str_replace("\/","/",$fString);

        //dd($fString,preg_replace("/\\r\\n|\s+/i",'',$fString), json_encode($data,JSON_UNESCAPED_UNICODE));
        //dd($fString,json_decode( preg_replace("/\\r\\n|\s+/i",'',$fString),true ) ,json_encode($data));
        return $fString;
    }

    //Json文本美化 - 低效版
    public static function stringFormat(array $data, $indent = null)
    {
        //处理非数组内容
        if (gettype($data) != 'array') {
            $data = json_decode(json_encode($data, JSON_UNESCAPED_UNICODE), true);
        }

        //递归转码-防json转义
        array_walk_recursive($data, function (&$val) {
            if (gettype($val) == 'string') {
                $val = urlencode($val);
            }
        });
        //转json字符串
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        //转码恢复
        $data = urldecode($data);

        // 缩进处理
        $ret         = '';
        $pos         = 0;
        $length      = strlen($data);
        $indent      = isset($indent) ? $indent : '    ';
        $newline     = "\n";
        $prevchar    = '';
        $outofquotes = true;
        for ($i = 0; $i <= $length; $i++) {
            $char = substr($data, $i, 1);
            if ($char == '"' && $prevchar != '\\') {
                $outofquotes = !$outofquotes;
            } elseif (($char == '}' || $char == ']') && $outofquotes) {
                $ret .= $newline;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $ret .= $indent;
                }
            }
            $ret .= $char;
            if (($char == ',' || $char == '{' || $char == '[') && $outofquotes) {
                $ret .= $newline;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $ret .= $indent;
                }
            }
            $prevchar = $char;
        }
        return $ret;
    }

}