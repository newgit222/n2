<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/21
 * Time: 22:40
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class FuchouzhePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'http://fcz.ttzifu.cn/?c=Pay';

        $mch_key = '5effae998b3890b6d1c920fc9ca794034f23d1a6';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'18866666666',
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

        $p_data['request_post_url'] =$url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($p_data);

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


    public function query($notifyData){

        $url = 'http://fcz.ttzifu.cn/?c=Pay&a=query';

        $mch_key = '5effae998b3890b6d1c920fc9ca794034f23d1a6';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'18866666666',
            'out_order_sn'=>$notifyData['sh_order']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);

        $result =  json_decode(self::curlPost($url,$p_data),true);
        Log::notice('query FuchouzhePay  API notice:'.json_encode($result));
        if($result['code'] != '1' ){
            return false;
        }
        if($result['data']['status'] != '9' ){
            return false;
        }
        return true;
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


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'3');
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
        Log::notice("FuchouzhePay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "success" ){
            if($this->query($notifyData)){
                echo "success";
                $data['out_trade_no'] = $notifyData['sh_order'];
                return $data;
            }
        }
        echo "error";
        Log::error('FuchouzhePay API Error:'.json_encode($notifyData));
    }
}

