<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/20
 * Time: 20:31
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WantengPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='919'){

        $pay_memberid = "10110";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "zdf6femj262u8ndy5kev71rlp44ied7c";   //密钥
        $tjurl = "http://www.pppw.pw//Pay_Index.html";   //提交地址
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
        $native['pay_attach'] = "123";
        $native['pay_productname'] ='goods';


        $native['request_post_url']= $tjurl;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($native);
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'919');
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
        Log::notice("WantengPay notify data".json_encode($notifyData));
//        {"memberid":"10110","orderid":"2003202044323807","transaction_id":"20200320204433491014","amount":"100.0000","datetime":"20200320204549","returncode":"00","sign":"5F14732EB278221404700CABFA91A6E8","attach":"123"}
        echo "OK";
        $data['out_trade_no'] = $notifyData['orderid'];
        return $data;  
    }
}