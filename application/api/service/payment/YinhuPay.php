<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/5
 * Time: 22:05
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class YinhuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='13'){
        $url = 'http://yh.benwo.hk/?c=Pay';

        $mch_key = '0ae87321fff223f318e472ba6c7636e2261a5cdf';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'13500001111',
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

        $url = 'http://yh.benwo.hk/?c=Pay&a=query';

        $mch_key = '0ae87321fff223f318e472ba6c7636e2261a5cdf';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'13500001111',
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
        Log::notice('query YinhuPay  API notice:'.json_encode($result));
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
        $url = $this->pay($params,'13');
        return [
            'request_url' => $url,
        ];
    }


    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'13');
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
        Log::notice("YinhuPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "success" ){
            if($this->query($notifyData)){
                echo "success";
                $data['out_trade_no'] = $notifyData['sh_order'];
                return $data;
            }
        }
        echo "error";
        Log::error('YinhuPay API Error:'.json_encode($notifyData));
    }
}