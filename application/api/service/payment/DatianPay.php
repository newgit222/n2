<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/17
 * Time: 22:02
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class DatianPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_trans'){


        $data = [
            'bid'   =>  '1242',
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'order_sn'   =>  $order['trade_no'],
            'notify_url'   => $this->config['notify_url'],
            'pay_type'   =>  $type,
  //          'username'   =>  '1',
//            'user_ip'   =>  get_userip(),
        ];

        $key = 'vLfYwR4CulLTclV3';

        $data['sign'] = md5(
            $key . '|' .
            $data['bid'] . '|' .
            $data['money'] . '|' .
            $data['order_sn'] . '|' .
            $data['notify_url']
        );
        $url = 'https://pay.datianpay.com/api/index';
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '100' )
        {
            Log::error('Create WanmeiPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create WanmeiPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
    }




    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_trans');
        return [
            'request_url' => $url,
        ];
    }
 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_trans');
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
        Log::notice("WanmeiPay notify data1".json_encode($notifyData));
//        {"pay_time":"1584455380","money":"200.00","pay_money":"200","order_sn":"115844545705970","sys_order_sn":"15844545219586790363","sign":"a1b5277addd9c8076aa5de3a06cbccf2"}
        echo "success";
        $data['out_trade_no'] = $notifyData['order_sn'];
        return $data;
    }
}
