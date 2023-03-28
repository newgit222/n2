<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/15
 * Time: 21:18
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LkbjPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='175409'){
        $url = 'https://upay.lkbj.site/pay/order';
        $merkey = 'c9c2d344f0fa33c1887f7d7a4ea5a75b';
        $data = [
            'business_order_no'  =>  $order['trade_no'],
            'business_no'  => '843851',// '843851',
            'pay_code'  =>  $type,
            'amount'  =>  sprintf("%.2f",$order["amount"]),
            'asynchronous_url'  =>  $this->config['notify_url'],
            'attach'  =>  '123',
            'type'  =>  'form',
        ];
        $data['sign'] = $this->createSign($data,$merkey);
        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".(http_build_query($data));

    }


    public function query($notifyData){
        $url = 'https://upay.lkbj.site/pay/queryOrder';
        $key = 'c9c2d344f0fa33c1887f7d7a4ea5a75b';
        $data=array(
            'business_no'=>'843851',
            'business_order_no'=>$notifyData['order_no']
        );
        $data['sign'] = $this->createSign($data,$key);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query LkbjPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query LkbjPay  API Error:'.$result['message']);
            return false;
        }
        if($result['data']['status'] != 'SUCCESS' ){
            return false;
        }
        return true;
    }



    public function createSign($array_data, $key)
    {
        $signPars = '';
        ksort($array_data);
        foreach ($array_data as $k => $v) {
            if ($v != '' && $k != 'sign') {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = strtoupper(md5($signPars));
        return $sign;
    }

    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'175409');
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
        Log::notice("LkbjPay notify data1".json_encode($notifyData));
        if(isset($notifyData['status']) && $notifyData['status'] == 'SUCCESS' ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('LkbjPay API Error:'.json_encode($notifyData));
    }

}