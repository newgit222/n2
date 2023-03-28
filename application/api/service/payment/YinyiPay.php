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

class YinyiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='2'){
        $url = 'https://pay.yy.17dayouxi.com/?c=pay';

        $mch_key = '0bc94ef60317a0530f62695d1a62fd575d2c09b5';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'zhong225',
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
        return "http://www.wantongpays.com/pay.php?".http_build_query($p_data);

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
//return true;
        $url = 'https://pay.yy.17dayouxi.com//?c=pay&a=query';

        $mch_key ='0bc94ef60317a0530f62695d1a62fd575d2c09b5';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'zhong225',
            'out_order_sn'=>$notifyData['sh_order']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);

        $result =  json_decode(self::curlPost($url,$p_data,null,20),true);
        Log::notice('query ChuangshijiPay  API notice:'.json_encode($result));
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
        $url = $this->pay($params,1);
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,1);
        return [
            'request_url' => $url,
        ];
    }


 public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,2);
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
        $url = $this->pay($params,2);
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
