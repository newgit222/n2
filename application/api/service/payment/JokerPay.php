<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\model\OwnpayOrder;
use think\Log;

/*
 * OC支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class JokerPay extends ApiPayment
{

    /*
     *OC支付平台支付签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getSign($data)
    {
        $md5str =$data['amount'].$data['mch_id'].'ES8ruSPyJFdpuFCeavGstSZENq2DJaqS';
        return md5($md5str);
    }

    /*
    *  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order,$payType='8')
    {
        $unified["amount"] = sprintf("%.2f", $order['amount']);
        $unified["mch_id"] = 'chow';
        $unified["ipaddress"] = request()->ip();
        $unified["orderId"] =$order['trade_no'];
        $unified["subject"] = $order['subject'];
        $unified["game_id"] = '';
        $unified["notifyUrl"] = $this->config['notify_url'];
        $unified['sign'] = $this->getSign($unified);

        //发送请求到XX
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Cache-Control: no-cache"
        );

        $response = self::curlPost('http://www.jokerpay.live/template/'.$payType.'/qrcode.html', $unified,[CURLOPT_HTTPHEADER=>$headers]);
        $response = json_decode($response,true);
        if($response['code'] !=10000)
        {
            Log::error('Create Joker API Error:'.($response['msg'] ? $response['msg']:""));
            throw new OrderException([
                'msg'   => 'Create Joker API Error:'.($response['msg'] ? $response['msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }


    /*
     * OC平台支付宝支付
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }

    /*
     * OC平台支付宝支付
    */
    public function guma_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }





    /*
   * OC平台支付宝支付
   */
    public function wap_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }


    /*
   * OC平台支付宝支付
   */
    public function pdd_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }

    /*
     *
     * OC平台支付回调处理
     */
    public function notify()
    {
        Log::error('data from Joker' .json_encode($_POST));
        $notifyData = $_POST;
        echo  'success';
        $data["out_trade_no"] =  $notifyData['out_trade_no'];
        return $data;
    }


    /*
     *
     *同步通知地址处理逻辑
     */
    public function  callback()
    {
        // $plat_order_no= $_POST['out_trade_no'];
        //todo 查询订单信息的同步通知地址

        return [
            'return_url' =>'http://www.baidu.com'
        ];
    }


}
