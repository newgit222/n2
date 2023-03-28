<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/20
 * Time: 14:35
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiaomifengPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $data = [
            'money' =>  sprintf("%.2f",$order["amount"]),
            'pay_type' =>  $type,
            'platform_id' =>  '1238701123955593217',
            'random_str' =>  $this->createNonceStr('30'),
            'notify_url' =>  $this->config['notify_url'],
            'order_no' =>  $order['trade_no'],
        ];
        $Md5key = 'ZBA4OBRCQ3';
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $data["sign"] = $sign;
        $url = 'http://103.107.236.211:8083/api/pay';
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['data']['state'] != '1' )
        {
            Log::error('Create XiaomifengPay API Error:'.$result['error_msg']);
            throw new OrderException([
                'msg'   => 'Create XiaomifengPay API Error:'.$result['error_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
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
        Log::notice("XiaomifengPay notify data1".json_encode($notifyData));
//        {"pay_state":"1","order_no":"2003201428564554","money":"100.0","pay_type":"1","random_str":"OaNplaOkBN0S5if5WGFiWvHqQPvvbd","sign":"FE92A9CDAB70D8FFD736FA1C6779E90D"}
        if($notifyData['pay_state'] == "1" ){
            echo "OK";
            $data['out_trade_no'] = $notifyData['order_no'];
            return $data;   
        }
        echo "error";
        Log::error('XiaomifengPay API Error:'.json_encode($notifyData));
    }
}