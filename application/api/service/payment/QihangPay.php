<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/1
 * Time: 15:01
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class QihangPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ap'){

        $data = [
            'merchant'  =>  'YB20030114441',
            'qrtype'  =>  $type,
            'customno'  => $order['trade_no'],
            'money'  => sprintf("%.2f",$order["amount"]),
            'sendtime'  =>  time(),
            'notifyurl'  => $this->config['notify_url'],
            'backurl'  => $this->config['return_url'],
            'risklevel'  =>  '5',
        ];



        $merkey = '248152be0bc7de7c66af3936f8b02d1e';
        $url = 'http://member.qihangpay.cn/api/v3/cashier.php';
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1).$merkey;
        $data['sign'] = md5($tmp_str);//签名
        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".htmlspecialchars(http_build_query($data));

    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ap');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'wp');
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
        Log::notice("Qihang notify data".json_encode($notifyData));
//        {"merchant":"YB20030114441","merchant_money":"1.00","qrtype":"ap","customno":"115830524683927","sendtime":"1583052468","orderno":"0301164758490","money":"1.00","paytime":"1583052654","state":"1","sign":"b92ced85171620cd4b0630c5aea90523"}
        if($notifyData['state'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['customno'];
            return $data;
        }
        echo "error";
        Log::error('Qihang API Error:'.json_encode($notifyData));
    }

}