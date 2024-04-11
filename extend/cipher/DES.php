<?php
namespace extend\cipher;

class DES
{
    protected $method;

    protected $secret_key;
    protected $iv;
    protected $options;

    public function __construct($key, $method = 'des-ecb', $iv = '', $options = 0)
    {
        // key是必须要设置的
        $this->secret_key = isset($key) ? $key : 'none';
        $this->method = $method;
        $this->iv = mb_substr($iv,0,8,'UTF8');
        $this->options = $options;
    }

    public function encrypt($string){
        switch ($this->method){
            default: $result = null; break;
            case 'des-ecb':
                $result = $this->ecbEncrypt($string);
                break;
            case 'des-cbc':
                $result = $this->cbcEncrypt($string);
                break;

        }
        $result = base64_encode($result); //输出转义
        return $result;
    }

    public function decrypt($string){
        $string = base64_decode($string); //输入转义
        switch ($this->method){
            default: $result = null; break;
            case 'des-ecb':
                $result = $this->ecbDecrypt($string);
                break;
            case 'des-cbc':
                $result = $this->cbcDecrypt($string);
                break;
        }
        return $result;
    }


    public function ecbEncrypt($data){
        return openssl_encrypt($data, $this->method, $this->secret_key);
    }
    public function ecbDecrypt($data){
        return openssl_decrypt($data, $this->method, $this->secret_key);
    }

    public function cbcEncrypt($data){
        return openssl_encrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }
    public function cbcDecrypt($data){
        return openssl_decrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }


}