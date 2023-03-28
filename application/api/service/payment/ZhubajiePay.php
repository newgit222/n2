<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/14
 * Time: 14:38
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ZhubajiePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_scan'){



        $data = [
            'app_id'    =>  '2020158',
            'order_id'    =>  $order['trade_no'],
            'describe'    =>  'goods',
            'total_fee'    =>  sprintf("%.2f",$order["amount"]),
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    =>  $this->config['return_url'],
            'pay_mode'    =>  $type,
            'client_ip'    =>  get_userip()
        ];


        $merkey = 'PPSRTNXsZrdwwzDSUhfGBBjLetozHUpX';
        $url = 'http://bajie.lianyuntj.com';

        $data['sign'] =$this->sign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['status'] != '1' )
        {
            Log::error('Create ZhubajiePay API Error:'.$result['error']);
            throw new OrderException([
                'msg'   => 'Create ZhubajiePay API Error:'.$result['error'],
                'errCode'   => 200009
            ]);
        }
        return $result['payurl'];
    }


    function sign($arr, $sign_key) {
        ksort($arr, SORT_STRING);
        $query = [];
        foreach ($arr as $key => $val) {
            if ($val != '' && $val != 'null' && $val != null && $key != 'sign' && $key != 'key') {
                $query[] = $key . "=" . $val;
            }
        }
        $str = implode('&', $query) . '&key=' . $sign_key;
        return strtoupper(md5($str));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_scan');
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
        Log::notice("ZhubajiePay notify data1".json_encode($notifyData));
//        {"app_id":"2020158","order_id":"115841691083765","describe":"goods","order_sys_id":"pay2020031415054380449","total_fee":"399.00","timestamp":"1584169543","status":"1","sign":"583037A7825CB5A5AD1A3E0D72A97B31"}
        if($notifyData['status'] == "1" ){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['order_id'];
            return $data;
        }
        echo "ERROR";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }

}