<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/14
 * Time: 20:06
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class QunfuPay extends ApiPayment
{


    /**
     * 统一下单
     */
    private function pay($order,$type='5'){
        $url = 'https://api.biqiug.com/api/pay';
        $merkey = 'yj2rq31vgj4sk6yam3i5e1kf4icznebl';
        $data = [
            'app_id'    =>  'z9wlxlnj',
            'price'    =>  sprintf("%.2f",$order["amount"])*100,
            'goods_name'    =>  'goods',
            'order_no'    =>  $order['trade_no'],
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    =>  $this->config['return_url'],
            'pay_type'    =>  $type,
            'format'    =>  'json',
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,($data)),true);
        if($result['error'] != '0' )
        {
            Log::error('Create QunfuPay API Error:'.$result['error_des']);
            throw new OrderException([
                'msg'   => 'Create QunfuPay API Error:'.$result['error_des'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_data']['url'];
    }


    public function query($notifyData){

        $url = 'https://api.biqiug.com/api/checkPay/order';

        $key = 'yj2rq31vgj4sk6yam3i5e1kf4icznebl';
        $data=array(
            'app_id'=>'z9wlxlnj',
            'order_no'=>$notifyData['order_no']
        );
        $data['sign'] = $this->getSign($data,$key);

        $result =  json_decode(self::curlPost($url,$data),true);
//
        Log::notice('query QunfuPay  API notice:'.json_encode($result));
        if(  $result['error'] != '0' ){
            Log::error('query QunfuPay  API Error:'.$result['error_des']);
            return false;
        }
        if(!$result['data']['is_pay'] ){
            return false;
        }
        return true;
    }



    public function getSign($data,$app_secret){
        ksort($data ); #key顺序排序

        $str='';

        foreach($data as $k=>$v ) $str.= ($k).'='.($v).'&'; #请勿urlencode

        $str.= 'app_secret='.$app_secret; #将秘钥加进来

        return  md5( strtolower( $str)); #先转为为小写 md5得到签名
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'5');
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
        Log::notice("QunfuPay notify data1".json_encode($notifyData));
        if(isset($notifyData['order_no'])){
            if($this->query($notifyData)) {
                echo "ok";
                $data['out_trade_no'] = $notifyData['order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('QunfuPay API Error:'.json_encode($notifyData));
    }
}