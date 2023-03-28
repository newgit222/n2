<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/11
 * Time: 23:20
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class BolebpPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='10022'){


        $data = [
            'app_id'    =>  '7366886369af975ca2315d107c88e821',
            'trade_no'    =>  $order['trade_no'],
            'channel_id'    =>  $type,
            'money'    =>  sprintf("%.2f",$order["amount"]),
            'ip'    =>  get_userip(),
            'notify_url'    =>  $this->config['notify_url'],
            'client_type'    =>  'pc',
        ];
        $merkey = 'd72058aae2bd2e33d0f65045937f531d';
        $url = 'https://www.bolepays.com/api/pay/create';
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        if(!$result['status'])
        {
            Log::error('Create BolebpPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create BolebpPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
    }


    /**
     * 生成签名
     */
    private function getSign($data,$appSecret) {
        ksort($data);
        reset($data);

        unset($data['sign']);

        $sign = '';
        foreach ($data as $key => $val) {
            $val = trim($val);
            if ($val === '') {
                continue;
            }
            $sign .= $key . '=' . $val . '&';
        }
        $sign .= 'app_secret=' . $appSecret;
        return md5($sign);
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'10022');
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
        Log::notice("BolebpPay notify data1".json_encode($notifyData));
        echo "SUCCESS";
        $data['out_trade_no'] = $notifyData['trade_no'];
        return $data;

    }

}