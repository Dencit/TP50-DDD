<?php
namespace extend\cipher;


class RSA{

    protected $publicKey=null;
    protected $privateKey=null;

    public function __construct(){ }

    public function setPublicKey($keyContent){
        $this->publicKey=$keyContent;
        return $this;
    }

    public function setPrivateKey($keyContent){
        $this->privateKey=$keyContent;
        return $this;
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    private function getPublicKey(){
        return openssl_pkey_get_public($this->publicKey);
    }

    /**
     * 获取私钥
     * @return bool|resource
     */
    private function getPrivateKey(){
        return openssl_pkey_get_private($this->privateKey);
    }
    /**
     * 公钥 加密
     * @param string $data
     * @return null|string
     */
    public function publicEncrypt($data){
        if (!is_string($data)) { $data = (String)$data; }
        return openssl_public_encrypt($data,$encrypted,$this->getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 私钥 加密
     * @param string $data
     * @return null|string
     */
    public function privateEncrypt($data=''){
        if (!is_string($data)) { return null; }
        return openssl_private_encrypt($data,$encrypted,$this->getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 加密公钥 解密
     * @param string $encrypted
     * @return null
     */
    public function publicDecrypt($encrypted = ''){
        if (!is_string($encrypted)) { return null; }
        $encrypted = str_replace(' ','+',$encrypted);
        return (openssl_public_decrypt( base64_decode($encrypted), $decrypted, $this->getPublicKey())) ? $decrypted : null;
    }

    /**
     * 加密私钥 解密
     * @param string $encrypted
     * @return null
     */
    public function privateDecrypt($encrypted = ''){
        if (!is_string($encrypted)) { return null; }
        $encrypted = str_replace(' ','+',$encrypted);
        return ( openssl_private_decrypt( base64_decode($encrypted), $decrypted, $this->getPrivateKey() ) ) ? $decrypted : null;
    }

}