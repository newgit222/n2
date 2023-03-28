<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/9
 * Time: 17:05
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class GgPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'http://xgo.dlwatch.cn/?c=Pay&';

        $mch_key = '97c300cb7163c7c616546b92e108e44739c0b607';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'xinyu888',
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


    public function query($notifyData){

        $url = 'http://goip.zyhdbw.cn/?c=Pay&a=query';

        $mch_key = '97c300cb7163c7c616546b92e108e44739c0b607';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'xinyu888',
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
        echo json_encode($result);
        Log::notice('query ChuanqiPay  API notice:'.json_encode($result));
        if($result['code'] != '1' ){
            return false;
        }
        if($result['data']['status'] != '9' ){
            return false;
        }
        return true;
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
   public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'2');
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
        Log::notice("ChuanqiPay notify data1".json_encode($notifyData));
//        {"money":"100.00","pt_order":"MS2020040715333456382","sh_order":"115862448142714","status":"success","time":"1586244949","sign":"8fa96c21534e35dde1bede41d281f8c4"}
        if($notifyData['status'] == "success" ){
            if(1){
                echo "success";
                $data['out_trade_no'] = $notifyData['sh_order'];
                return $data;
            }
        }
        echo "error";
        Log::error('ChuanqiPay API Error:'.json_encode($notifyData));
    }
}
