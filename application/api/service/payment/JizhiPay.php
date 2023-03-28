<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/24
 * Time: 20:09
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class JizhiPay extends ApiPayment
{


    private $JZ_RSA_PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDWg5QfS2XHR8e4+/VhwT2o2nAn
zMGaE2m2Wn7yVLl58JeEZA/6YbMJU/YSwKJ30YHopnP1eEzd3UOTvuR7Cg9BB/uc
ipUVc8r7viaTKe8A9ICMuInnTlsjt1cTb0je1Q9jPCRqiOcDUb9o2NTPtydDXxB7
9jyQhvmoP7eTigr61QIDAQAB
-----END PUBLIC KEY-----';

    private $MERCHANT_PRIVATE_KEY = '-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANaDlB9LZcdHx7j7
9WHBPajacCfMwZoTabZafvJUuXnwl4RkD/phswlT9hLAonfRgeimc/V4TN3dQ5O+
5HsKD0EH+5yKlRVzyvu+JpMp7wD0gIy4iedOWyO3VxNvSN7VD2M8JGqI5wNRv2jY
1M+3J0NfEHv2PJCG+ag/t5OKCvrVAgMBAAECgYBvUhUaMGx/ADcbPlXXBwtShxlQ
7idCJ/TiFxBt/Z3LNBnNMIhCLVVV7+ZxUNp/R5AdBdSDfIyXTm31TEkRuOS0rWkH
20LWSPS9ucJE+um5QdpB4axEcjUTXUJv/A4dhy75iAyqbs9wnOr8mJOaB6dGkJNj
6rx3/mHj+AOMV5edwQJBAPLd1ggA2Dz8JAZNNhKnqfvukv+ohYR78zR4RpGZHhvC
ipF8yalEa6KU03nosgNf738muO39zy51LRCgQlUC060CQQDiHT0ugpVvNYYHfKNX
RQUjcuPg+Bkh6d3FDQm6KEvoMNrXgBeJSLjchztdJPjPPjnBo5rKdYCqag7eSGB2
tujJAkEAiyvKAW4Se+f08NN///KqOt78x5oXYNLyjAe8UKTQlxHE/BfpK6E0mMb1
5G8Oy1ZHVLKo2GBQQAwPle5v9G0ZRQJAa8z+10bAkdWVwcoFYdzxFzZ2OFJwQP/r
kD+oDI2bquZn32gdUIFQSStb/QtcaAnFpXEnojClGBoaXpVpEBAiKQJAeXZKHkkR
ddI6Ri+r+kuxPlOiOhM+INWOuSnldXYTy+DTH22IP4ybX6RupuMlTpi70gfrylLO
k+mRDmnUiCikjQ==
-----END PRIVATE KEY-----';

    private $APP_TOKEN = 'dbfc53ba968c440f9eae8a1408a35e2d';


    /**
     * 统一下单
     */
    private function pay($order,$type='AliPayCashier'){


        $data = [
            "merchant_id"   =>  '10802',
            "order_no"   =>  $order['trade_no'],
            "amount"   =>  sprintf("%.2f",$order["amount"]),
            "user_id"   =>  '123456',
            "user_name"   =>  'test',
            "goods_name"   =>  'goods',
//            "sign"   =>  '',
            "pay_type"   =>  $type,
            "notify_url"   => $this->config['notify_url'],
            "user_ip"   =>  get_userip(),
        ];

        $url = 'http://47.90.127.132:8999/api/pay';

        $data['sign'] = $this->sign($this->getPaySignFieldString($data));
        $result =  json_decode($this->doPost($url,json_encode($data)),true);
        if($result['status_code'] != '00000' )
        {
            Log::error('Create Zhuque API Error:'.$result['status_msg']);
            throw new OrderException([
                'msg'   => 'Create Zhuque API Error:'.$result['status_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }


    /*
   签名数据：
   data：utf-8编码的订单原文，
   privatekeyFile：私钥路径
   passphrase：私钥密码
   返回：base64转码的签名数据
   */
    public function sign($data)
    {
        $signature = '';
        $privatekey = openssl_pkey_get_private($this->MERCHANT_PRIVATE_KEY);
        $res=openssl_get_privatekey($privatekey);
        openssl_sign($data, $signature, $res,'SHA256');
        openssl_free_key($res);

        return base64_encode($signature);
    }

    /**
    获取支付接口加签字符串
     */
    public function getPaySignFieldString($data)
    {
        $signFieldArray = array('merchant_id','order_no','amount','user_id','user_name','goods_name');
        return $this->getSignFieldString($signFieldArray,$data);
    }


    /**
    获取签名字符串
     */
    public function getSignFieldString($signFieldArray=array(),$data = array())
    {
        $signFieldString = "";
        foreach($signFieldArray as $signKey)
        {
            if(!array_key_exists($signKey,$data))
            {
                    throw new OrderException([
                        'msg'   => '签名字段:'.$signKey.'不存在',
                        'errCode'   => 200009
                     ]);
            }
            $fieldData = $data[$signKey];
            $signFieldString.=$signKey."=".$fieldData."&";
        }
        return $signFieldString;
    }

    public function doPost($url,$postData,$headers=array())
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$postData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'app-token:'.$this->APP_TOKEN,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData))
        );

        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'AliPayCashier');
        return [
            'request_url' => $url,
        ];
    }

    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WeChatPayCashier');
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
        $url = $this->pay($params,'WeChatPayCashier');
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

        $input = file_get_contents("php://input");
        Log::notice("JizhiPay notify data".$input);
	Log::notice("JizhiPay notify data1".json_encode($_POST));
        $notifyData = json_decode($input,true);
//        if($notifyData['error_code'] == "0" ){
//            echo "success";
            $data['out_trade_no'] = $notifyData['order_no'];
            return $data;
//        }
//        echo "error";
//        Log::error('hgpay API Error:'.$input);
    }

}
