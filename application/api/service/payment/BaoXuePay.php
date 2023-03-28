<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
class BaoXuePay extends ApiPayment
{
    public function pay($order,$type){
        $data = [
            'amount' => sprintf("%.2f", $order["amount"]),
            'client_ip' => get_userip(),
            'goods_name' => 'goods',
            'merchant_id' => '100332',
            'notify_url' => $this->config['notify_url'],
            'out_trade_id' => $order['trade_no'],
            'product_id' => $type,
            'return_url' => $order['return_url']
        ];

        $data['sign'] = $this->getSign($data,'01b4439970d75932053c01ff1d827911');

        Log::error('BaoXuePay Api Data :'.json_encode($data,true));

        $result = self::curlPost('http://ttt.lllovolll.com/pay/gateway/create_order',$data);
        Log::error('BaoXuePay Return Data :'.$result);

        $result = json_decode($result,true);

        if ($result['code'] != 1){
            var_dump($result);die;
        }

        return $result['data']['pay_url'];
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
//        $result = strtoupper($sign);

        return $sign;
    }
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '87');
        return [
            'request_url' => $url,
        ];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '87');
        return [
            'request_url' => $url,
        ];
    }


    public function notify(){
        $notifyData = $_POST;
        Log::notice("BaoXuePay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == 2){
            echo "success";
            $data['out_trade_no'] = $notifyData['out_trade_id'];
            return $data;
        }
        echo "fail";
        Log::error('BaoXuePay notify Error:' . json_encode($notifyData));
    }

}
