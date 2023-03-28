<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 20:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WuYouPay extends ApiPayment
{


    public function randCode($length = 5, $type = 0)
    {
        $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
        if ($type == 0) {
            array_pop($arr);
            $string = implode("", $arr);
        } elseif ($type == "-1") {
            $string = implode("", $arr);
        } else {
            $string = $arr[$type];
        }
        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
    }


    /**
     * 统一下单
     */
    private function pay($order, $type = 'HF_WXH5')
    {
        $apiKey = 'a0313a7fe6a97ee610a855a674a674f6';
        $data = array(
            'mch_id' => '1626772319',// 商户号
            'trade_type' => $type,//支付方式
            'nonce' => $this->randCode(12),// 随机字符串
            'timestamp' => time(),//时间戳
            'subject' => 'goods',
            'detail' => 'goods_detail',
            'out_trade_no' => $order['trade_no'],
            'total_fee' => sprintf("%.2f", $order["amount"]) * 100,
            'spbill_create_ip' => request()->ip(),
            'timeout' => '30',
            'notify_url' => $this->config['notify_url']
        );
        $data['sign'] = $this->getMd5Sign($data, $apiKey);
        $data = json_encode($data);
        $headers = array(
            "Content-Type: application/json",
        );
        $url = "http://sa.rencerdy.com/pay/unifiedorder";
        $response = httpRequest($url, 'post', $data, $headers);
        $result = json_decode($response, true);
        if ($result['result_code'] != 'SUCCESS') {
            Log::error('Create WuYouPay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create WuYouPay API Error:' . $result['message'],
                'errCode' => 200009
            ]);
        }
        return $result['pay_url'];
    }


    /**
     * @param $params
     * 微信
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'HF_WXH5');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, 'HF_WXH5');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * Md5签名
     * @param $params
     * @param $signKey
     * @return string
     */
    public function getMd5Sign($params, $signKey)//签名方式
    {
        ksort($params);
        $data = "";
        foreach ($params as $key => $value) {
            if ($value == '' || $value == null) {
                continue;
            }
            $data .= $key . '=' . $value . '&';
        }
        $sign = md5($data . 'key=' . $signKey);
        return $sign;
    }


    public function notify()
    {
        $notifyData = $_POST;
        Log::notice("WuYouPay notify data" . json_encode($notifyData));
        if ($notifyData['result_code'] == 'SUCCESS') {
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['out_trade_no'];
            return $data;
        }
        echo "error";
        Log::error('WuYouPay API Error:' . json_encode($notifyData));
    }


}

