<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/21
 * Time: 16:39
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class SuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='903'){

        $pay_memberid = "10165";   //商户后台API管理获取
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "0yublf7747632gbzig8tfm68fqvtov3t";   //商户后台API管理获取
        $tjurl = "http://www.shangccapi.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $type; //支付宝扫码  //商户后台通道费率页 获取银行编码
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
//echo($md5str . "key=" . $Md5key);
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "123";
        $native['pay_productname'] ='团购商品';

        $native['request_post_url']= $tjurl;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($native);
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
        Log::notice("SuPay notify data1".json_encode($notifyData));
        if($notifyData['returncode'] == "00" ){
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "error";
        Log::error('SuPay API Error:'.json_encode($notifyData));
    }

}