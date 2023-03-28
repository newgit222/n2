<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * OC支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class FeihuaPay extends ApiPayment
{
    /*
    *  taobao _pay  统一下单
    *
    */
    private $private_key; #私钥
    private $public_key; #公钥
    private $xw_public_key; #平台公钥

    private $pi_key; #私钥 ID
    private $pu_key; #公钥 ID
    private $xw_key; #平台公钥 ID

    public function __construct()
    {
        #私钥
        $this->private_key = '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kAXRE4tjf8TbcVDCAV7WP/Hsne0hvYdePZiOxx9CiRvyhxDS
atsglhXGoZmtQM4WmDJpRYtf9fWRdT7bd3oIqnDV0+s5qIb4AigQNcbIMcVYr6xj
531k6z7ELBJ4Iw5NaV1xFPIExhTF+vfILfMwqkxFwqlRYI0cR0dwb/YjsQIDAQAB
AoGAAIy+nkOGExocLxPyFhVjxdClv4NIsyHmwI6B63KnA5jmPmCzRXB0fJsWFlWX
dDLy7DZxOS+FI+28k6S+9EFcLKPYllhek0kokuBN+aBcf3+7TPq/JqwAtO5LvS76
weaPe6tQXi/MSHfY62hviOP47MnYYdBIOyMnAUoZzFNutoECQQDcpW7OK93c8Iqo
rCwjjceHHFED/1CvTkn1T5XpIre/wClI3Nob14jVps/42p5ZBs1ujJF6gs7KJZjw
YsboSlrhAkEA2saRnenMd0Le8WhVf1sEmfVyVsNmvxnex+7DA316EqF0cp9nFCls
PvRDA2s2U41AmL5DabIPwBSHGu9BR8Iy0QJAU0vs4c5zqmXwoq6k1yM+EQaimxS2
vAedKgvKd0HRBoWf0E731AzxLl8UIkk+ADPuN+6/OPXK5Ut+SjmwB5+SYQJAW+n9
F8k4XAq+O9JHyV/mCQDz7rvdmQA3duw7BmZbOSSYFegemHvmvHRHC1Kp2mSH9SRK
kkd7EooNUSWf2ZQfYQJBAJ5hUpTXaHTyvbux+Tu8NZAIrpnSj20EO4KznZ2vQuAf
JhktlIt2xBVpkRYlcFz4UWRuDHjHTwoURcNPDSSLG2A=
-----END RSA PRIVATE KEY-----';

        #公钥
        $this->public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kAXRE4tjf8TbcVDCAV7WP/Hs
ne0hvYdePZiOxx9CiRvyhxDSatsglhXGoZmtQM4WmDJpRYtf9fWRdT7bd3oIqnDV
0+s5qIb4AigQNcbIMcVYr6xj531k6z7ELBJ4Iw5NaV1xFPIExhTF+vfILfMwqkxF
wqlRYI0cR0dwb/YjsQIDAQAB
-----END PUBLIC KEY-----';

        #平台公钥
        $this->xw_public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCOZ5Y+ybYfGY2c1J709Pw2A85dVKUucHbuODDvb3p7wY46UyjcakBCXebmihectFrqXj25rjFkiHr6vixxH73Cpy1pY1P4F+OeMgnls1xZu+rzWZDdrzFt3bzBnYc0LjeOSV2rfHqp02Q+qxTZCug2WYuWmcac0kLGD4CKvabVkQIDAQAB
-----END PUBLIC KEY-----';


        //echo $private_key;
        $this->pi_key =  openssl_pkey_get_private($this->private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $this->pu_key = openssl_pkey_get_public($this->public_key);//这个函数可用来判断公钥是否是可用的
        $this->xw_key = openssl_pkey_get_public($this->xw_public_key);//这个函数可用来判断公钥是否是可用的
    }

    /**
     * 参数通过私钥加密
     * @param $data
     * @param $pi_key
     */
    public function encrypred($data){


        openssl_private_encrypt($data,$encrypted,$this->pi_key);//私钥加密
        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }




    /**
     * 参数通过平台公钥加密
     * @param $encrypted
     * @param $pi_key
     */
    public function encrypred_pingtai_pulibc_Key($data){
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->xw_key, OPENSSL_PKCS1_PADDING);
            $crypto .= $encryptData;
        }
        return base64_encode($crypto);
    }

    /**
     * 参数通过公钥解密
     * @param $encrypted
     * @param $pi_key
     */
    public function decrypred($encrypted){
        openssl_public_decrypt(base64_decode($encrypted),$decrypted,$this->pu_key);//私钥加密的内容通过公钥可用解密出来
        return $decrypted;
    }


    /**
     * 参数通过私钥解密
     * @param $encrypted
     * @param $pi_key
     */
    public function decrypred_from_private($encrypted){
        $crypto = '';
        foreach (str_split(base64_decode($encrypted), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $this->pi_key);
            $crypto .= $decryptData;
        }
        return $crypto;
    }



    /**
     * @param $data 待签名字符串
     * @param $privateKey
     * @return string 生成的签名
     */
    public function generateSign($data){
        $signature='';
        openssl_sign($data,$signature,$this->pi_key);
        //openssl_free_key($this->pi_key);
        return bin2hex($signature);
    }

    /**
     * @param $data 待验签数据
     * @param $sign 签名字符串
     * @param $publicKey
     * @return bool
     */
    public function veritySign($data,$sign){
        $result = openssl_verify($data,hex2bin($sign),$this->xw_key);
        return (bool)$result;
    }


    function curlPost2($url = '', $postData = '', $options = array(),$daili_ip="")
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$options);
        }

        if(!empty($daili_ip)){
            $m = explode(":",$daili_ip);
            curl_setopt($ch, CURLOPT_PROXY, $m[0]); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYPORT, $m[1]); //代理服务器端口
        }

        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    private  function getOcPayUnifiedOrder($order, $type = 'Alipay')
    {
        $appid = 'tdmer01';
        $out_trade_no = $order['trade_no'];
        $amount = sprintf("%.2f", $order['amount']);
        $notify_url = "http://www.zhongtongpay.com/api/notify/notify?channel=FeihuaPay";

        $native = [
            'userId'                     =>     111,#
            'terminalType'         =>     '2',
            'merchantCode'        =>    $appid,#"HZ001",
            'channel'                    =>    $type,#'UnionCloudPay',
            'merchantTradeNo'     =>        $out_trade_no,#'b'.uniqid(),
            'amount'                    =>    $amount,#'499.00',
            'extendedAttrData' =>     "",
            'returnUrl'                =>    "http://www.baidu.com",#'http://www.baidu.comPay/Notice/jiabaozhifu',
            'notifyUrl'                    =>   $notify_url,# 'http://www.baidu.compay/success',
        ];

        ksort($native);
        $signData = '';
        foreach ($native as $value) {
            if (!$value) {
                continue;
            }
            $signData .= $value;
        }
        $native["sign"] = $this->generateSign($signData);
        $enc =     $this->encrypred_pingtai_pulibc_Key(json_encode($native));
        $arrData = [
            'merchantCode'  =>  $appid,#
            'content'               => $enc,
        ];
        $option = array();
        $option[] = "Content-Type: application/json;charset=UTF-8";
        $apiurl = "https://api.daniubei.com/pay/center/deposit/apply";
        $response_h = $this->curlPost2($apiurl,json_encode($arrData),$option,"");

//响应结果接收处理
        $responseData = json_decode($response_h, true);
        if(!$responseData){
            $m = explode("Connection: keep-alive",$response_h);
            $response_h = '';
            $response = trim($m[1]);
            $response = str_replace("\r","",$response);
            $response = str_replace("\n","",$response);
            $responseData = json_decode($response, true);
        }




//验证参数
        if (!$responseData || $responseData['status'] != 'SUCCESS') {
            echo "Error responded:".$response_h;
            exit;
        }

        $responseData2 = json_decode($responseData['data'], true);

//1.解密：用商户私钥 得到解密的数组 $decrypted
        $decrypted = $this->decrypred_from_private($responseData2['content']);

//2.回调再次验签：用平台公钥 得到 验签结果
        $arrDecrypt = json_decode($decrypted, true);
        $natiVerify= [
            'merchantCode'        =>    $arrDecrypt['merchantCode'],
            'merchantTradeNo'=>    $arrDecrypt['merchantTradeNo'],
            'tradeNo'                    =>    $arrDecrypt['tradeNo'],
            'payUrl'                    =>    $arrDecrypt['payUrl'],
        ];

        ksort($natiVerify);
        $strVer = '';
        foreach ($natiVerify as $value) {
            if (!$value) {
                continue;
            }
            $strVer .= $value;
        }
        $verify =  $this->veritySign($strVer,$arrDecrypt['sign']);
        $result = ($verify==1?'success':'false');
        if ($result!= 'success') {
            echo "验签失败";
            exit;
        }

//放码

        $ercode_url = $arrDecrypt['payUrl'];
        if(empty($ercode_url)){
            die("获取二维码失败,或订单号重复:".$arrDecrypt['payUrl']);
        }
        return $ercode_url;
    }


    /*
    * chaore平台支付宝支付
    */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $url,
        ];
    }

    /*
  * chaore vx
  */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $url,
        ];
    }

 public function wap_zfb($params)
    {
        //获取预下单
        $url = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $url,
        ];
    }



    /*
     *
     * ++\9+
     */
    public function notify()
    {
        Log::error('Post data from feihua php input:'.file_get_contents('php://input'));
        Log::error('Post data from feihua request:'.json_encode($_REQUEST));
	$_REQUEST = json_decode(file_get_contents('php://input'),true);
        $arrData = [
            'merchantCode' => $_REQUEST['merchantCode'],
            'content' => $_REQUEST['content'],
        ];
        
        $decrypted = $this->decrypred_from_private($arrData['content']);
        Log::error('json:'.$decrypted);
        $arrDecrypt = json_decode($decrypted, true);
        if(1){
            echo "success";
            $data["out_trade_no"] =  $arrDecrypt['merchantTradeNo'];
            return $data;
        }

        throw new OrderException([
            'msg'   => 'noyify hezhong API Error:',
            'errCode'   => 200009
        ]);
    }

}
