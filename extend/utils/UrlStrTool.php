<?php

namespace extend\utils;

/**
 * 文本链接处理工具
 * Class UrlStrTool
 * @package extend\utils
 */
class UrlStrTool
{
    /**
     * 截取 文本链接中的参数 输出 字典
     * @param string $url
     * @return array
     */
    public static function getQueryMap(string $url)
    {
        $queryStr = substr($url, strripos($url, '?'));
        $queryStr = str_replace('?', '', $queryStr);
        $queryArr = explode('&', $queryStr);
        $queryMap = [];
        array_walk($queryArr, function ($value) use (&$queryMap) {
            $currArr = explode('=', $value);
            if (!empty($currArr[1])) {
                //过滤不正常的key名
                preg_match("/\\;|\\,/", $currArr[0], $m);
                if (!isset($m[0])) {
                    $queryMap[$currArr[0]] = $currArr[1];
                }
            }
        });
        ksort($queryMap);
        //dd($url, $queryArr, $queryMap);
        return $queryMap;
    }

    /**
     * 根据参数字典 输出 链接参数
     * @param array $queryMap
     * @return string
     */
    public static function buildHttpQuery(array $queryMap)
    {
        $http_build_query = '';
        if (!empty($queryMap)) {
            $http_build_query = http_build_query($queryMap);
        }
        return $http_build_query;
    }

}