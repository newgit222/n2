<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/10
 * Time: 19:09
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HuanyuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type=''){

        $url = 'http://www.skdfjuzfb.xyz/api/createOrder';

        $key = '16907fe5d5f74f29bbb43092a3f4ccc5';


        // 下面的代码不要动
        $data = array();
        $data['orderNo'] = $order['trade_no'];
        $data['appId'] = '20041018595010004';
        $data['money'] = sprintf("%.2f",$order["amount"]);
        $data['returnUrl'] = $this->config['return_url'];
        $data['notifyUrl'] = $this->config['notify_url'];
        $data['productName'] = 'pay';
        $data['sign'] = md5('appId=' . $data['appId'] . '&money=' . $data['money'] . '&notifyUrl=' . $data['notifyUrl'] . '&orderNo=' . $data['orderNo'] . '&productName=' . $data['productName'] . '&returnUrl=' . $data['returnUrl'] . '&key=' . $key);
        $result = json_decode(self::curlPost($url, $data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create HuanyuPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create HuanyuPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['obj'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'');
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
        Log::notice("HuanyuPay notify data1".json_encode($notifyData));
        if(isset($notifyData['orderNo']) ){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderNo'];
            return $data;
        }
        echo "error";
        Log::error('HuanyuPay API Error:'.json_encode($notifyData));
    }
}