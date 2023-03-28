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

class WzPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = '8007')
    {
        $url = 'http://47.57.186.145:3020/api/pay/create_order';
        $merkey = 'FAYJVOLRZ9RRFK84DEEPUFWBS1Q1BRASMDABIYM6D9JUVRVH5ZRNTSFVD69ZVBUBFIZ3R796YL4VNPTBCSO4C6JHQ3ZF9SLUN2ZT2MNFKKB70BEHYOGWHWIHG98YKIZO';
        $data = [
            'mchId' => '20000007',
            'appId' => '4bf78beaecd447fcbda2eb9aa882890e',
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
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '8007');
        return [
            'request_url' => $url,
        ];
    }


    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '8007');
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
        Log::notice("WzPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == '2' || $notifyData['status'] == '3') {
            echo "success";
            $data['out_trade_no'] = $notifyData['mchOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('WzPay API Error:' . json_encode($notifyData));
    }
}
