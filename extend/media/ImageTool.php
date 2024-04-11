<?php
namespace extend\media;


use domain\base\base\error\BaseError;
use domain\base\base\exception\Exception;

class ImageTool{

    public  $img;
    public  $mime;
    public  $ext;

    public function __construct(){
    }

    //获取图片 输出压缩对象
    public function compress(  $name, $width = 600, $height = -1, $degrees = 0 ){
        if($width>2048){ $width=2048; }
        if($height>2048){ $height=2048; }
        $degreesParam=['90','-90','-180','180',];
        if( !in_array($degrees,$degreesParam) ){ $degrees = 0; }

        $saveName = str_replace('\\','/',$name);
        $fullPath = config('filesystem.disks.public.root').'/'.$saveName;
        $defaultPath = __DIR__.'/image/loading.jpg';
        $file_exists = file_exists( $fullPath );
        if( !$file_exists ){ $fullPath=$defaultPath; };

        $img_info = getimagesize($fullPath);
        $mime = $img_info['mime'];
        $ext = explode('/',$mime)[1];
        switch ($ext){
            default: //Exception::http(BaseError::code('IMAGE_EXT_FAIL'),BaseError::msg('IMAGE_EXT_FAIL')); break;
            case 'jpg':
            case 'jpeg': $img = imagecreatefromjpeg($fullPath); break;
            case 'png': $img = imagecreatefrompng($fullPath); break;
            case 'gif': $img = imagecreatefromgif($fullPath); break;
        }

        $img = imagescale($img,$width,$height);
        $img=imagerotate($img, $degrees, 0);

        $this->mime = $mime;
        $this->ext = $ext;
        $this->img = $img;

        //获取base图片数据
        switch ( $this->ext ){
            default: //Exception::http(BaseError::code('IMAGE_EXT_FAIL'),BaseError::msg('IMAGE_EXT_FAIL')); break;
            case 'jpg': case 'jpeg': $baseData=imagejpeg( $this->img,null,80 ); break;
            case 'png': $baseData=imagepng( $this->img,null,7  ); break;
            case 'gif': $baseData=imagegif( $this->img ); break;
        }
        $this->data = $baseData;
        //卸载内存
        imagedestroy( $this->img );

        return $this;
    }

    public function outPut(){
        header('Content-type: '.$this->mime );

        //打印base图片数据
        echo $this->data;

        exit();
    }


}