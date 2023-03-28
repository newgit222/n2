<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/29
 * Time: 23:04
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class TangrenPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'https://www.trenpay.top/?c=Pay&';

        $mch_key = '859abe22e699279a209225cbde1b33e0b1e5a722';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'18788884567',
            'ptype'=>$type,
            'order_sn'=>$order['trade_no'],
            'money'=> sprintf("%.2f",$order["amount"]),//增加一定随机数金额
            'goods_desc'=>'goods',
            'client_ip'=>get_userip(),
            'format'=>'page',
            'notify_url'=>$this->config['notify_url']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);

        return $url.http_build_query($p_data);
    }


    public function getSign($parameters,$key){
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = md5($signPars);
        return $sign;
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
        $notifyData =$_POST;
        Log::notice("TangrenPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "success" ){
                echo "success";
                $data['out_trade_no'] = $notifyData['sh_order'];
                return $data;
        }
        echo "error";
        Log::error('TangrenPay API Error:'.json_encode($notifyData));
    }

}