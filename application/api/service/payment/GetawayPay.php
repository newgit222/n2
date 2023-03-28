<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/4
 * Time: 21:44
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class GetawayPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='Alipay'){

        $data = [
            'uid'   =>  40,
            'order_sn'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'payment_method'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
        ];
        $key = 'kIYphjkSkcQZmwhacSAe9UVfTjswbR9doLY5FU71p23smWhcxe2VFgSTC8sa';

        $url = 'https://aqf.me/api/order/create';
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $sign = md5(md5($pay_data).$key);
        $data['sign'] = $sign;
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['status'] != '200' )
        {
            Log::error('Create GetawayPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create GetawayPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_url'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'Alipay');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WechatPay');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
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


        $notifyData = $_POST;
        Log::notice("GetawayPay notify data".json_encode($notifyData));
//        {"order_sn":"115833338639846","amount":"50.00","payment_amount":"50.00","payment_at":"1583333999","sign":"996166ac3131c6e9e4ef81a15ef9eeca"}
        echo "success";
        $data['out_trade_no'] = $notifyData['order_sn'];
        return $data;
    }
}