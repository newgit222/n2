<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/9
 * Time: 19:59
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class AathhPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){


        $data = [
            'mch_id'    =>  'caishen',
            'ptype'    => $type,
            'order_sn'    => $order['trade_no'],
            'money'    =>  sprintf("%.2f",$order["amount"]),
            'goods_desc'    =>  'goods',
            'client_ip'    =>  get_userip(),
            'format'    =>  'page',
            'notify_url'    => $this->config['notify_url'],
            'time'    =>  time(),
        ];

        $key = '9f7ccec1bbe3483bd75573abdebb2380285a78ec';

        ksort($data);
        $sign_str='';
        foreach($data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$key}";
        $data['sign'] = md5($sign_str);


        $url = 'http://aathh669.com/?c=Pay';
        return $url.'&'.http_build_query($data);

    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1');
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

        //这里不太确定返回格式 看下日志就行了

        $notifyData = $_POST;
        Log::notice("AathhPay notify data1".json_encode($notifyData));
//        {"money":"100.00","pt_order":"MS2020030920193821572","sh_order":"115837563860883","status":"success","time":"1583756526","sign":"879f0d8d62f0f5894e13fcdadafa7369"}
        if($notifyData['status'] == 'success' ) {
            echo "success";
            $data['out_trade_no'] = $notifyData['sh_order'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }
}