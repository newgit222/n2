<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/17
 * Time: 23:44
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HxPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='icbc'){

        $key = '7a2de48e36a9c4b672262d0f4a31dc03';
        $Aes= new Aes($key);
        $data['order_price']=sprintf("%.2f",$order["amount"]);
        $data['order_id']=$order['trade_no'];
        $data['type']=$type;
        $data['notify_url']=$this->config['notify_url'];
        $data['return_url']=$this->config['return_url'];
        $data['sign']=$this->getpaysign($data,$key);
        $datas['account']=8420;//商户的api_id,在api信息里查看

        $datas['content']=base64_encode($Aes->encrypt(json_encode($data)));
        $d['content'] = base64_encode(json_encode($datas));
        $r = $this->httpPost('http://www.rl233.cn/Api/pay',$d);
        $json = json_decode($r,true);
        if($json['status'] != 'ok' ){
            Log::error('Create HxPay API Error: '.$json['msg']);
            throw new OrderException([
                'msg'   => 'Create HxPay API Error: '.$json['msg'],
                'errCode'   => 200009
            ]);
        }
        //获取到的json数组的data
        $data = $json['data'];
        //解密
        $data = base64_decode($data);
        $data = $Aes->decrypt($data);
        $data = json_decode($data,true);
        $sign = $data['sign'];
        $resign = $this->getpaysign($data,$key);
        if($sign!=$resign){
            Log::error('Create HxPay API Error: 签名错误');
            throw new OrderException([
                'msg'   => 'Create HxPay API Error: 签名错误',
                'errCode'   => 200009
            ]);
        }
        unset($data['sign']);

        return $data['url'];
    }


    /**
     * 用户端生成签名的方法
     * @param array $a
     * @param string $key
     */
    private function getpaysign($a=[],$key=''){
        ksort($a, SORT_STRING);
        $signText='';
        foreach($a as $k => $v){
            if($k == 'sign' || strlen($v) <= 0) continue;
            $signText .= $k.'='.$v.'&';
        }
        $signText=rtrim($signText, "&");
        $signText=strtoupper(md5($signText.$key));
        return $signText;
    }

    function httpPost($url,$param){
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 5);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb123213213qwe($params)
    {
        //获取预下单
        $url = $this->pay($params,'icbc');

        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'1');
        return [
            'request_url' => $url,
        ];
    }



    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }





    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData = $_POST;
        Log::notice("HxPay notify data1".json_encode($notifyData));
        $content = $_POST['content'];
        if(empty($content)){
            Log::error('hgpay API Error: 参数为空');
        }
        //解密
        $apikey = '7a2de48e36a9c4b672262d0f4a31dc03';//TODO 这个是测试apikey,这边需要换成你自己的apikey
        $content = base64_decode($content);
        $aes= new Aes($apikey);
        $r = json_decode($aes->decrypt($content),true);
        $sign = $r['sign'];
        $resign = $this->getpaysign($r,$apikey);
        if($sign==$resign){
            echo 'success';
            $data['out_trade_no'] = $r['order_id'];
            return $data;
        }else{
            Log::error('HxPay API Error: 签名错误');
        }

    }
}


class Aes
{
    /**
     * var string $method 加解密方法，可通过openssl_get_cipher_methods()获得
     */
    protected $method;

    /**
     * var string $secret_key 加解密的密钥
     */
    protected $secret_key;

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv;

    /**
     * var string $options （不知道怎么解释，目前设置为0没什么问题）
     */
    protected $options;

    /**
     * 构造函数
     *
     * @param string $key 密钥
     * @param string $method 加密方式
     * @param string $iv iv向量
     * @param mixed $options 还不是很清楚
     *
     */
    public function __construct($key='key',$method = 'AES-128-CBC', $iv = '5effe26250e19130', $options = 1)
    {
        // key是必须要设置的
        $this->secret_key = isset($key) ? $key : 'morefun';

        $this->method = $method;

        $this->iv = $iv;

        $this->options = $options;
    }

    /**
     * 加密方法，对数据进行加密，返回加密后的数据
     *
     * @param string $data 要加密的数据
     *
     * @return string
     *
     */
    public function encrypt($data)
    {
        return openssl_encrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }
    /**
     * 解密方法，对数据进行解密，返回解密后的数据
     *
     * @param string $data 要解密的数据
     *
     * @return string
     *
     */
    public function decrypt($data)
    {
        return openssl_decrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }

    function hexToStr($hex)//十六进制转字符串
    {
        $string="";
        for($i=0;$i<strlen($hex)-1;$i+=2)
            $string.=chr(hexdec($hex[$i].$hex[$i+1]));
        return  $string;
    }




}