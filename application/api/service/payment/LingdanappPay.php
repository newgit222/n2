<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/8
 * Time: 17:45
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LingdanappPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_app'){

        $url = 'https://pay.falabo.cn/index.php/Qypay/order';

        $merkey = '452ff0fc7c5b723984cd33856aa0a3ca';

        $data = [
            'merchant_id'   =>  '20200417145027210',
            'orderid'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notify_url'   =>  $this->config['notify_url'],
//            'attach'   =>  '',
            'pay_type'   =>  $type,
        ];
        $sign = 'merchant_id='.$data['merchant_id'].'&orderid='.$data['orderid'].'&amount='.$data['amount'].'&notify_url='.$data['notify_url'].'&key='.$merkey;
        $data['sign'] = md5($sign);
        $result =  json_decode(self::curlPost($url,($data)),true);
        if(!isset($result['trade_no'])){
            Log::error('Create LingdanappPay API Error:');
            throw new OrderException([
                'msg'   => 'Create LingdanappPay API Error:',
                'errCode'   => 200009
            ]);
        }
        return 'trade_no='.$result['trade_no'].'&biz_type=share_pp_pay&biz_sub_type=peerpay_trade&presessionid=&app=tb&channel=&type2=gulupay';

    }





    /**
     * @param $params
     * 支付宝
     */
    public function ali_sdk($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_app');    
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
        Log::notice("LingdanPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "1" ){
            echo "ok";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "error";
        Log::error('LingdanPay API Error:'.json_encode($notifyData));
    }
}
