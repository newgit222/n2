<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/17
 * Time: 15:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LingdansaomaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_scan'){

        $url = 'https://pay.falabo.cn/index.php/Qypay/order';

        $merkey = 'a77f041a5ab18351bee34cd46cd1f04f';

        $data = [
            'merchant_id'   =>  '20200420141729768',
            'orderid'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notify_url'   =>  $this->config['notify_url'],
//            'attach'   =>  '',
            'pay_type'   =>  $type,
        ];
        $sign = 'merchant_id='.$data['merchant_id'].'&orderid='.$data['orderid'].'&amount='.$data['amount'].'&notify_url='.$data['notify_url'].'&key='.$merkey;
        $data['sign'] = md5($sign);
        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }





    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_xianyu');
        return [
            'request_url' => $url,
        ];
    }

public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_scan');
        return [
            'request_url' => $url,
        ];
    }

public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_scan');
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


        $notifyData =$_POST;
        Log::notice("LingdanPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "1" ){
            echo "ok";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "error";
        Log::error('LingdanPay API Error:'.json_encode($notifyData));
    }

}
