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
class DnPay extends ApiPayment
{


    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $type="aliwap")
    {
        $version='1.0';
        $customerid= "11107";
        $userkey="da556e315385e4361516b39fee43fbf07a0066c9";
        $sdorderno= $order['trade_no'];
        $total_fee=sprintf("%.2f", $order['amount']);

        $notifyurl = $this->config['notify_url'];
        $returnurl = "http://www.baidu.com";
        $remark=!empty($_POST['remark'])?$_POST['remark']:'123';

        $data = array(
            "version"      => $version,
            "customerid"        => $customerid,
            "sdorderno"        => $sdorderno,
            "total_fee"      => $total_fee,
            "paytype"    => $type,
            "notifyurl"    => $notifyurl,
            "returnurl"    => $returnurl,
            "remark"    => $remark,
            "bankcode"    => "",
            "get_code"    => "",
        );
        $sign=md5('version='.$version.'&customerid='.$customerid.'&total_fee='.$total_fee.'&sdorderno='.$sdorderno.'&notifyurl='.$notifyurl.'&returnurl='.$returnurl.'&'.$userkey);

        $data["sign"] = $sign;

       // $jsapi["json"] = "json";
        $url = "http://www.xinyipay.com/apisubmit?".http_build_query($data);

        return $url;
    }

    public function h5_zfb($params)
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
        Log::error('Post data from dana' . json_encode($_POST));
        $status=$_POST['status'];
        $customerid=$_POST['customerid'];
        $sdorderno=$_POST['sdorderno'];
        $total_fee=$_POST['total_fee'];
        $paytype=$_POST['paytype'];
        $sdpayno=$_POST['sdpayno'];
        $remark=$_POST['remark'];
        $sign=$_POST['sign'];
        $userkey="da556e315385e4361516b39fee43fbf07a0066c9";
        $mysign=md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$userkey);

        if($sign==$mysign) {
            if ($status == '1') {
                echo "success";
                $data["out_trade_no"] = $_POST['sdorderno'];
                return $data;
            } else {
                echo "fail";die();
            }
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
