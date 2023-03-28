<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/3
 * Time: 14:29
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\helper\hash\Md5;
use think\Log;

class WealthPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ali'){

        $data = [
            'appId' =>  '7b07f95397c96b1c5bc652711615e5ff',
            'account' =>  '127.0.0.1',
            'amount' => sprintf("%.2f",$order["amount"]),
            'businessOrderSn' =>  $order['trade_no'],
            'paymentProvider' =>  $type,
            'bussinessMember' =>  '1',
            'symbol' =>  'WBT',
            'returnUrl' =>  $this->config['return_url'],
            'notifyUrl' =>  $this->config['notify_url'],
        ];

        $data['sign'] = $this->getSign($data);
        $url = 'https://api888.15511515.com/business/order/buy';
        return $url.'?'.http_build_query($data);
    }


    private function getSign($data){
        $data['appSecret'] = '9c5f088a6677c40e489a86f50aab63c73d9521a5192b77291ebb8dbb72c1c77a';
        ksort($data);
        $str = strtolower(md5(urldecode(http_build_query($data))));
        return strtoupper(md5($str));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ali');
        return [
            'request_url' => $url,
        ];
    }
	 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ali');
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
        $url = $this->pay($params,'wechat');
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
        Log::notice("WealthPay notify data".json_encode($notifyData));
//        {"appId":"b3482ed66424acdaf71ab919b69d8b0a","businessOrderSn":"115832223497255","notifyTime":"1583223103199","quantity":"800.00","sign":"75CAC0908375435967F5D772DD7CEBD5","tradeNo":"C158322234585351ib99aL","tradeStatus":"finish"}
        if($notifyData['tradeStatus'] == "finish" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['businessOrderSn'];
            return $data;
        }
        echo "error";
        Log::error('WealthPay API Error:'.json_encode($notifyData));
    }
}
