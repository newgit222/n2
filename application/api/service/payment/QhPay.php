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
class QhPay extends ApiPayment
{


    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order,$pay_code='915')
    {

        $pay_memberid = '1940135147';
        $pay_amount  = sprintf("%.2f", $order['amount']);    //交易金额
        $pay_applydate = date('Y-m-d H:i:s',time());  //订单时间
        $pay_orderid =  $order['trade_no'] ;    //订单号
        $pay_notifyurl =  $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl =  "http://www.baidu.com";  //页面跳转返回地址
        $Md5key = '2997a02e38ab092e7c36e8fb8eb440b6';   //密钥
        $apiUrl = "http://47.52.225.167/pay";   //网关提交地址

        $data = array(
            "merchant"      => $pay_memberid,
            "amount"        => $pay_amount,
            "pay_code"      => $pay_code,
            "order_no"      => $pay_orderid,
            "notify_url"    => $pay_notifyurl,
            "return_url"    => $pay_callbackurl,
            "order_time"    => $pay_applydate,
            "attach"        => 'a',
            "cuid"          => '1',
            'json'          => 'json'
        );

        $sign = $this->getSign($data,$Md5key);
        $data["sign"] = $sign;
       // $jsapi["json"] = "json";
        $response = self::curlPost($apiUrl, $data);
        $response = json_decode($response,true);

        if(empty($response['QRCodeLink']))
        {
            Log::error('Create QH API Error:');
            throw new OrderException([
                'msg'   => 'Create QH API Error:',
                'errCode'   => 200009
            ]);
        }
        return $response;
    }

    public function guma_zfb($params)
    {
        //获取预下单
        $data = self::getPayUnifiedOrder($params);

        return [
            'request_url' =>  urldecode($data['QRCodeLink']),
        ];
    }

    public function guma_vx($params)
    {
        //获取预下单
        $data = self::getPayUnifiedOrder($params);

        return [
            'request_url' =>  urldecode($data['QRCodeLink']),
        ];
    }

    /*
     * OC平台支付回调处理
     */
    public function notify()
    {
        Log::error('Post data from QH' . json_encode($_POST));
        $Md5key = '2997a02e38ab092e7c36e8fb8eb440b6';   //密钥
        $data = array(
            "merchant"      => $_POST['merchant'],
            "amount"        => $_POST['amount'],
            "sys_order_no"  => $_POST['sys_order_no'],
            "out_order_no"  => $_POST['out_order_no'],
            "order_time"    => $_POST['order_time'],
            "attach"        => $_POST['attach'],
            "cuid"          => $_POST['cuid'],
            "realPrice"     => $_POST['realPrice'],
        );

        $sign          =  $_POST['sign'];
        $local_sign = $this->getSign($data,$Md5key);
        if($sign == $local_sign){
            echo "success";
            $data["out_trade_no"] =  $_POST['out_order_no'];
            return $data;
        }
        throw new OrderException([
            'msg'   => 'Create QH API Error:',
            'errCode'   => 200009
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





    /**
     * 数据验签
     * @param array $data
     * @param string $key
     * @return string
     */
    public function getSign($data,$key)
    {
        $para_filter = $this->paraFilter($data);
        $para_sort   = $this->argSort($para_filter);
        $prestr      = $this->createLinkString($para_sort);

        return $this->md5Encrypt($prestr, $key);
    }
    /**
     * 除去数组中值为空参数
     * @param array $data
     * @return array
     */
    public function paraFilter($data)
    {
        $para_filter = array();
        foreach ($data as $key=>$val)
        {
            if($key == "sign" || $val == '' || $key == "json")continue;
            else $para_filter[$key] = $data[$key];
        }
        return $para_filter;
    }

    /**
     * 对待签名参数数组排序
     * @param array $para
     * @return array
     */
    public function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;

    }

    /**
     *把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para
     * @return bool|string
     */
    public function createLinkString($para) {
        $arg  = "";
        foreach ($para as $key=>$val)
        {
            $arg.=$key."=".$val."&";
        }

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * MD5加密
     * @param $prestr
     * @param $key
     * @return bool
     */
    public function md5Encrypt($prestr, $key) {
        $prestr = $prestr . 'key='.$key;
        return md5($prestr);
    }

    /**
     * 验签
     * @param $mysgin
     * @param $sign
     * @return bool
     */
    public function isSign($mysgin, $sign){
        if($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }







}
