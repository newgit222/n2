<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *YftPay 支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class YishouPay extends ApiPayment
{

    /*
    * Yft 统一下单
    *
    */
    private function getPayUnifiedOrder($order, $type = 'alipay')
    {

$params =  [
    'app_id' => '44',
    'sign_type' => 'md5',
    'type' => $type,
    'amount' => sprintf("%.2f", $order['amount']),
    'out_trade_no' => $order['trade_no'],
    'debug' => 0,
    'timestamp' => time(),
    'return_url' => 'http://www.bbb.com',
];
$sign_type = $params['sign_type'];
unset($params['sign'], $params['sign_type']);
ksort($params);
$params['sign'] = strtoupper(md5(urldecode(http_build_query($params))));
$params['sign_type'] = $sign_type;
$data = urldecode(http_build_query($params));
$url = 'https://yizhuanfen.com/api/gateway';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$output = curl_exec($ch);
$res =json_decode($output,true);
if($res['code'] == 10000)
{
 return $res['data']['pay_url'];

}
//       Log::error('Create OC API Error:'.($res'message'] ? $response['Msg']:""));
            throw new OrderException([
                'msg'   => 'Create yishou API Error:'.($res['message'] ? $res['message']:""),
                'errCode'   => 200009
            ]);

    }


    /*
     *微信h5产品
     * @param $params
     * @return array
     * @throws OrderException
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $response = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $response,
        ];
    }



 public function test($params)
    {
        //获取预下单
        $response = self::getPayUnifiedOrder($params);
        return [
            'request_url' =>  $response,
        ];
    }



    /*
     * Luckpay平台支付回调处理
     */
    public function notify()
    {
            $notifyData = $_POST;
            Log::notice("yishou notify data".json_encode($_POST));
                echo 'success';
                $data["out_trade_no"] = $notifyData['out_trade_no'];
                return $data;
    }

}
