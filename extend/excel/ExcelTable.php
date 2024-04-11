<?php
namespace extend\excel;

use domain\base\error\BaseError;
use domain\base\exception\Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use think\facade\Filesystem;

class ExcelTable
{
    //https://blog.csdn.net/gc258_2767_qq/article/details/81003656

    protected $excelData=[];

    public function __construct(){

    }

    public function getUpLoadExcel(){

        $file = request() -> file('file');
        if ($file == null) {
            Exception::http(BaseError::code('UPLOAD_FILE_NOT_FOUND'),BaseError::msg('UPLOAD_FILE_NOT_FOUND'));
        }
        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);
        if( !in_array( $extension, ["xlsx","xls"] ) ){
            Exception::http(BaseError::code('UPLOAD_FILE_NOT_EXCEL'),BaseError::msg('UPLOAD_FILE_NOT_EXCEL'));
        }
        $saveName = Filesystem::disk('local') -> putFile('local', $file);

        $saveName = str_replace('\\','/',$saveName);
        $fullPath = config('filesystem.disks.local.root').'/'.$saveName;
        $file_exists = file_exists( $fullPath );
        if(!$file_exists){
            Exception::http(BaseError::code('UPLOAD_FILE_NOT_FOUND'),BaseError::msg('UPLOAD_FILE_NOT_FOUND'));
        }
        unlink($fullPath); //清除上传表格

        return $file;
    }

    public function listDataExport($data,$fileName,$tableHeader,$tableSheet='data'){

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Admin")    //作者
            ->setLastModifiedBy("Admin") //最后修改者
            ->setTitle("Office 2007 XLSX Document")  //标题
            ->setSubject("Office 2007 XLSX Document") //副标题
            ->setDescription("document for Office 2007 XLSX, generated using PHP classes.")  //描述
            ->setKeywords("office 2007 openxml php") //关键字
            ->setCategory("file"); //分类

        //居中设置
        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];

        //列结构数据
        $column = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $keys = array_keys($tableHeader);

        //设置当前工作表标题
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle( $tableSheet );

        //设置中文表头
        $row = 1; $i=0;
        foreach ($data[0] as $k=>$v){
            if( in_array($k,$keys) ){
                $col = $column[$i];

                $worksheet->getStyle( $col.$row )->getFont()->setBold(true);
                $worksheet->getStyle( $col.$row )->getAlignment()->setWrapText(true);
                $worksheet->getStyle( $col.$row )->applyFromArray($styleArray);

                $worksheet->getColumnDimension( $col )->setAutoSize(true);

                $worksheet->setCellValue( $col.$row, $tableHeader[$k] );

                $i++;
            }
        }
        //设置英文表头
        $row = 2; $i=0;
        foreach ($data[0] as $k=>$v){
            if( in_array($k,$keys) ) {
                $col = $column[$i];
                $worksheet->setCellValue($col.$row, $k);

                $worksheet->getStyle( $col.$row )->getFont()->setBold(true);
                $worksheet->getStyle( $col.$row )->getAlignment()->setWrapText(true);
                $worksheet->getStyle( $col.$row )->applyFromArray($styleArray);
                $worksheet->getStyle( $col.$row )->getFont()->getColor()->setARGB( Color::COLOR_RED );

                $i++;
            }
        }

        foreach ($data as $index => $arr) { $row++;
            $j=0;
            foreach ($arr as $m => $n) {
                if( in_array($m,$keys) ) {
                    $col = $column[$j];

                    preg_match('/^0[\d]+/is',$n,$zeroM);
                    if( isset($zeroM[0]) ){
                        $worksheet->getStyle( $col.$row )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_TEXT );
                    }
                    preg_match('/\d{4}-\d{2}-\d{2}/is',$n,$dateM);
                    if( isset($dateM[0]) ){
                        $worksheet->getStyle( $col.$row )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_TEXT );
                    }
                    preg_match('/^[\d]+$/is',$n,$numM);
                    if( isset($numM[0]) && strlen($n)>11 ){
                        $worksheet->getStyle( $col.$row )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
                        $n="'".$n."'";
                        $worksheet->getCell($col.$row)->setValueExplicit( $n ,DataType::TYPE_STRING);
                    }

                    $worksheet->getStyle( $col.$row )->getAlignment()->setWrapText(true);
                    $worksheet->getStyle( $col.$row )->applyFromArray($styleArray);
                    $worksheet->setCellValue( $col.$row, $n );
                    $j++;
                }
            }
        }

        //第一种保存方式
        //$writer = new Xlsx($spreadsheet);
        //保存的路径可自行设置
        //$file_name = '../'.$fileName . ".xlsx";
        //$writer->save($file_name);

        //第二种直接页面上显示下载
        $file_name = $fileName . ".xlsx";
        header('Content-Encoding: UTF-8');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$file_name.'"');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        //注意createWriter($spreadsheet, 'Xls') 第二个参数首字母必须大写
        $writer->save('php://output'); exit();

    }

    public function excelFileImport($fileData){
        $info=null;

        $reader = IOFactory::createReader('Xlsx');
        if ( !$reader->canRead($fileData) ) {
            $reader = IOFactory::createReader('Xls');
            if (!$reader->canRead($fileData)) {
                Exception::http(BaseError::code('UPLOAD_EXCEL_ONLY'),BaseError::msg('UPLOAD_EXCEL_ONLY'));
            }
        }
        $reader->setReadDataOnly(true);//只读
        $spreadsheet = $reader->load($fileData);

        $sheet = $spreadsheet->getSheet(0); // 读取第一個工作表
        $highest_row = $sheet->getHighestRow(); // 取得总行数
        $highest_column = $sheet->getHighestColumn(); ///取得总列数

        $highestColumnIndex = Coordinate::columnIndexFromString($highest_column);  //转化为数字;
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            for ($j = 1; $j <= $highest_row; $j++) {
                $content = $sheet->getCellByColumnAndRow($i, $j)->getCalculatedValue();

                if( is_float($content) ){
                    $content = (string)$content;
                }

                //处理日期
                preg_match('/^\d{5}\\.\d{9}$/is',$content,$dateM);
                if(  isset($dateM[0]) ){
                    $toTimestamp = Date::excelToTimestamp($content);
                    $date = date("Y-m-d H:i:s", $toTimestamp );
                    $content = $date;
                }

                //处理科学计数法
                preg_match('/\\.[\d]+E\\+/is',$content,$bigIntM);
                if(  isset($bigIntM[0]) ){
                    $content = $this->NumToStr($content);
                }

                //超过11位的数字加上单引号, 能避免被 转excel取整转成科学计数法
                if( strlen($content)>11 ){ $content= trim($content,"''"); }
                $content= trim($content,"''");

                $info[$j-1][$i-1] = (string) $content;
            }
        }

        $this->excelData=$info;
        return $this;
    }

    public function toArray($startRow=1){
        $keys = array_keys( array_flip($this->excelData[$startRow]) );
        $data = [];
        foreach ( $this->excelData as $ind=>$arr ){
            if( $ind >= $startRow+1 ){
                $child = [];
                foreach ( $arr as $m=>$n ){ $child[ $keys[$m] ] = $n; }
                $data[] = $child;
            }
        }
        return $data;
    }

    function NumToStr($num){
        if (stripos($num,'E')===false) return $num;
        $num = trim(preg_replace('/[=\'"]/','',$num,1),'"');//出现科学计数法，还原成字符串
        $result = "";
        while ($num > 0){
            $v = $num - floor($num / 10)*10;
            $num = floor($num / 10);
            $result   =   $v . $result;
        }
        return $result;
    }

}