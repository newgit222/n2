<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/12
 * Time: 21:38
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class HiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://vip.hipay123.com/submit.php';

        $merkey = '30226b2bd8d8ed1f628842c438a9e4c3';

        $data = [
            'pid'   =>  '100105',
            'type'   =>  $type,
            'out_trade_no'   =>  $order['trade_no'],
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
            'name'   =>  'goods',
            'money'   =>  sprintf("%.2f",$order["amount"]),
//            'sign_type'   =>  'MD5',
        ];
        $data['sign'] = $this->get_Sign($data,$merkey);
        return $url.'?'.http_build_query($data);

    }





    function get_Sign($arr, $sign_key) {
        ksort($arr);
        $query = [];
        foreach ($arr as $key => $val) {
            if ($val != '' && $val != 'null' && $val != null && $key != 'sign' && $key != 'key') {
                $query[] = $key . "=" . $val;
            }
        }
        $str = implode('&', $query)  . $sign_key;
        return md5($str);
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

        $notifyData =$_GET;
        Log::notice("HiPay notify data1".json_encode($notifyData));
        if( isset($notifyData['trade_status']) && $notifyData['trade_status'] == "TRADE_SUCCESS" ){
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
        }
        echo "error";
        Log::error('HiPay API Error:'.json_encode($notifyData));
    }

}