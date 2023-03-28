<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/18
 * Time: 14:53
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class WangjiaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'18888888888',
            'ptype'=>$type,
            'order_sn'=>$order['trade_no'],
            'money'=>sprintf("%.2f",$order["amount"]),//增加一定随机数金额
            'goods_desc'=>'goods',
            'client_ip'=>get_userip(),
            'format'=>'page',
            'notify_url'=>$this->config['notify_url']
        );
        $mch_key = '43f6c417c94b6a1523236c36b0772a98edd7d2c4';
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);
        $url='https://f.wzfpay.vip/?c=Pay&';
        $url.=http_build_query($p_data);
        return $url;
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

        $notifyData = $_POST;
        Log::notice("WangjiaoPay notify data1".json_encode($notifyData));
//        {"money":"300.00","pt_order":"MS2020031815084937623","sh_order":"115845153293733","status":"success","time":"1584515565","sign":"e538eeacd617be5f4b4044ec20cff5ca"}
        if($notifyData['status'] == "success" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['sh_order'];
            return $data;
        }
        echo "error";
        Log::error('WangjiaoPay API Error:'.json_encode($notifyData));
    }

}