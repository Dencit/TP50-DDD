<?php

namespace domain\base\enum;

class BaseEnum
{
    /**
     * notes: 合计年区间数
     * @param string $beginDateTime
     * @param string|null $endDateTime
     * @param string $unit
     * @return int|string
     * @author 陈鸿扬 | @date 2022/10/14 12:17
     */
    public static function totalYearInterval(string $beginDateTime, string $endDateTime = null, string $unit = '')
    {
        $beginTime = strtotime($beginDateTime);
        $endTime = strtotime($endDateTime);
        if (empty($endDateTime)) {
            $endTime = time();
        }
        $betweenYear = (int)floor(($endTime - $beginTime) / 31536000);
        if (!empty($unit)) {
            $betweenYear .= $unit;
        }
        return $betweenYear;
    }
}