<?php
namespace domain\base\exception;

use think\exception\HttpException;
use think\Log;

class Exception
{
     public static function http($error_code,$msg,$method=__METHOD__){
         $method = str_replace("\\", "/",$method);
         $errMsg = PHP_EOL
             .'{'
                 .'"http_exception":'
                     .'{'
                        .'"code":"'.$error_code.'",'
                        .'"msg":"'.$msg.'",'
                        .'"method":"'.$method.'"'
                    .'}'
             .'}';

         Log::record($errMsg,'notice');
         throw new HttpException( $error_code,$msg );
     }

    public static function app($error_code,$msg,$method=__METHOD__){
        $method = str_replace("\\", "/",$method);
        $errMsg = PHP_EOL
            .'{'
                .'"app_exception":'
                    .'{'
                        .'"code":"'.$error_code.'",'
                        .'"msg":"'.$msg.'",'
                        .'"method":"'.$method.'"'
                    .'}'
            .'}';

        Log::record($errMsg,'notice');
        throw new HttpException( $msg, $error_code );
    }
    
}