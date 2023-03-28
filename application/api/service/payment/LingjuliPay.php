<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/5
 * Time: 14:26
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LingjuliPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='901'){


        $customer_no = "200794880";   //商户ID
        $customer_order = $order['trade_no'];    //订单号
        $amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $produce_date = date("Y-m-d H:i:s");  //订单时间
        $notify_url = $this->config['notify_url'];   //服务端返回地址
        $callback_url = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "5litbqmu5e9d4avoq0ws0drbcqnxfa5b";   //密钥
        $tjurl = "http://www.027pay.com/Pay_Defray.html";   //提交地址
        //扫码
        $native = array(
            "customer_no" => $customer_no,
            "customer_order" => $customer_order,
            "amount" => $amount,
            "produce_date" => $produce_date,
            "bank_code" => $type,
            "notify_url" => $notify_url,
            "callback_url" => $callback_url,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["sign_md5"] = $sign;
        $native['append_fields'] = "1234|456";
        $native['goods_name'] ='VIP基础服务';


        $native['request_post_url'] =$tjurl;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($native);
    }


    public function query($notifyData){
        $url = 'http://www.027pay.com//Pay_Trade_query.html';

        $Md5key = '5litbqmu5e9d4avoq0ws0drbcqnxfa5b';
        $native=array(
            'customer_no'=>'200794880',
            'customer_order'=>$notifyData['customer_order']
        );

        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["sign_md5"] = $sign;

         $result =  json_decode(self::curlPost($url,$native),true);
        Log::notice('query LingjuliPay  API notice:'.json_encode($result));
        if(  $result['trading_code'] != '00' ){
            Log::error('query LingjuliPay  API Error:'.$result['trade_state']);
            return false;
        }
        if($result['trading_state'] != 'SUCCESS' ){
            return false;
        }
        return true;
    }


    /**
     * @param $params
     * @return array
     * 微信
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'901');
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
        Log::notice("LingjuliPay notify data".json_encode($notifyData));
//        $notifyData = json_decode($input,true);
        if($notifyData['trading_code'] == "00" ){
            if($this->query($notifyData)) {
                echo "OK";
                $data['out_trade_no'] = $notifyData['customer_order'];
                return $data;  
            }
        }
        echo "error";
        Log::error('LingjuliPay API Error:'.json_encode($notifyData));
    }

}
