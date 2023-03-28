<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/28
 * Time: 18:37
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ShuangziPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='Alipay'){
        $data = [
            "merchantCode"  =>  '888',
            "merchantTradeNo"  =>  $order['trade_no'],
            "userId"  =>  '1',
            "amount"  => sprintf("%.2f",$order["amount"]),
            "notifyUrl"  => $this->config['notify_url'],
            "returnUrl"  => $this->config['return_url'],
            "terminalType"  =>  '1',
            "channel"  =>  $type,
        ];
        $merkey = 'dfcb5fbe68144d0e80ec45123c81e41f';
        $url = 'https://api.nytai.com/pay/center/deposit/apply';
        ksort($data);
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= $v;
        }
        $data['sign'] = md5($tmp_str.$merkey);//签名
        $param = [
            'merchantCode'  =>  '888',
            'signType'  =>  'md5',
            'content'  =>  json_encode($data),
        ];

        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );

        $result =  json_decode(self::curlPost($url,json_encode($param),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['status'] != 'SUCCESS' )
        {
            Log::error('Create ShuangziPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create ShuangziPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }

        $result = json_decode($result['data'],true);
        $result = json_decode($result['content'],true);

        return $result['payUrl'];
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'Alipay');
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
        $url = $this->pay($params,'Wechat');
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

        $input = file_get_contents("php://input");
        Log::notice("ShuangziPay notify data".$input);

        $notifyData = json_decode($input,true);
        if(isset($notifyData['content'])){
            $notifyData = json_decode($notifyData['content'],true);
            if( isset($notifyData['tradeStatus']) &&  $notifyData['tradeStatus'] == "PAYMENT_SUCCESS" ){
                echo "success";
                $data['out_trade_no'] = $notifyData['merchantTradeNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('ShuangziPay API Error:'.$input);   
    }

}