<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/4
 * Time: 16:25
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class F1Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='3'){
        $url = 'http://www.f1ae86.com/?c=Pay';

        $mch_key = '5a4483eaadcacf162caf1957edd18c5a1c71c8f2';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'15866666688',
            'ptype'=>$type,
            'order_sn'=>$order['trade_no'],
            'money'=> sprintf("%.2f",$order["amount"]),//增加一定随机数金额
            'goods_desc'=>'goods',
            'client_ip'=>get_userip(),
            'format'=>'page',
            'notify_url'=>$this->config['notify_url']
        );
//var_dump($p_data);die();
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

        $url = 'http://www.f1ae86.com/?c=Pay&a=query';

        $mch_key = '5a4483eaadcacf162caf1957edd18c5a1c71c8f2';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'15866666688',
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
        Log::notice('query F1Pay  API notice:'.json_encode($result));
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
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params,'3');
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
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
        Log::notice("ChuangshijiPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "success" ){
            if($this->query($notifyData)){
                echo "success";
                $data['out_trade_no'] = $notifyData['sh_order'];
                return $data;
            }
        }
        echo "error";
        Log::error('ChuangshijiPay API Error:'.json_encode($notifyData));
    }
}
