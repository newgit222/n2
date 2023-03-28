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

class FenggouPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){

        $url = 'http://a.uidfcoin.com/api/neworder';

        $merkey = '228c89aaab7e15e036e72a20850ff756';

        $data = [
            'order_id'  =>  $order['trade_no'],
            'user_name'  =>  'xxpay',
            'amount'  =>  sprintf("%.2f",$order["amount"])*100,
            'pay_channel'  =>  $type,
            'notify_url'  =>  $this->config['notify_url'],
            'vcode' =>  '123'
        ];

        $data['sign'] = $this->getSign($data,$merkey);



         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '1' )
        {
            Log::error('Create FenggouPay API Error:'.$result['data']);
            throw new OrderException([
                'msg'   => 'Create FenggouPay API Error:'.$result['data'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
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
        $url = $this->pay($params,'1');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WXCODE');
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
        Log::notice("FenggouPay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['status'] == "2" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['order_id'];
            return $data;
        }
        echo "error";
        Log::error('FenggouPay API Error:'.$input);
    }

}