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
class MadingPay extends ApiPayment
{
    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 'alipay')
    {
        $appid = '1085';
        $app_key = 'e349cac888faf7e226cb79466471bc0f84af8972';
        $out_trade_no = $order['trade_no'];
        $amount = sprintf("%.2f", $order['amount']);
        $notify_url = $this->config['notify_url'];

        $version='1.0';
        $customerid=$appid;
        $sdorderno=$out_trade_no;
        $total_fee=$amount;
        $paytype=$type;
        $notifyurl=$notify_url;
        $returnurl='www.baidu.com';
        $remark='';
        $get_code=0;

        $sign=md5('version='.$version.'&customerid='.$customerid.'&total_fee='.$total_fee.'&sdorderno='.$sdorderno.'&notifyurl='.$notifyurl.'&returnurl='.$returnurl.'&'.$app_key);


        $url = "https://www.todozeg.com/apisubmit?version=1.0&customerid={$customerid}&sdorderno={$sdorderno}&total_fee={$total_fee}&paytype={$paytype}&notifyurl={$notifyurl}&returnurl={$returnurl}&remark={$remark}&get_code={$get_code}&sign={$sign}";

        return $url;
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
    public function guma_zfb($params)
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

        Log::error('Post data from mading post' . json_encode($_POST));
        Log::error('Post data from mading php input:'.file_get_contents('php://input'));

        if(1){
            echo "success";
            $data["out_trade_no"] =  $_POST['sdorderno'];
            return $data;
        }

        throw new OrderException([
            'msg'   => 'noyify hezhong API Error:',
            'errCode'   => 200009
        ]);
    }

}
