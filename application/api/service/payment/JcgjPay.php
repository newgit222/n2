<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 22:42
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class JcgjPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order, $type = 'alipay')
    {
        $sitename = "test";
        $mch_key  = 'oaJO5K1aSaKNj3oJAsjjT3NaduNAT3b0';
        $mchId    = 1092;
        $p_data   = array(
            "pid"          => $mchId,
            "type"         => $type,
            "notify_url"   => $this->config['notify_url'],
            "return_url"   => $this->config['return_url'],
            "out_trade_no" => $order['trade_no'],
            "name"         => 'test',
            "money"        => sprintf("%.2f", $order["amount"]),
        );
        ksort($p_data);
        reset($p_data);
        $sign_str = '';
        foreach ($p_data as $pk => $pv) {
            $sign_str .= $pk . "=" . $pv . "&";
        }
        //去掉最后一个&字符
        $sign_str = rtrim($sign_str, '&');
        //如果存在转义字符，那么去掉转义
        $sign_str                   = $sign_str . $mch_key;
        $p_data['sign']             = md5($sign_str);
        $p_data['sign_type']        = 'MD5';
        $url                        = 'http://jinchen.tcfaka.com/submit.php';
        $p_data['request_post_url'] = $url;
        return "http://www.ci6pmd.cn//pay.php?" . http_build_query($p_data);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

   public function test($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        \think\Log::notice("JcgjPay notify data" . json_encode($_GET));
        if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            echo "success";
            $data['out_trade_no'] = $_GET['out_trade_no'];
            return $data;
        }
        echo "error";
        Log::error('JcgjPay API Error:');
    }



}
