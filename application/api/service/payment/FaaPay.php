<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/7
 * Time: 22:37
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FaaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='Wxpay'){
        $url = 'http://api.faapay.cn/pay/payapp/index';
        $merkey = '6i3trd9tkqfhjw3pxz7azkdx';
        $data = [
            'orderid_out'   =>  $order['trade_no'],
            'appid'   =>  '93539078',
            'shopid'   =>  '103',
            'paytype'   =>  $type,
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'returnurl'   =>  $this->config['return_url'],
            'notifyurl'   =>  $this->config['notify_url'],
            'version'   =>  'v1.0',
            'note'   =>  'goods',
        ];
        $data['sign'] = $this->createSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,($data)),true);
        if($result['code'] != '1' )
        {
            Log::error('Create FaaPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create FaaPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payurl'];
    }


    // 创建签名
    public function createSign($requestarray,$merkey){
        if($requestarray['note']){
            unset($requestarray['note']);
        }
        ksort($requestarray);
        $md5str = "";
        foreach ($requestarray as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $merkey));
        return $sign;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'Wxpay');
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
        Log::notice("FaaPay notify data1".json_encode($notifyData));
        if(isset($notifyData['state']) && $notifyData['state'] == "2" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderid_out'];
            return $data;
        }
        echo "error";
        Log::error('FaaPay API Error:'.json_encode($notifyData));
    }
}