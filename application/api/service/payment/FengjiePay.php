<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/20
 * Time: 20:05
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\helper\hash\Md5;
use think\Log;

class FengjiePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='zkl'){

        $url = 'https://merchant.cdfjpay.com/api/neworder';

        $merkey = '660588c5-cf90-4878-9a16-a7664539b079';

        $data = [
            'MerchantOrderNum'  =>  $order['trade_no'],
            'MerchantAccount'  =>  'zhong225',
            'Amount'  =>  intval($order["amount"]),
            'PayType'  =>  $type,
            'ReturnUrl'  =>  $this->config['notify_url'],
        ];
$str = $data['MerchantAccount'].$data['MerchantOrderNum'].$data['Amount'].$data['ReturnUrl'].$merkey;
        $data['Sign'] = strtoupper(md5($str));
 $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);

//var_dump($data);;

//         $result =  json_decode(self::curlPost($url,$data),true);
//var_dump($result);die();
        if($result['Code'] != '0' )
        {
            Log::error('Create FenggouPay API Error:'.json_encode($result));
            throw new OrderException([
                'msg'   => 'Create fengjiePay API Error:'.$result['data'],
                'errCode'   => 200009
            ]);
        }
        return $result['PayUrl'];
    }

//    public function query($notifyData){
//        $url = 'http://a.uidfcoin.com/api/GerOrder';
//
//        $key = '228c89aaab7e15e036e72a20850ff756';
//        $data=array(
//            'order_id'  =>  $notifyData['order_id'],
//            'user_name' => 'xxpay',
//        );
//        $data['sign'] = $this->getSign($data,$key);//strtoupper(md5($data['orderId'].$key));
//
//        return $result =  json_decode(self::curlPost($url,$data),true);
//        Log::notice('query FenggouPay  API notice:'.json_encode($result));
//        if(  $result['code'] != '1' ){
//            Log::error('query FenggouPay  API Error:'.$result['data']);
//            return false;
//        }
//        if($result['data']['status'] != '2' ){
//            return false;
//        }
//        return true;
//    }




    public function getSign($data,$userkey){
        ksort($data);
        $string1 = '';
        foreach ($data as $key => $v) {
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key=" . $userkey;

        return strtoupper(md5($string1));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
  public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        Log::notice("FengjiePay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['Status'] == "36" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['MerchantOrderID'];
            return $data;
        }
        echo "error";
        Log::error('FenggouPay API Error:'.$input);
    }

}
