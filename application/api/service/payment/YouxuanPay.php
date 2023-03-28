<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/28
 * Time: 15:00
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use phpDocumentor\Reflection\Types\Self_;
use think\Log;

class YouxuanPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type=''){

        $url = 'http://180.215.254.34:3000/sf/order_api/createOrder';
        $signKey = 'd72570dbbf43439f9aa27744fbcecbc2';


        $data = array(
            "notify_url"=>$this->config['notify_url'],
           "partner_id"=>"1573956682478915584",
          "money"=> sprintf("%.2f",$order["amount"]),
          "channel_type"=> $type,
          "mer_trade_no"=>$order['trade_no'],
          "timestamp"=> date('Y-m-d H:i:s')
        );
        $data['sign'] = $this->getSign($data, $signKey);
        $data['attach'] = 'goods';

        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );

        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);

        if($result['code'] != '200' )
        {
            Log::error('Create YouxuanPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create YouxuanPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }



    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($data, $secret)
    {
        $signPars = "";
        ksort($data);
        foreach($data as $k => $v) {
            $signPars .= $k . $v;
        }
        $signPars .=  'key' . $secret;
        $sign = md5($signPars);
        return $sign;
    }


    /**
     * @param $params
     * @return array
     *  test
     */
    public function wap_zfb($params){
        //获取预下单
        $url = $this->pay($params, 'huafei');
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
        Log::notice("YouxuanPay notify data".$input);

        $notifyData = json_decode($input,true);
        if($notifyData['status'] == '1' ){
            echo "success";
            $data['out_trade_no'] = $notifyData['mer_trade_no'];
            return $data;
        }
        echo "FAIL";
        Log::error('YouxuanPay API Error:'.$input);
    }
}
