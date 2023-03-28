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
class JsPay extends ApiPayment
{


    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 'alipay')
    {
        error_reporting(0);
        $api = 'http://api.jshipay.com/index/unifiedorder?format=json';
        $appid = '1051532';
        $app_key = '4X0PdwHxdxtSYRVG8llStSQjJpbuab9P';

        $out_trade_no = $order['trade_no'];
        $pay_type = $type;
        $amount = sprintf("%.2f", $order['amount']);
        $callback_url = $this->config['notify_url'];
        $success_url = 'http://www.baidu.com';
        $error_url = 'http://www.baidu.com';
        $version = 'v1.1';
        $out_uid = '';

        $data = [
            'appid'        => $appid,
            'pay_type'     => $pay_type,
            'out_trade_no' => $out_trade_no,
            'amount'       => $amount,
            'callback_url' => $callback_url,
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'version'      => $version,
            'out_uid'      => $out_uid,
        ];

//拿APPKEY与请求参数进行签名
        $sign = $this->getSign($app_key, $data);
        $data['sign'] = $sign;
        $response = self::curlPost($api, $data);
        $response = json_decode($response,true);
        if($response['code'] !=200)
          {
              Log::error('Create JSPAY API Error:'.($response['msg'] ? $response['msg']:""));
              throw new OrderException([
                  'msg'   => 'Create JSPAY API Error:'.($response['msg'] ? $response['msg']:""),
                  'errCode'   => 200009
              ]);
        }
        return $response;
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
     * OC平台支付宝支付
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $data['url'],
        ];
    }


    /*
    * OC平台支付宝支付
    */
    public function guma_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $data['url'],
        ];
    }



    /*
     *
     * OC平台支付回调处理
     */
    public function notify()
    {
        $app_key = '4X0PdwHxdxtSYRVG8llStSQjJpbuab9P';
//商户名称
        $appid  = $_POST['appid'];
//支付时间戳
        $callbacks  = $_POST['callbacks'];
//支付状态
        $pay_type  = $_POST['pay_type'];
//支付金额
        $amount  = $_POST['amount'];
//支付时提交的订单信息
        $success_url  = $_POST['success_url'];
//平台订单交易流水号
        $error_url  = $_POST['error_url'];
//该笔交易手续费用
        $out_trade_no  = $_POST['out_trade_no'];
//实付金额
        $amount_true  = $_POST['amount_true'];
//用户请求uid
        $out_uid  = $_POST['out_uid'];
//回调时间戳
        $sign  = $_POST['sign'];

        $data = [
            'appid'        => $appid,
            'callbacks'     => $callbacks,
            'pay_type' => $pay_type,
            'amount'       => $amount,
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'out_trade_no'      => $out_trade_no,
            'amount_true'      => $amount_true,
            'out_uid'      => $out_uid,
            'sign'      => $sign,
        ];
        Log::error('Post data' . json_encode($_POST));
        if($this->verifySign($data,$app_key) != $sign)
        {
            throw new OrderException([
               'msg'   => 'Create Hnpay API Error:',
               'errCode'   => 200009
            ]);
        }
        echo "success";
        $data["out_trade_no"] =  $_POST['out_trade_no'];
        return $data;
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
