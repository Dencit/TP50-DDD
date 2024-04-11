<?php

namespace domain\base\tran;

class CommonTran extends Tran
{
    public function transform($object)
    {
        $data = is_array($object) ? $object : json_decode(json_encode($object),true);

        if (isset($data['create_time']))
            $data['create_time'] = (string)$data['create_time'];

        if (isset($data['update_time']))
            $data['update_time'] = (string)$data['update_time'];

        return $data;
    }

    //获取 header VERSION - APP_1.0.0_ID_13
    protected function getVersion($header){
        $verData = [
            'body'=>'NONE_0.0.0',
            'client'=>'none','version'=>'0.0.0',
            'id'=>0
        ];
        if( isset($header['version']) ){
            $version = explode('_',$header['version']);
            if( isset($version[0])&&isset($version[1]) ){
                $verData['body']= ($version[0]).'_'.($version[1]);
            }
            if( isset($version[0]) ){ $verData['client'] = $version[0]; }
            if( isset($version[1]) ){ $verData['version'] = $version[1]; }
            if( isset($version[2])&&isset($version[3]) ){
                $key = strtolower( $version[2] );
                $verData[  $key ] = $version[3];
            }
        }
        return $verData;
    }

    /*
     * notes: 计算两点地理坐标之间的距离 - 已经调较到和ES计算结果相等
     * @author 陈鸿扬 | @date 2021/1/5 12:48
     * @param $longitude1 起点经度
     * @param $latitude1 起点纬度
     * @param $longitude2 终点经度
     * @param $latitude2 终点纬度
     * @param Int $unit 单位 1:米 2:公里
     * @param Int $decimal 精度 保留小数位数
     * @return float
     */
    protected function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){
        $EARTH_RADIUS = 6371.0069757804; //地球半径系数
        $PI = 3.14159265358979323846;
        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;
        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $distance = $distance * $EARTH_RADIUS * 1000;
        if($unit==2){
            $distance = $distance / 1000;
        }
        return round($distance, $decimal);
    }

}