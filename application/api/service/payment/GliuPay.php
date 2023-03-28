<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/2
 * Time: 17:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class GliuPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'https://v1.thesihai.com/appmerchantproxy/transfer';
        $Md5key = 'a457c5b4c3ec79e97b4eec14ed4ad198';
        $data = [
            'partner'   =>  '1004180',
            'payment_type'   =>  $type,
            'total_fee'   =>  sprintf("%.2f",$order["amount"]),
            'out_trade_no'   =>  $order['trade_no'],
            'return_url'   =>  $this->config['return_url'],
            'notify_url'   =>  $this->config['notify_url'],
            'body'   =>  'goods',
        ];
        $data['sign']   = md5("partner=".$data['partner']."&out_trade_no=".$data['out_trade_no']."&total_fee=".$data['total_fee']."&payment_type=".$data['payment_type']."&notify_url=".$data['notify_url']."&return_url=".$data['return_url']."&body=".$data['body'].$Md5key);
        $data['request_post_url'] =$url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }


    public function query($notifyData){
        $url = 'https://v1.thesihai.com/appmerchantproxy/getstatus';

        $Md5key = 'a457c5b4c3ec79e97b4eec14ed4ad198';
        $native=array(
            'order_cp'=>$notifyData['out_trade_no']
        );
        $native["sign"] = md5("order_cp=".$native['order_cp'].$Md5key);
        $result =  json_decode(self::curlPost($url,$native),true);
        Log::notice('query GliuPay  API notice:'.json_encode($result));
        if(  $result['Retcode'] != '200' ){
            Log::error('query GliuPay  API Error:'.$result['Message']);
            return false;
        }
        if($result['Status'] != '2' ){
            return false;
        }
        return true;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'2');  
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
        $notifyData = $_GET;
        Log::notice("GliuPay notify data1".json_encode($notifyData));
        if( isset($notifyData['trade_status']) &&   $notifyData['trade_status'] == '1' ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        Log::error("GliuPay error data".json_encode($notifyData));

    }

}