<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/22
 * Time: 23:16
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class AryPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'alipay_f2f')
    {
        $url = 'http://gh.haizei.me/api/v1/charges';
        $merkey = 'd6a0e559b46c4b65b14f5bbd2b3c17f5';
        $data = [
            'uid' => '602209807830614016',
            'money' => sprintf("%.2f", $order["amount"]),
            'channel' => $type,
            'outTradeNo' => $order['trade_no'],
            'notifyUrl' => $this->config['notify_url'],
            'returnUrl' => $this->config['return_url'],
            'timestamp' => time() * 1000,
        ];
        $data['sign'] = $this->getSign($data, $merkey);
        $result = json_decode(self::curlPost($url, $data), true);
        if ($result['code'] != '0') {
            Log::error('Create AryPay API Error:' . $result['msg']);
            throw new OrderException([
                'msg' => 'Create AryPay API Error:' . $result['msg'],
                'errCode' => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }


    private function getSign($data, $secret)
    {
        $data['token'] = $secret;

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a, 0, strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, 'alipay_f2f');
        return [
            'request_url' => $url,
        ];
    }

public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'alipay_f2f');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @return mixed
     * 回调
     */


/**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData = $_POST;
        Log::notice("AryPay notify data1" . json_encode($notifyData));
        if (isset($notifyData['outTradeNo'])) {
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['outTradeNo'];
            return $data;
        }
        echo "error";
        Log::error('AryPay API Error:' . json_encode($notifyData));
    }




}

