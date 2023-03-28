<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 16:31
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JubaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='932'){

        $pay_memberid = "50321";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "8917e376f8a14cbe93e03dcdeb83b2c5";   //密钥
        $tjurl = "http://gateway.jubaofupay.com/api.aspx?";   //提交地址
        $pay_bankcode = $type;   //银行编码
        //扫码
        $native = array(
            "parter" => $pay_memberid,
	    "type" => $pay_bankcode,
           // "pay_orderid" => $pay_orderid,
            "value" => $pay_amount,
 "orderid" => $pay_orderid,
          //  "pay_applydate" => $pay_applydate,
          ///  "pay_bankcode" => $pay_bankcode,
//            "hrefbackurl" => $pay_notifyurl,
            "callbackurl" => $pay_callbackurl,
        );
    //    ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
$md5str = substr($md5str , 0, -1). $Md5key;
        $sign = strtolower(md5($md5str));
        $native["sign"] = $sign;
//var_dump($native);die();
return  $tjurl.urldecode(http_build_query($native));die();
        $result =  json_decode(self::curlPost($tjurl,$native,null,15),true);
var_dump( self::curlPost($tjurl,$native,null,15));die();        

if(!isset($result['payurl'])  ||  !$result['payurl'] ){
            Log::error('Create TianchengswPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create TianchengswPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payurl'];
    }

    public function getSign($native,$key){
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return strtoupper(md5($md5str . "key=" . $key));
    }


    public function query($notifyData){

	return true;

        $url = 'http://www.oyqfwtt.cn/Pay_Trade_query.html';

        $Md5key = "z3fy6t7d6bm4kl42ibwop0qlcds5fzyd";
        $native=array(
            'pay_memberid'=>'10028',
            'pay_orderid'=>$notifyData['orderid']
        );

        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;

        $result =  json_decode(self::curlPost($url,$native),true);
        Log::notice('query TianchengswPay  API notice:'.json_encode($result));
        if(  $result['returncode'] != '00' ){
            Log::error('query TianchengswPay  API Error:'.$result['trade_state']);
            return false;
        }
        if($result['trade_state'] != 'SUCCESS' ){
            return false;
        }
        return true;
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'903');
        return [
            'request_url' => $url,
        ];
    }




 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipaywap');
        return [
            'request_url' => $url,
        ];
    }

 public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'1007');
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
        $url = $this->pay($params,'1007');
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
        Log::notice("TianchengswPay notify data1".json_encode($notifyData));
        if(1 ){
            if(1){
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        Log::error("TianchengswPay error data".json_encode($notifyData));

    }
}
