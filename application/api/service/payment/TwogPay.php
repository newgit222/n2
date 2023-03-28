<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/2
 * Time: 18:33
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class TwogPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='zfb001'){

        $url = 'https://s.2048pay.com/api/createOrder';

        $data = [
            'businessCode'  =>  '2A01A509',
            'outTradeNo'  =>  $order['trade_no'],
            'type'  =>  $type,
            'notifyUrl'  =>  $this->config['notify_url'],
            'goodsName'  =>  'goods',
            'amount'  =>  sprintf("%.2f",$order["amount"]),
        ];
        $key = 'DC520F543C5BF2A2DE9449A72D74B5B5';

        $data['sign'] = $this->getSign($data,$key);
         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create TwogPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create TwogPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }





    public function getSign($data,$key){
        ksort($data);

        $data['key'] = $key;
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1);
        return md5($tmp_str);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfb001');
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
        Log::notice("TwogPay notify data".json_encode($notifyData));
        if($notifyData['tradeStatus'] == 'TRADE_SUCCESS' ){
            echo "success";
            $data['out_trade_no'] = $notifyData['merchOrderNo'];
            return $data;
        }
        echo "FAIL";
        Log::error('TwogPay API Error:'.json_encode($notifyData));
    }

}