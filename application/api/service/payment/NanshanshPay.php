<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/26
 * Time: 21:09
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class NanshanshPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='NAN4'){
        $url = 'http://www.zia8.com/Pay.php';
        $data = [
            'UserTradeNo'   =>  $order['trade_no'],
            'UserUId'   =>  '10084',
            'PayTimes'   =>  time(),
            'NotifyLink'   =>  $this->config['notify_url'],
            'ReturnLink'   =>  $this->config['return_url'],
            'BankCode'   =>  $type,
            'PayNames'   =>  'goods',
            'PayMoney'   =>  sprintf("%.2f",$order["amount"]),
        ];
        $data['Sign'] = $this->getSign($data);

        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);

    }



    public function query($notifyData){
        $url = 'http://www.zia8.com/QueryOrder.php';
        $data=array(
            'UserUId'=>'10084',
            'UserTradeNo'=>$notifyData['UserTradeNo']
        );
        $data['Sign'] = $this->getSign($data);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query NanshanshPay  API notice:'.json_encode($result));
        if(  $result['code'] != '1' ){
            Log::error('query NanshanshPay  API Error:'.$result['message']);
            return false;
        }
        if(count($result['data']) <1 ){
            return false;
        }

        if($result['data'][0]['status'] != '2' ){
            return false;
        }
        return true;
    }




    public function getSign($param){
        ksort($param);
        $md5str = "";
        foreach ($param as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return  strtoupper(md5($md5str.'key=86ZJQpPAjc3LZ33iU8B638B6qZ3IIj68'));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'NAN4');
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
        $notifyData = $_POST;
        Log::notice("NanshanshPay notify data1".json_encode($notifyData));


        if($notifyData['PayStatus'] == "Order_SUCCESS" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['UserTradeNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }
}
