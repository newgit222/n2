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
class CrPay extends ApiPayment
{


    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 'alipay.wap')
    {
        $api = 'http://www.pddrent.top/api/unified_order';
        $appid = '15673';
        $app_key = 'FKQEgcSfPDmCpIrOsNBRvebywHzakMju';

        $out_trade_no = $order['trade_no'];
        $pay_type = $type;
        $amount = sprintf("%.2f", $order['amount']);
        $notify_url = $this->config['notify_url'];
        $success_url = 'http://www.baidu.com';
        $version = '1.0';

        $data = [
            'version'      => $version,
            'mch_id'        => $appid,
            'out_trade_no' => $out_trade_no,
            'total_fee'       => $amount,
            'pay_type'     => $pay_type,
            'notify_url' => $notify_url,
            'return_url' => $success_url,
            'format'    => 'json',
            'remark' =>'1'
        ];

//拿APPKEY与请求参数进行签名
        $sign = $this->getSign($app_key, $data);
        $data['sign'] = $sign;
        $response = self::curlPost($api, $data);
        $response = json_decode($response,true);

        if($response['code'] != 1)
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
        $string_sign_temp = $string_a . "&" . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtolower($sign);

        return $result;
    }

    /*
    * chaore平台支付宝支付
    */
    public function h5_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $data['data']['url'],
        ];
    }

    /*
  * chaore vx
  */
    public function h5_vx($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params, "wechat.h5");
        return [
            'request_url' =>  $data['data']['url'],
        ];
    }

    /*
     *
     * chaoren平台支付回调处理
     */
    public function notify()
    {

        Log::error('Post data from chaoren' . json_encode($_POST));
        $Md5key = 'FKQEgcSfPDmCpIrOsNBRvebywHzakMju';   //密钥
        $data = array(
            "version"      => $_POST['version'],
            "status"      => $_POST['status'],
            "mch_id"      => $_POST['mch_id'],
            "trade_no"      => $_POST['trade_no'],
            "out_trade_no"      => $_POST['out_trade_no'],
            "total_fee"      => $_POST['total_fee'],
            "pay_money"      => $_POST['pay_money'],
            "pay_type"      => $_POST['pay_type'],
            "remark"      => $_POST['remark'],
        );

        $sign = $this->getSign($Md5key, $data);

        $local_sign          =  $_POST['sign'];
        if($sign == $local_sign){
            echo "success";
            $data["out_trade_no"] =  $_POST['out_trade_no'];
            return $data;
        }

        throw new OrderException([
            'msg'   => 'Create chaoren API Error:',
            'errCode'   => 200009
        ]);
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
