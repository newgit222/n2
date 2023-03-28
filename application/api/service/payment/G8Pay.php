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
class G8Pay extends ApiPayment
{
    /*
    *  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $pay_bankcode="alipay")
    {
        $pay_memberid = "10000045";   //
        $pay_amount = intval($order['amount']);    //交易金额
        $Md5key = "375e2425fb2d8eed15e579500537fd32ea62f28c";     //密钥
        $data = array(
            "customerid" =>$pay_memberid, // 商户号
            "orderid" => $order['trade_no'], // 订单号
            "total_fee" => $pay_amount, // 订单金额 整数不能带任何小数点
            "trade_type" => $pay_bankcode, // 交易类型
            "notify_url" => $this->config['notify_url'], // 异步通知地址
            "nonce_str" => md5(time()), // 随机字符串
            "return_url" => "http://www.aaa.com", // 前台通知地址
            "subject" => "shop", // 订单标题
            "body" =>"shop", // 订单描述
            "client_ip" =>"183.62.225.12" // 客户端IP
        );
        $post_data = array_filter($data);
        ksort($post_data);
        $reqPar = urldecode(http_build_query($post_data)) . "&key=" . $Md5key;
        $post_data['sign'] = strtoupper(md5($reqPar));
        $post_data['request_post_url'] ="http://www.8gfastpay.com/gateway/payment";
        return "http://caishen.sviptb.com/pay.php?".http_build_query($post_data);
    }


    public function guma_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }

    public function wap_vx($params)
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
        Log::error('Post data from 8g:'.file_get_contents('php://input'));
 $_REQUEST =	json_decode(file_get_contents('php://input'), true);
        $returnArray = array( // 返回字段
            "customerid" => $_REQUEST['data']["customerid"], // 商户ID
            "orderid" =>  $_REQUEST['data']["orderid"], // 订单号
            "nonce_str" =>  $_REQUEST['data']["nonce_str"], // 交易金额
            "pay_status" =>  $_REQUEST['data']["pay_status"], // 交易时间
            "time" =>  $_REQUEST['data']["time"], // 支付流水号
            "total_fee" => $_REQUEST['data']["total_fee"],
//	   'sign' => $_REQUEST['data']["sign"],
        );

        $Md5key = "375e2425fb2d8eed15e579500537fd32ea62f28c";
        $post_data = $returnArray;
        ksort($post_data);
        $reqPar = urldecode(http_build_query($post_data)) . "&key=" . $Md5key;
	$sign = strtoupper(md5($reqPar));
        if ($sign == $_REQUEST['data']["sign"]) {
	   
            if ($_REQUEST["data"]["pay_status"] == "1") {
                echo "success";
                $data["out_trade_no"] = $returnArray['orderid'];
                return $data;
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
