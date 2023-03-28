<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/4
 * Time: 22:14
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class PkPay extends ApiPayment
{

    private $host ='https://tecud.cn/';

    private $private_key = 'MIIEpAIBAAKCAQEA7biS+hYizmb2g+74BQbaSKrNY4gI1cAvgKxkeHNpdcz1EmPY LqUydjZ4Zyxg4W9WGBUJP4pyE3L7SrlhBGNV5Fvht0ncNceJuB7W2NhFKovO1jRy /TH+ZWZ6W+Kef4JxP9J0e7hbsEH1ADl1omGoj6/sRqmTA6/5c6em9JC7U/C8GvVl gUx4xFEo2NsuIG3w49e+wli5FAmj33nmKbYHKMqV1IyroRmJiAThrM87G8Jup1tR 371gTfOFEOuhUsYzPyWX11xoXr6hH6EvGYlsVQZXDeA0C6r2cwQEw83KqZgC5t33 c3QLXMxpVj7MqzgvahHP8eGO5ER2C63mPEIaHwIDAQABAoIBAADTt48vLIfTS+BB oJND5taB4Z0LCBzWQlQMRX01yZQjVmbt+f6PjJFktx6ExQGDxaTnzdRuwt7Wc5Fa +79sKQxWVX6xvxZgz1XYpZ6zgeoX+mw8WnoPBneL17kiHLMM3R//t3AGwCroz9Vk Xchm1pNJZWUJif1lS1TfrHEFX+F23zkbYKdZwKUJlGal5f8WnBpeyTUeYArDk7gb foIhjadGhgkTD3c1ZavKNhDkWzha1wEVwaU2Hui3rts+WAUfwE8Yb5H6orPjziZo IeuXFVxeQQt8vYqf/6QevG8VA2oo0GaikIpn0R/yHuEL3UOfyTqmFH4SVdgxCXQL i/CqLgECgYEA/NmAMyk5dojuJ2zSYTb/0z8olgKyti1jTwIrTknOVg43zk68OQu2 XhWt6goYKNY+EqnHFWSu8Q+UuYC2ZdovD5wRt5C5eiTQ8zgbqB6o4wAaX5h9pbFo 0mOBL3Xg3GB7SwJbyWDLxMnasU6A4kMGdUuMvYlqKZ8UHH0K6uiHOl8CgYEA8K7R iRr8jtGXSQalRRErp8Veyoo46ypyI9C3ch62h8CM0PTdUJjo6zyTwr9vFfbaaYxx u2Tc5VHqgGruMxYKTVphuaJ5vnp7G7OOJHv+6ENjpKlGdB4kpixH00NRbPgDYy2H mMXnriOCwfKyVsVUsuTo19oQMQeRT1wcQvMIuEECgYBTYLmYpVBwmQjHcWgSbJuB ooMUrIEX9/GWfSWgpE/pKrkWS/pfaVBSyB1YXsnGtZc0ek3O7SuPDE9DuIDtJzx7 jkB9Yrj24SsR+P+NucaBTGhl+5f7SnxBXFSePOXQZfRiML3pswSeuN1nuHZhpi58 AVTAr46VeQKdvlIaUmE9SwKBgQDLw3VqGgrP7umhzZqGCq9m7lX+K19c2Sag48cr Oo1WCNIu5d+oqIwo+7QtIF0vvznSOjDfcdkB3IQoDF91qKc4LdimjMN9W7x2C2G/ yapZgRQPB7C6BfsJJFv6o/d/xck4Ygd2c7ZdW3sMMCiEBbRbcCkg9WDX4YxIH7dd htxPAQKBgQDqyFhBjLTbCDMGsngmz5R3KeMGEke24OdGCD1HzRwqlPV3Q6aPNFGD NmukYIyzp5lG13mFvbcH2cro27L6mIqx5PmrIjMifIAARMUSfS6ctmPQ3yuNDy95 my0KAK1FwHZeveCG7hYmIj7GAFQ2tT85jq0GGQ7rrXCmNyCRGTfgtg==';



    /**
     * 微信支付
     */
    public function guma_vx($param){
        $result = $this->pay($param,'wechat');
        return [
            'request_url' => $result['payment_url'],
        ];
    }

 public function guma_zfb($param){
        $result = $this->pay($param,'alipay');
        return [
            'request_url' => $result['payment_url'],
        ];
    }
 public function h5_zfb($param){
        $result = $this->pay($param,'alipay');
        return [
            'request_url' => $result['payment_url'],
        ];
    }
 public function test($param){
        $result = $this->pay($param,'alipay');
        return [
            'request_url' => $result['payment_url'],
        ];
    }

    /**
     * 统一下单
     */
    public function pay($order,$type){
        $url = $this->host.'/merchant_api/v1/orders/payment';
        $param = [
            'account_name'  =>  'jack',
            'merchant_order_id'  =>  $order['trade_no'],
            'total_amount'  =>  $order["amount"],
            'timestamp'  =>   date(DATE_ISO8601),
            'notify_url'  =>  $this->config['notify_url'],
            'subject'  =>  '支付',
//            'return_url'  =>  '',
            'payment_method'  =>  $type,
        ];
        $param_json = json_encode($param);
        $sign = $this->getSign($param_json,$this->private_key);
        $params = [
            "data"=> $param_json,
            "signature"=> $sign
        ];
//        var_dump($params);
        $result = $this->curl_post($url,$params);
        $result = json_decode($result,true);
        if(!isset($result['status'])){
            Log::error('Create YftPay API Error:' . ($result['message'] ? $result['message'] : ""));
            throw new OrderException([
                'msg' => 'Create YftPay API Error:' . ($result['message'] ? $result['message'] : ""),
                'errCode' => 200009
            ]);
        }
        return $result;
    }

    /**
     * @param $content
     * @param $privateKey
     * @return string
     *  生成sign
     */
    public function getSign($content, $privateKey){
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $key = openssl_get_privatekey($privateKey);
        openssl_sign($content, $signature, $key, "SHA256");
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

     /**
     * curl
     * @param  string $url [description]
     * @return [type]      [description]
     */

    private function curl_post($url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
//    var_dump($headers);
        curl_close($ch);
        return $data;
    }
	
    /*
     * Luckpay平台支付回调处理
     */
    public function notify()
    {
	Log::error('Post data from Feihua post' . json_encode($_POST));
            Log::error('Post data from Feihua:'.file_get_contents('php://input'));
            $notifyData = json_decode($_POST['data'],true);

            if($notifyData['notify_type'] == 'trade_completed'){
                //处理业务逻辑
                echo 'SUCCESS';
                $data["out_trade_no"] = $notifyData['order']['merchant_order_id'];
                return $data;
            }
            throw new OrderException([
                'msg' => 'Create QH API Error:',
                'errCode' => 200009
            ]);
    }
}
