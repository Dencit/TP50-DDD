<?php
namespace extend\WxPayApi\source;
/**
* 	配置账号信息
*/

class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 * 
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 * 
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 * 
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 * 
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	public $APPID = '[APP_ID]';
	public $APPSECRET = '[APP_SECRET]';

	public $SMALL_APPID='[SMALL_APP_ID]';
	public $SMALL_APPSECRET='[SMALL_APP_SECRET]';

	public $MCHID = '[MCH_ID]';
	public $KEY = '[KEY]';
	
	//=======【证书路径设置】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * @var path
	 */
	public $SSL_CERT_PATH = '../cert/public/apiclient_cert.pem';
	public $SSL_KEY_PATH = '../cert/public/apiclient_key.pem';
	
	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	public $CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	public $CURL_PROXY_PORT = 0;//8080;
	
	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	public $REPORT_LEVENL = 1;
	public $NOTIFY_URL = "";
	public $REFUND_NOTIFY_URL = "";

	public function GetMerchantId(){
		return $this->MCHID;
	}

	protected function getPemString($path){
		$pemFile = file_get_contents($path);
		$pemFile = preg_replace('/\\n/','',$pemFile);
		preg_match("/CERTIFICATE-----(.*)-----END/",$pemFile,$m);
		if ( isset($m[1]) ){ return $m[1]; }
		return null;
	}

	public function __construct()
	{
		$this->APPID = config("api.wx.h5.app_id");
		$this->APPSECRET = config("api.wx.h5.secret");

		$this->SMALL_APPID = config("api.wx.small.app_id");
		$this->SMALL_APPSECRET = config("api.wx.small.secret");

		$this->MCHID = config("api.wx.pay.mch_id");
		$this->KEY = config("api.wx.pay.key");

		$this->SSL_CERT_PATH = config("api.wx.pay.ssl_cert_path");
		$this->SSL_KEY_PATH = config("api.wx.pay.ssl_key_path");
		$this->CURL_PROXY_HOST = config("api.wx.pay.curl_proxy_host");
		$this->CURL_PROXY_PORT = config("api.wx.pay.curl_proxy_port");
		$this->REPORT_LEVENL = config("api.wx.pay.report_levenl");
		$this->NOTIFY_URL = config("api.wx.pay.notify_url");
		$this->REFUND_NOTIFY_URL = config("api.wx.pay.refund_notify_url");
	}
}
