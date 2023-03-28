<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
class DiDiPay extends ApiPayment
{
    public function pay($order,$type){
        $data = [
            'timestamp' => time(),
            'userId' => '火星玩家001',
            'customerNo' => 'sh1667562455144',
            'payTypeId' => $type,
            'amount' => $order["amount"],
            'orderNo' => $order['trade_no'],
            'title' =>'商品标题',
            'description'=>'商品描述',
            'customerCallbackUrl' => $this->config['notify_url'],
        ];
        $apikey = '8eca8f129c5941b683ba9bc3bdfea815';
        $data['sign'] = $this->getSign($data,$apikey);
        $apiurl = 'https://api.didi168.net/c/payment/pay';
        $result = json_decode(self::curlPost($apiurl,$data),true);

         Log::error('Create NewXxPay API Error:'.json_encode($result,true));

        if ($result['success'] !== true){
            Log::error('Create DiDiPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create DiDiPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return  $result['data']['url'];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'8005');
        return [
            'request_url' => $url,
        ];
    }

    private function getSign($data, $secret)
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a . 'key=' . $secret);


        return $sign;
    }


    public function notify(){
        $input = file_get_contents("php://input");
        Log::notice("DiDiPay notify data" . $input);
        $notifyData = json_decode($input, true);
        if ($notifyData['success'] === true) {
            echo "ok";
            $data['out_trade_no'] = $notifyData['data']['orderNo'];
            return $data;
        }
        echo "error";
        Log::error('DiDiPay API Error:' . $input);
    }

}
