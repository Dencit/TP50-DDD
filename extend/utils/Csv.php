<?php

namespace extend\utils;

/**
 * notes: Csv类
 * @author 陈鸿扬 | @date 2021/9/12 18:14
 * Class Csv
 * @package App\Tool
 */
class Csv
{
    //读取 转换好键值对 的 Csv数据
    public static function listDataLoad(string $fullPath, $titleIndex = 0)
    {
        $listData = [];
        $result   = self::loadCsvData($fullPath);
        //dd($result["$titleIndex"]);//
        if (isset($result[$titleIndex])) {
            $titleKeys = array_values($result[$titleIndex]);
            array_walk($result, function ($item, $index) use ($titleKeys, $titleIndex, &$listData) {
                if ($index > $titleIndex) {
                    $newItem = [];
                    array_walk($titleKeys, function ($key, $ind) use ($item, &$newItem) {
                        //恢复csv文件内,字段值的换行符.
                        $item["$ind"] = str_replace("\\r\\n", PHP_EOL, $item["$ind"]);
                        //设置新结构.
                        $newItem["$key"] = $item["$ind"];
                    });
                    if (!empty($newItem)) {
                        $listData[] = $newItem;
                    }
                }
            });
        }
        return $listData;
    }

    //读取原始Csv数据
    public static function loadCsvData(string $fullPath)
    {
        $result   = [];
        $filePath = $fullPath . ".csv";
        if (is_file($filePath)) {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                while ($data = fgetcsv($handle)) {
                    $result[] = mb_convert_encoding($data, "UTF-8", "GBK");
                }
                fclose($handle);
            }
        }
        return $result;
    }

    /*
     * notes: 列表数据转Csv
     * @author 陈鸿扬 | @date 2021/3/8 16:15
     */
    public static function listDataExport($data, $tableHeader, $fileName, $format = 'csv')
    {
        header("Content-Type: application/force-download");
        header('Content-Encoding:UTF-8');

        $file_name = $fileName . "." . $format;
        header('Content-Disposition:attachment;filename="' . $file_name . '"');

        //with Bom
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);

        //设置中文表头
        $tHeader = implode(',', array_values($tableHeader));
        echo $tHeader . PHP_EOL;
        ob_end_flush();

        $keyArr = array_keys($tableHeader);
        foreach ($data as $index => $arr) {
            $currRow = '';
            foreach ($keyArr as $ind => $key) {
                //符号处理 - 转全角 - 避免破坏csv结构
                preg_match("/,/", $arr["$key"], $comma);
                if (isset($comma[0])) {
                    $arr["$key"] = str_replace(',', '，', $arr["$key"]);
                }
                preg_match("/\"/", $arr["$key"], $quota);
                if (isset($quota[0])) {
                    $arr["$key"] = str_replace('"', '”', $arr["$key"]);
                }

                //空值处理
                if (empty($arr["$key"]) && $arr["$key"] !== 0 && $arr["$key"] !== 0.0) {
                    $currRow .= "" . $arr["$key"] . "-" . ',';
                } else {
                    $currRow .= $arr["$key"] . ',';
                }
            }
            $currRow = trim($currRow, ',');
            echo $currRow . PHP_EOL;
            flush();
        }

        exit;
    }

    /*
    * notes: 列表数据转Csv 保存到目录
    * @author 陈鸿扬 | @date 2021/3/8 16:15
    */
    public static function listDataSaveFile(array $data, $path, $fileName, $format = 'csv')
    {

        if (empty($data)) {
            $data = [
                ['标题' => '无数据']
            ];
        }

        //待写入cvs文本
        $content = chr(0xEF) . chr(0xBB) . chr(0xBF);

        //设置中文表头
        $tableHeader = $data[0];

        $tHeader = implode(',', array_keys($tableHeader));
        $content .= $tHeader . PHP_EOL;

        $keyArr = array_keys($tableHeader);
        foreach ($data as $index => $arr) {
            $currRow = '';
            foreach ($keyArr as $ind => $key) {
                //符号处理 - 转全角 - 避免破坏csv结构
                preg_match("/,/", $arr["$key"], $comma);
                if (isset($comma[0])) {
                    $arr["$key"] = str_replace(',', '，', $arr["$key"]);
                }
                preg_match("/\"/", $arr["$key"], $quota);
                if (isset($quota[0])) {
                    $arr["$key"] = str_replace('"', '”', $arr["$key"]);
                }

                //空值处理
                if (empty($arr["$key"]) && $arr["$key"] !== 0 && $arr["$key"] !== 0.0) {
                    $currRow .= "" . $arr["$key"] . "-" . ',';
                } else {
                    $currRow .= $arr["$key"] . ',';
                }
            }
            $currRow = trim($currRow, ',');
            $content .= $currRow . PHP_EOL;
        }

        //设置保存的路径
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $pathFileName = $path . $fileName . '.' . $format;

        //写入目标
        $handle = fopen($pathFileName, "w") or die("Unable to open file!");
        fwrite($handle, $content);
        fclose($handle);
    }


}
