<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * OC支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class BiniuPay extends ApiPayment
{
    /*
    *  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $pay_bankcode="926")
    {
        $pay_memberid = "200871657";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f", $order['amount']);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl =  $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = "http://www.baidu.com";  //页面跳转返回地址
        $Md5key = "mlv8nuuoyt5lo7xthyj9e70lkygddfig";

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
        $native['pay_attach'] = "goods";
        $native['pay_productname'] ='goods';
        $native['request_post_url'] ="https://gate.biniupay.com/submit";
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($native);
    }

    public function guma_yhk($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }


    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }
	 public function test($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }






    public function guma_yhk2($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }

    /*
     * Dn平台支付回调处理
     */
    public function notify()
    {
        Log::error('Post data from biniu' . json_encode($_REQUEST));
            if ($_REQUEST["returncode"] == "00") {
                echo "ok";
                $data["out_trade_no"] = $_POST['orderid'];
                return $data;
            }
        throw new OrderException([
            'msg' => 'Create QH API Error:',
            'errCode' => 200009
        ]);
    }

    /*
     *
     *同步通知地址处理逻辑
     */
    public function  callback()
    {
        // $plat_order_no= $_POST['out_trade_no'];
        //todo 查询订单信息的同步通知地址

        return [
            'return_url' =>'http://www.baidu.com'
        ];
    }


}
