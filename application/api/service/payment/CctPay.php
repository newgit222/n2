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

class CctPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = '1')
    {
        $url = 'https://www.ccteng8.com/orderpay/payment/insert';
        $merkey = 'E93BW5N75YHB338FHL2Y8D4BS6K95VQZK8SSI5W9B7YKXNHWW2VE1ZJ1YEVRM41Y0863MHE3N2PO4QC5EECIOM35V9JL3FFVXD7SVTGWH4YND4K6YBCR3K02U046JK7J';
        $data = [
            'order_no' => $order['trade_no'],
            'amount' => sprintf("%.2f", $order["amount"]),
            'notify_url' => $this->config['notify_url'],
            'return_url' => $this->config['return_url'],
            'userid' => '10885',
            'channel' => $type,
        ];
        $data['sign'] = $this->getSign($data, $merkey);
        $data['html_type'] = 1;
        $data['param'] = 'goods';
        $result = json_decode(self::curlPost($url, $data), true);
        if ($result['code'] != '0') {
            Log::error('Create CctPay API Error:' . $result['msg']);
            throw new OrderException([
                'msg' => 'Create CctPay API Error:' . $result['msg'],
                'errCode' => 200009
            ]);
        }
        return $result['data']['url'];
    }


    private function getSign($data, $secret)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a, 0, strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a . $secret);
        return $sign;
    }


    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, '1');
        return [
            'request_url' => $url,
        ];
    }

 public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '1');
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
        Log::notice("CctPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == 1) {
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['order_no'];
            return $data;
        }
        echo "error";
        Log::error('CctPay API Error:' . json_encode($notifyData));
    }

}

