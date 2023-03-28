<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
class FaCaiCloudPay extends ApiPayment
{
    public function pay($order,$type){
        $data = [
            'channel_type' => $type,
            'mer_trade_no' => $order['trade_no'],
            'money' => floor($order["amount"]),
            'notify_url' => $this->config['notify_url'],
            'partner_id' => '1588510692900737024',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $apikey = 'cfd27b9fbd144d6d8cc3d8f2ef64a09c';
        $data['sign'] = $this->getSign($data,$apikey);
        $apiurl = 'http://180.215.254.34:3000/sf/order_api/createOrder';
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result = json_decode(self::curlPost($apiurl, json_encode($data),[CURLOPT_HTTPHEADER=>$headers]), true);
        if ($result['code'] != 200){
            Log::error('Create FaCaiCloudPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create FaCaiCloudPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return  $result['data']['pay_url'];


    }
   public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'huafeiMC');
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
            $string_a .= "{$k}{$v}";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        Log::error('FaCaiCloudPay signNoKeyStr:'.$string_a);
        //签名步骤三：MD5加密
        Log::error('FaCaiCloudPay signKeyStr:'.$string_a . 'key' . $secret);
        $sign = md5($string_a . 'key' . $secret);


        return $sign;
    }


    public function notify(){
        $input = file_get_contents("php://input");
        Log::notice("FaCaiCloudPay notify data" . $input);
        $notifyData = json_decode($input, true);
        if ($notifyData['status'] == 1) {
            echo "success";
            $data['out_trade_no'] = $notifyData['mer_trade_no'];
            return $data;
        }
        echo "error";
        Log::error('FaCaiCloudPay API Error:' . $input);
    }

}
