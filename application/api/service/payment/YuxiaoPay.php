<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/25
 * Time: 22:18
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YuxiaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='3'){
        $url = 'http://m.yuxiaopay.com/webMerchantPay/scanCodePay';      
        $merkey = 'dsfdjfjvcnvmxchksdjs565623';
        $data = [
            'mchNo' =>  '1350635567',
            'mchUserNo' =>  '1350635567',
            'outTradeNo' =>  $order['trade_no'],
            'channel' => $type,
            'amount' =>  sprintf("%.2f",$order["amount"]),
            'body' =>  'goods',
            'payDate' =>  date('YmdHis'),
            'notifyUrl' =>  $this->config['notify_url'],
            'returnUrl' =>  $this->config['return_url'],
        ];
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $pay_data .= '&signKey=' .$merkey;
        $sign = md5($pay_data);
        $data['sign'] = $sign;
        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);
    }


    /**
     * @param $params
     * 支付宝
     */
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
        Log::notice("YuxiaoPay notify data1".json_encode($notifyData));
        if($notifyData['resultCode'] == "00" ){
            if($notifyData['returnCode'] == '2' ) {
                echo "success";
                $data['out_trade_no'] = $notifyData['outTradeNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }
}