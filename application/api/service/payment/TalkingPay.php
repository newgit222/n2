<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/20
 * Time: 21:12
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class TalkingPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='ALI_H5'){
        $url = 'http://47.104.168.97/pay-api/unified/pay';
        $merkey = 'rjol6kxtj2qn1lws1vo8bk2uzm1y2d92';
        $data = [
            'mch_no'    =>  '5010662002271411740835',
            'app_id'    =>  '10052002271411624075',
            'nonce_str'    =>  self::createNonceStr(),
            'trade_type'    =>  $type,
            'total_fee'    =>  sprintf("%.2f",$order["amount"])*100,
            'body'    =>  'goods',
            'notify_url'    =>  $this->config['notify_url'],
            'out_trade_no'    =>  $order['trade_no'],
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['return_code'] != '0000' )
        {
            Log::error('Create TalkingPay API Error:'.$result['return_msg']);
            throw new OrderException([
                'msg'   => 'Create TalkingPay API Error:'.$result['return_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_info'];
    }


    public function query($notifyData){
        $url = 'http://47.104.168.97/pay-api/unified/query';

        $key = 'rjol6kxtj2qn1lws1vo8bk2uzm1y2d92';
        $data=array(
            'mch_no'=>'5010662002271411740835',
            'app_id'=>'10052002271411624075',
            'nonce_str'=>self::createNonceStr(),
            'out_trade_no'=>$notifyData['out_trade_no'],
        );
        $data['sign'] = $this->getSign($data,$key);
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        Log::notice('query LuckyPay  API notice:'.json_encode($result));
        if(  $result['return_code'] != '0000' ){
            Log::error('query TalkingPay  API Error:'.$result['return_msg']);
            return false;
        }
        if($result['trade_status'] != 'SUCCESS' ){
            Log::error('query TalkingPay  API Error:'.$result['trade_desc']);
            return false;
        }
        return true;
    }




    public function getSign($parameters,$key){
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= 'key='.$key;
        $sign = md5($signPars);
        return strtoupper($sign);
    }

    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ALI_H5');
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
        Log::notice("TalkingPay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['return_code'] == "0000" ){
            if($notifyData['trade_status'] == "SUCCESS") {
                if($this->query($notifyData)) {
                    echo "success";
                    $data['out_trade_no'] = $notifyData['out_trade_no'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('TalkingPay API Error:'.$input);
    }
}