<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/19
 * Time: 13:06
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class ShinianPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='903'){
        $pay_memberid = "10160";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号，需要保证唯一，不可重复提交相同订单ID
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "nejl4kvpll2wdyq7zdckg52s2fh499ik";   //密钥
        $tjurl = "http://511gtpay.zhuque96.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $type;   //银行编码
//扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] ='goods';
        $native['pay_return_type'] = "html";//html：网页，json：为接口json方式输出

        $native['request_post_url']= $tjurl;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($native);
//        return $result = self::curlPost($tjurl,$native);
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
        Log::notice("ShinianPay notify data1".json_encode($notifyData));
//        {"memberid":"10160","orderid":"115846005716614","transaction_id":"20200319144933100569","amount":"500.0000","datetime":"20200319145318","returncode":"00","sign":"309DE4073AEA47EBB88705DCB158FF35","attach":"1234|456"}
        if($notifyData['returncode'] == "00" ){
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "error";
        Log::error('ShinianPay API Error:'.json_encode($notifyData));
    }
}