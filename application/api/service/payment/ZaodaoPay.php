<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/10
 * Time: 21:00
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class ZaodaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){

        $data = [
            'goodsname' =>  'goods',
            'uid' =>  '100097',
            'orderid' =>  $order['trade_no'],
            'orderuid' =>  '1',
            'price' => sprintf("%.2f",$order["amount"]),
            'istype' =>  $type,
            'notify_url' =>  $this->config['notify_url'],
            'return_url' =>  $this->config['return_url'],
        ];
        $merkey = '4d46248a71c3d3078c0bae222b12a28d';
        $url = 'http://www.wo159.com:9896/pay/PayMent.do';
        $data['sign'] = $this->getSign($merkey,$data);
//        return $url.'?'.http_build_query($data);
//        return $result =  json_decode(self::curlPost($url,$data),true);
        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {

       $result = md5($data['goodsname'].$data['istype'].$data['notify_url'].$data['orderid'].$data['price'].$data['return_url'].$data['uid'].$secret);

        return $result;
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
     * 微信
     */
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
        $notifyData = $_GET;
        Log::notice("ZaodaoPay notify data1".json_encode($notifyData));
//        {"signinfo":"FAE17B1696915BD8BAAAB522DFD6B05F","total_fee":"10.0","trade_no":"115838494320334","attach":"null","status":"0"}
        if($notifyData['status'] == "0" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['trade_no'];
            return $data;
        }
        echo "error";
        Log::error('ZaodaoPay API Error:'.json_encode($notifyData));
    }
}