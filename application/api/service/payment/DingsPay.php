<?php

namespace app\api\service\payment;

use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * 鼎盛支付支付平台第三方出码
 * Class 鼎盛支付
 * @package app\api\service\payment
 */

class DingsPay extends ApiPayment
{


    //签名
    public function getSign($args)
    {
        ksort($args);
        $mab = '';
        foreach ($args as $k => $v) {
            if ($k == 'sign' || $k == 'key' || $v == '') {
                continue;
            }
            $mab .= $k . '=' . $v . '&';
        }
        $mab .= 'key=' . $args['key'];
        return md5($mab);
    }


    /*
    *  统一下单
    *
    */
    private function getPayUnifiedOrder($order, $orderchannel = 4)
    {
        $payurl                    = 'http://api.s80038.com/api/createOrder';
        $mch_id                    = '80000551';              // 商户编号
        $mch_key                   = 'af730a7d85937437';            // 商户密钥
        $params['merchantid']      = $mch_id;
        $params['key']             = $mch_key;
        $params['orderchannel']    = $orderchannel;//支付类型
        $params['merchantorderid'] = $order['trade_no'];//订单号
        $params['applyamount']     = sprintf("%.2f", $order['amount']);//金额
        $params['callbackurl']     = $this->config['notify_url'];//回调地址
        $params['sign']            = $this->getSign($params);//签名
        unset($params['key']);
        $response = self::curlPost($payurl, $params);
        $result   = json_decode($response, true);
        if ($result['code'] == '0') {
            return $result['data']['payurl'];
        }
        throw new OrderException([
            'msg'     => 'Create DsPay API Error:' . $result['errMsg'],
            'errCode' => 200009
        ]);
    }


    /**
     * 微信扫码支付
     * @param $params
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->getPayUnifiedOrder($params, 4);
        return [
            'request_url' => $url,
        ];
    }


    /**"
     * 支付宝H5
     * @param $params
     * @return string[]
     */
    public function test($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params, 4);
        return [
            'request_url' => $url,
        ];
    }
 public function guma_yhk($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params, 4);
        return [
            'request_url' => $url,
        ];
    }


    /*
     * DsPay平台支付回调处理
     */
    public function notify()
    {
        Log::error('Post data from DsPay' . json_encode($_REQUEST));
        if (1) {
            echo "success";
            $data["out_trade_no"] = $_REQUEST['merchantorderid'];
            return $data;
        }
    }
}

