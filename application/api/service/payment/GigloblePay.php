<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/6
 * Time: 22:28
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class GigloblePay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='113'){
        $url = 'https://gp.gi-global.com:817/order/initOrder.aspx';

        $data = [
            'version'   =>  '1.0',
            'custid'   =>  '1002168161',
            'ordercode'   =>  $order['trade_no'],
            'ordertype'   =>  $type,
            'amount'   =>  sprintf("%.2f",$order["amount"])*100,
            'backurl'   =>  $this->config['notify_url'],
            'fronturl'   =>  $this->config['return_url'],
            'backmemo'   =>  '123',
//            'format'   =>  'json',
        ];

        $key = '6g2oedxs2';
        $data['sign'] = $this->getSign($data,$key);
        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        if($result['code'] != 'SUCCESS' )
        {
            Log::error('Create GigloblePay API Error:'.$result['MSG']);
            throw new OrderException([
                'msg'   => 'Create GigloblePay API Error:'.$result['MSG'],
                'errCode'   => 200009
            ]);
        }
        return $result['codeurl'];
    }


    public function getSign($parameters,$key){
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = md5($signPars);
        return $sign;
    }

    /**
     * @param $params
     * 支付宝
     */
    public function guma_yl($params)
    {
        //获取预下单
        $url = $this->pay($params,'113');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function guma_jd($params)
    {
        //获取预下单
        $url = $this->pay($params,'114');
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
        Log::notice("GigloblePay notify data".$input);
//        {"custid":"1002168161","ordercode":"115861862153759","amount":"10000","orderstatus":"2","backmemo":"123","sign":"e1c6b5cd63e20d78959ed82a9444a9e5"}
        $notifyData = json_decode($input,true);
        if($notifyData['orderstatus'] == "2" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['ordercode'];
            return $data;
        }
        echo "error";
        Log::error('GigloblePay API Error:'.$input);  
    }
}