<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/2
 * Time: 20:11
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YbPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = '8006')
    {
        $url = 'http://pay.hxx.nvtu.ren/api/pay/create_order';
        $merkey = 'VZ9VVT9TY2GZRGXJ15H5SL4RGI3BMUZDDU8C7ASCUHKDO7QONFPBJZNRB1UMP0E3HUVBVJPPDLWMUVP6LZT8VWZR6HPZ0LVS8SBQECADCBLKBQSVXUL8QQEPZMNHMF7G';
        $data = [
            'mchId' => '20000039',
            'appId' => 'cd4a1f118b624042a044fffd59fa6a14',
            'productId' => $type,
            'mchOrderNo' => $order['trade_no'],
            'currency' => 'cny',
            'amount' => sprintf("%.2f", $order["amount"]) * 100,
            'notifyUrl' => $this->config['notify_url'],
            'subject' => 'goods',
            'body' => 'goods',
            'extra' => '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data, $merkey);
        $result = json_decode(self::curlPost($url, $data), true);
        if ($result['retCode'] != 'SUCCESS') {
            Log::error('Create YbPay API Error:' . $result['retMsg']);
            throw new OrderException([
                'msg' => 'Create YbPay API Error:' . $result['retMsg'],
                'errCode' => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function getSign($data, $secret)
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a . 'key=' . $secret);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 微信H5
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, '8003');
        return [
            'request_url' => $url,
        ];
    }

 public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '8003');
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
        $notifyData = $_POST;
        Log::notice("YbPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == '2' || $notifyData['status'] == '3') {
            echo "success";
            $data['out_trade_no'] = $notifyData['mchOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('YbPay API Error:' . json_encode($notifyData));
    }
}
