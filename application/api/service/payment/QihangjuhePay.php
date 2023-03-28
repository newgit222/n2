<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/16
 * Time: 13:49
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class QihangjuhePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='zfbsm'){

        $data = [
            'time'  =>  time(),
            'mch_id'  =>  '19988888888',
            'pay_code'  =>  $type,
            'order_sn'  =>  $order['trade_no'],
            'money'  =>  sprintf("%.2f",$order["amount"]),
            'goods_desc'  =>  'goods',
            'return_url'  =>  $this->config['return_url'],
            'notify_url'  =>  $this->config['notify_url'],
        ];
        ksort($data);
        $key='c775a8bfc8debe05deef3409f2eb0ebeb6c2004f';
        $sign=md5(http_build_query($data).'&key='.$key);
        $data['sign']=$sign;
        $url='http://jh.payqihang.com/index.php?c=Pay';
        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbsm');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'wxsm');
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
        Log::notice("DaniuPay notify data1".json_encode($notifyData));
//        {"money":"100.00","order_sn":"XL2020031614080251498","out_order_sn":"115843388843458","status":"success","time":"1584339512","sign":"b4dcc229a7ba563f17b46e52614db505"}
        if($notifyData['status'] == "success" ){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['out_order_sn'];
            return $data;
        }
        echo "ERROR";
        Log::error('DaniuPay API Error:'.json_encode($notifyData));
    }
}