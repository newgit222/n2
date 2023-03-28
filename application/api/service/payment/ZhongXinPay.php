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

class ZhongXinPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = '8097')
    {
         $url = 'http://pay.kioopuy.vip/api/pay/create_order';
        $merkey = 'C5JF340GICH1CI8WCWIGDF7ZSJEJJKOXSKPS1IO1LIVR47TRYZAHCJNHBWM9SMAE4V16GVA3RYWA8COYWUAHAVDEVIRHWN3C0BFLOTPK7AMYRSGDYYSFRFO1PIBBQT9C';
        $data = [
            'mchId' => '20000052',
            'appId' => '91cddb9b08234dbc95c8b118b846a7ce',
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
            Log::error('Create ZhongXinPay API Error:' . $result['retMsg']);
            throw new OrderException([
                'msg' => 'Create ZhongXinPay API Error:' . $result['retMsg'],
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
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, '8097');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 微信H5
     */
    public function dxmc($params)
    {
        //获取预下单
        $url = $this->pay($params, '8099');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 微信H5
     */
    public function wxh5($params)
    {
        //获取预下单
        $url = $this->pay($params, '8097');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 微信H5
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '8097');
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
        Log::notice("ZhongXinPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == '2' || $notifyData['status'] == '3') {
            echo "success";
            $data['out_trade_no'] = $notifyData['mchOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('ZhongXinPay API Error:' . json_encode($notifyData));
    }
}
