<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/28
 * Time: 0:18
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LingpaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay.native'){



        $data = [
            'amount'    =>  sprintf("%.2f",$order["amount"])*100,
            'orderNo'    =>  $order['trade_no'],
            'payType'    =>  $type,
            'returnUrl'    =>  $this->config['return_url'],
            'callbackUrl'    =>  $this->config['notify_url'],
            'mid'    =>  '109',
            'remark'    =>  'goods',
            'key'   =>  'QGLZoLPq'
        ];

        ksort($data);
        $pay_data = "";
        foreach ($data as $key => $val) {
            $pay_data = $pay_data . $key . "=" . $val . "&";
        }

        $pay_data = substr($pay_data,0,strlen($pay_data) - 1);
        $sign = md5($pay_data);
        $data['sign'] = $sign;
        $url = 'http://132.232.48.102/addorderpost';
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['returnCode'] != '0' )
        {
            Log::error('Create LingpaoPay API Error:'.$result['returnMsg']);
            throw new OrderException([
                'msg'   => 'Create LingpaoPay API Error:'.$result['returnMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['url'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay.native');
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
        Log::notice("LingpaoPay notify data".$input);

        $notifyData = json_decode($input,true);
        if($notifyData['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['outOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('LingpaoPay API Error:'.$input);
    }
}