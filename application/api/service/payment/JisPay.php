<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * 吉盛支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class JisPay extends ApiPayment
{


    /*
    *  JisPay _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 'ALISM')
    {

        $api = 'http://www.jishengpay.com/Pay_Defray.html';
        $appid = '201185136';
        $app_key = '4acl3thsda026x0oytdm9drq6z203ywc';
        $out_trade_no = $order['trade_no'];
        $pay_type = $type;
        $amount = sprintf("%.2f", $order['amount']);
        $notify_url = $this->config['notify_url'];
        $return_url = $this->config['return_url'];
        $data = [
            'trade_mch_no'        => $appid,
            'trade_order_no' => $out_trade_no,
            'trade_time' => date('Y-m-d H:i:s'),
            'trade_type'     => $pay_type,
            'href_notify' => $notify_url,
            'href_callback' => $return_url,
            'total_fee'       => $amount,
        ];
        $sign = $this->getSign($app_key, $data);
        $data['request_post_url'] = $api;
        $data['trade_sign'] = $sign;
        return "http://www.wantongpays.com/pay.php?".http_build_query($data);
    }

    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    function getSign($secret, $data)
    {

        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }

    /*
    * 支付宝支付
    */
    public function wap_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' => $data,
        ];

    }
 public function test($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' => $data,
        ];

    }

    /*
  * vx
  */
    public function h5_vx($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params, "wechat.h5");
        return [
            'request_url' =>  $data['data']['url'],
        ];
    }

    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("JisPay notify data1".json_encode($notifyData));
//        if($notifyData['status'] == "200" ){
if(1){
            if(1){
                echo "OK";
                $data['out_trade_no'] = $notifyData['trade_order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('JisPay API Error:'.json_encode($notifyData));
    }

    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    function verifySign($data, $secret) {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);

        if ($sign != $sign2) {
            return false;
        }
        return true;
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

