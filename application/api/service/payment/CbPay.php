<?php

namespace app\api\service\payment;

use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * 超宝pay支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */

class CbPay extends ApiPayment
{


    /*
    *  CbPay _pay  统一下单
    *
    */
    private function getOcPayUnifiedOrder($order, $type = '1005')
    {
        $api                      = 'http://pub.chaobaopay.com/pay/Scanpay.html';
        $appid                    = '30011550';
        $app_key                  = 'r6w95wluhm0d7qukzyp8x22ikmw184sq';
        $out_trade_no             = $order['trade_no'];
        $amount                   = sprintf("%.2f", $order['amount']);
        $notify_url               = $this->config['notify_url'];
        $return_url               = $this->config['return_url'];
        $data                     = [
            'Wg_shh' => $appid,
            'Wg_ddh' => $out_trade_no,
            'Wg_tjsj' => date('Y-m-d H:i:s'),
            'Wg_yhbm' => $type,
            'Wg_fwdtz' => $notify_url,
            'Wg_ymtz' => $return_url,
            'Wg_ddje' => $amount,
        ];
        $sign                     = $this->getSign($app_key, $data);
        $data['request_post_url'] = $api;
        $data['Wg_md5qm']         = $sign;
        return "http://www.wantongpays.com/pay.php?" . http_build_query($data);

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
    public function small_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params,1005);
        return [
            'request_url' => $data,
        ];


    }

  public function small_vx($params)
    {
        //▒~N▒▒~O~V▒~D▒~K▒~M~U
        $data = self::getOcPayUnifiedOrder($params,1005);
        return [
            'request_url' => $data,
        ];


    }



 public function test($params)
    {
        //▒~N▒▒~O~V▒~D▒~K▒~M~U
        $data = self::getOcPayUnifiedOrder($params,1004);
        return [
            'request_url' => $data,
        ];


    }

    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData = $_POST;
        Log::notice("CbPay notify data1" . json_encode($notifyData));
        if ($notifyData['Wg_jyzt'] == "00") {
            if (1) {
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['Wg_ddh'];
                return $data;
            }
        }
        echo "error";
        Log::error('CbPay API Error:' . json_encode($notifyData));
    }


    /*
     *
     *同步通知地址处理逻辑
     */
    public function callback()
    {
        // $plat_order_no= $_POST['out_trade_no'];
        //todo 查询订单信息的同步通知地址

        return [
            'return_url' => 'http://www.baidu.com'
        ];
    }

}

