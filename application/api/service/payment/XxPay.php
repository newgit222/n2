<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/8
 * Time: 20:13
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XxPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://gateway.enrichtec.com:9909/api/v1.1/pay/placeorder';

        $key = 'd98c84891558fddb697ff695e77bc526';

        $data = [
            'amount'    =>  sprintf("%.2f",$order["amount"])*100,
            'attach'    =>  '123',
            'clientip'    =>  get_userip(),
            'currency'    =>  'CNY',
            'mhtorderno'    =>  $order['trade_no'],
            'mhtuserid'    =>  '123',
            'notifyurl'    =>  $this->config['notify_url'],
            'opmhtid'    =>  'xxpay',
            'paytype'    =>  $type,
            'random'    =>  self::createNonceStr(),
        ];

        $data['sign'] = $this->getSign($data,$key);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['rtCode'] != '0' ){
            Log::error('Create XxPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create XxPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['result']['payurl'];
    }

    public function getSign($native,$key){
        ksort($native);
        $md5str = "";
        foreach ($native as $k => $val) {
            $md5str = $md5str . $k . "=" . $val . "&";
        }
        return strtoupper(md5($md5str . "signkey=" . $key));
    }








    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb123213213qweqwe($params)
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
        $notifyData = $_POST;
        Log::notice("XxPay notify data1".json_encode($notifyData));
        if(isset($notifyData['mhtorderno'])  ){
                echo "success";
                $data['out_trade_no'] = $notifyData['mhtorderno'];
                return $data;  
        }
        Log::error("XxPay error data".json_encode($notifyData));
    }

}