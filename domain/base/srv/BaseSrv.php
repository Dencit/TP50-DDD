<?php

namespace domain\base\srv;

use domain\base\error\BaseError;
use domain\base\exception\Exception;

/**
 * notes: 领域层-业务类
 * desc: 当不同 应用端/模块 的 应用层-业务类,对同一个表数据(或第三方API)进行操作, 该表的操作代码分散在多个应用端中且冗余, 就需要抽象到这一层.
 * 领域层-业务类 允许 被 跨应用端/模块 调用, 而 各应用层-业务 则保持隔离, 避免应用层业务耦合.
 * 调用原则: 向下调用[仓储类,第三方服务-SDK]
 */
class BaseSrv
{
    //订单序列号
    public static function SerialNumber($prefix = null, $useId = null)
    {

        $dateTime = self::udate("YmdHis");
        $nicoTime = self::udate("u");
        $randA    = rand(0, 9999);
        $randB    = rand(0, 9999);

        $prefix = empty($prefix) ? 100 : $prefix;
        $useId  = empty($useId) ? $nicoTime : $useId;
        $useId  = substr($useId, -4);

        //3位数渠道号 + 日月时分秒 + 随机1位数A + 用户id后4位 + 随机1位数B
        $num = $prefix . $dateTime . $randA . $useId . $randB;
        //dd($num);//

        return $num;
    }

    //生成微秒
    public static function udate($strFormat = 'u', $uTimeStamp = null)
    {
        // If the time wasn't provided then fill it in
        if (is_null($uTimeStamp)) {
            $uTimeStamp = microtime(true);
        }
        // Round the time down to the second
        $dtTimeStamp = floor($uTimeStamp);
        // Determine the millisecond value
        $intMilliseconds = round(($uTimeStamp - $dtTimeStamp) * 1000000);
        // Format the milliseconds as a 6 character string
        $strMilliseconds = str_pad($intMilliseconds, 6, '0', STR_PAD_LEFT);
        // Replace the milliseconds in the date format string
        // Then use the date function to process the rest of the string
        return date(preg_replace('`(?<!\\\\)u`', $strMilliseconds, $strFormat), $dtTimeStamp);
    }

    //json 格式检查
    public static function jsonCheck($jsonStr)
    {
        $data = json_decode($jsonStr, true);
        if (empty($data)) {
            Exception::app(BaseError::code("WRONG_JSON_FORMAT"), BaseError::msg("WRONG_JSON_FORMAT"), __METHOD__);
        }
        return $data;
    }

    //过滤url链接中的 根域名, 返回相对路径.
    public static function urlPathFilter($url, &$match = null)
    {
        $path = $url;
        preg_match("/^http.*(\.com|\.cn)(.*$)/", $url, $match);
        if (isset($match[2])) {
            $path = $match[2];
        }
        return $path;
    }

}