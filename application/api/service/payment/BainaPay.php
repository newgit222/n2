<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/10
 * Time: 18:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class BainaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $data = [
            'mchid' =>  '90097',
            'mchno' =>  $order['trade_no'],
            'tradetype' =>  $type,
            'totalfee' =>  sprintf("%.2f",$order["amount"])*100,
            'descrip' =>  'goods',
            'attach' =>  'goods',
            'clientip' =>  get_userip(),
            'notifyurl' =>  $this->config['notify_url'],
            'returnurl' =>  $this->config['return_url'],
        ];

        $merkey = '8bfb0164149446489b0d394d60d7faf6';
        $url = 'http://pay.bainapays.com/pay/payIndex';
        $data['sign'] = $this->getSign($merkey,$data);
        return $url.'?'.http_build_query($data);
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {

        // 去空
        $data = array_filter($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);


        return $sign;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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
        $url = $this->pay($params,'weixin');
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
        Log::notice("BainaPay notify data1".json_encode($notifyData));
//        {"sign":"7DB88C243A6368B40B1AFE83399B263B","mchno":"115838439122252","mchid":"90097","attach":"goods","transactionid":"182003102038327159","totalfee":"100.0","resultcode":"1","tradetype":"alipay"}
        if($notifyData['resultcode'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['mchno'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }

}