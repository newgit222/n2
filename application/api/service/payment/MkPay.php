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
class MkPay extends ApiPayment
{
    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 8007)
    {
        $appid = '20000060';
        $out_trade_no = $order['trade_no'];
        $amount = intval($order['amount']);
        $notify_url = $this->config['notify_url'];
        $data = [
            'mchId'        => $appid,
            'appId'        => '615f12248cf447b7aa03ebac185ea5ab',
            'mchOrderNo' => $out_trade_no,
            'currency'   => 'cny',
            'amount'       => $amount,
            'notifyUrl' => $notify_url,
            'subject'   => 'a',
            'body'      =>'body',
            'extra'     => 'a',
            'productId' => $type
        ];

//拿APPKEY与请求参数进行签名
        $sign = $this->getSign($data);
        $data['sign'] = $sign;
        $response = self::curlPost("https://pay.mkpay.net/api/pay/create_order", $data);
        $response = json_decode($response,true);
        if($response['retCode'] != "SUCCESS")
          {
              Log::error('Create hezhong API Error:'.($response['errDes'] ? $response['errDes']:""));
              throw new OrderException([
                  'msg'   => 'Create hezhong API Error:'.($response['errDes'] ? $response['errDes']:""),
                  'errCode'   => 200009
              ]);
        }
        return $response['payParams']['payUrl'];
    }

    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    function getSign($data)
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        $string_a = $string_a."&key=SXDAE59NMQV9GZBTBWQOARRMRFHVMI0XRZUKNROP6WSUS8K5V4CQDBFDKJTKQBVRP4DHF7P9WA7OBYZUT1DRACDRQX4CJDMFEQFPRZ1DE9N0HC8VMCALQ6ZLEY8WDY18";
        //签名步骤三：MD5加密
        $sign = md5($string_a);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }

    /*
    * chaore平台支付宝支付
    */
    public function h5_zfb($params)
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
     * chaoren平台支付回调处理
     */
    public function notify()
    {

        Log::error('Post data from hezhong' . json_encode($_POST));

        $data = $_POST;
        unset($data['sign']);
        $sign = $this->getSign("CMXzWds8cZHXOPAGLLtVqsExpzkhWAGE", $data);

        $local_sign          =  $_POST['sign'];
        if($sign == $local_sign){
            echo "success";
            $data["out_trade_no"] =  $_POST['out_trade_no'];
            return $data;
        }

        throw new OrderException([
            'msg'   => 'noyify hezhong API Error:',
            'errCode'   => 200009
        ]);
    }

}
