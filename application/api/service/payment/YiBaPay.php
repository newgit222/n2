<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/10
 * Time: 20:17
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YiBaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1002'){

        $url = 'https://18-pays.com//api/trans/pay';

        $secret_key = 'ef64d7f48d2bf54dcaf2c06eb66b740e';
        $data = [
            'merchant_sn' => '2000000029',
            'channel_code' => $type,
            'notify_url' => $this->config['notify_url'],
            'down_sn' => $order['trade_no'],
            'amount' => sprintf("%.2f",$order["amount"])*100,//
//            'bank_code' => $params['bank_code'],
            'pay_type' => '2',
        ];

        $data['sign'] = $this->makeSign($data, $secret_key);

        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/z.php?".http_build_query($data);
    }



    //签名

    private function makeSign($post, $secret)
    {

        ksort($post);
        $data = '';
        foreach ($post as $key => $val) {
            if (!in_array($key, ['sign', 'code', 'msg']) && $val !== '') {
                $data .= $key . '=' . $val . '&';
            }
        }

        $data .= 'key=' . $secret;
        $sign = strtolower(md5($data));

        return $sign;
    }

    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1002');
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
        Log::notice("YiBaPay notify data1".json_encode($notifyData));
        if($notifyData['code'] == '0' ){
            if($notifyData['status'] == '1' ){
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['down_sn'];
                return $data;
            }
        }
        echo "error";
        Log::error('YiBaPay API Error:'.json_encode($notifyData));
    }
}