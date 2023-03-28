<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/12
 * Time: 15:42
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class BaianPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://59.153.74.103:7768/api/orders';

        $merkey = '5feeee3820aae4cbb37b7e01a807e455';


        $data = [
            'client_id' =>  '108',
            'out_trade_no' =>  $order['trade_no'],
            'trade_type' =>  $type,
            'total_amount' =>  sprintf("%.2f",$order["amount"]),
            'nonce_str' =>  self::createNonceStr(),
            'body' =>  'goods',
            'callback_url' =>  $this->config['return_url'],
            'notify_url' =>  $this->config['notify_url'],
        ];

        $data['sign'] = $this->sign($data,$merkey);
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if(isset($result['message']))
        {
            Log::error('Create BaianPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create BaianPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_url'];
    }


    private function query($notifyData){
        $url = 'http://59.153.74.103:7768/api/orders/query';

        $merkey = '5feeee3820aae4cbb37b7e01a807e455';

        $data = [
            'client_id' =>  '108',
            'out_trade_no' =>  $notifyData['out_trade_no'],
            'trade_no' =>  $notifyData['trade_no'],
            'trade_type' =>  $notifyData['trade_type'],
            'total_amount' =>  $notifyData['total_amount'],
            'nonce_str' =>  self::createNonceStr(),
        ];
        $data['sign'] = $this->sign($data,$merkey);
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        Log::notice('query BaianPay  API notice:'.json_encode($result));

        if(isset($result['message'])){
            Log::error('query BaianPay  API Error:'.$result['message']);
            return false;
        }
        if(!isset($result['status']) ){
            return false;
        }

        if( $result['status'] != '1' ){
            return false;
        }
        return true;
    }


    function sign($arr, $sign_key) {
        ksort($arr);
        $query = [];
        foreach ($arr as $key => $val) {
            if ($val != '' && $val != 'null' && $val != null && $key != 'sign' && $key != 'key') {
                $query[] = $key . "=" . $val;
            }
        }
        $str = implode('&', $query) . '&client_secret=' . $sign_key;
        return md5($str);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
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

        $input = file_get_contents("php://input");
        Log::notice("BaianPay notify data".$input);

        $notifyData = json_decode($input,true);
        if( isset($notifyData['status']) &&  $notifyData['status'] == "1" ){

//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
//            }
        }
        echo "error";  
        Log::error('BaianPay API Error:'.$input);
    }
}
