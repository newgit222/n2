<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/17
 * Time: 20:32
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YunxingPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://www.zhifu678.com/submit.php';

        $merkey = 'C226E57ECEC8268BDBEA951A9382ABC7';

        $data = [
            'pid'   =>  '654227',
            'type'   =>  $type,
            'out_trade_no'   =>  $order['trade_no'],
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
            'name'   =>  'goods',
            'money'   =>  sprintf("%.2f",$order["amount"]),
//            'sign_type'   =>  'MD5',
        ];


        $data['sign'] = $this->get_Sign($data,$merkey);


        return $url.'?'.http_build_query($data);

    }


    private function query($notifyData){
        $url = 'http://www.zhifu678.com/api.php';
        $merkey = 'C226E57ECEC8268BDBEA951A9382ABC7';
        $data = [
            'act'   =>  'order',
            'pid'   =>  '654227',
            'key'   =>  $merkey,
            'out_trade_no'   =>  $notifyData['out_trade_no'],
        ];
        $result = json_decode(self::curlPost($url,$data),true);
        Log::notice('query YunxingPay  API notice:'.json_encode($result));
        if(  $result['code'] != '1' ){
            Log::error('query YunxingPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['status'] != '1' ){
            return false;
        }
        return true;
    }



    function get_Sign($arr, $sign_key) {
        ksort($arr);
        $query = [];
        foreach ($arr as $key => $val) {
            if ($val != '' && $val != 'null' && $val != null && $key != 'sign' && $key != 'key') {
                $query[] = $key . "=" . $val;
            }
        }
        $str = implode('&', $query)  . $sign_key;
        return md5($str);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
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

        $notifyData =$_GET;
        Log::notice("YunxingPay notify data1".json_encode($notifyData));
        if($notifyData['trade_status'] == "TRADE_SUCCESS" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('YunxingPay API Error:'.json_encode($notifyData));
    }
}