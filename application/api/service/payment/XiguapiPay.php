<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/6
 * Time: 14:48
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiguapiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){
        $url = 'https://api.xiguapay.net/openapi/thirdpay/getqr';
        $merkey = '83f17e541d10b3e3309163310364facf';
        $data = [
            'pay_type'  =>  $type,
            'order_id'  =>  $order['trade_no'],
            'money'  =>  sprintf("%.2f",$order["amount"])*100,
            'client_ip'  =>  get_userip(),
            't'  =>  time(),
            'ver'  =>  'v1',
            'is_mobile'  =>  '0',
            'app_id'  =>  '050660248',
            'cb_url'  =>  $this->config['notify_url'],
            'mark'  =>  'goods',
        ];
        $data['sign'] = $this->getSign($data,$merkey);
         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['status'] != '1' )
        {
            Log::error('Create XiguapiPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create XiguapiPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }

    public function getSign($parameters,$key){
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v ;
            }
        }
        $signPars .= $key;
        $sign = md5($signPars);
        return $sign;
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
        $notifyData =$_POST;
        Log::notice("XiguapiPay notify data1".json_encode($notifyData));

        if($notifyData['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['order_id'];
            return $data;
        }
        echo "err";
        Log::error('XiguapiPay API Error:'.json_encode($notifyData));
    }
}