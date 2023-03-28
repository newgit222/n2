<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\model\OwnpayOrder;
use think\Log;

/*
 * OC支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class XuanwuPay extends ApiPayment
{

    /*
     *OC支付平台支付签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getSign($data)
    {
        ksort($data);
        $md5str = http_build_query($data);
        $md5str = urldecode($md5str)."9VhTML[C3g`J~.Xw@H6,U8!2^(B|=ipy";
        return strtolower(md5($md5str));
    }

    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order,$payType='ALIHB')
    {
        $unified["mch_id"] = 524;
        $unified["type"] = $payType;
        $unified["notify_url"] = $this->config['notify_url'];

        $unified["out_trade_no"] = $order['trade_no'];
        $unified["body"] =  "11";
        $unified["total_fee"] = sprintf("%.2f", $order['amount']);
        $unified["client_ip"] = "127.0.0.1";
        $unified["card_type"] = "0";
        $unified['sign'] = $this->getSign($unified);

        $json = json_encode($unified, 320);
        //发送请求到OC
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );

        $response = self::curlPost('http://xuanwuapi.zzdklpp.com/api/pay', $json,[CURLOPT_HTTPHEADER=>$headers]);
        $response = json_decode($response,true);
        if($response['error_code'] !=0)
        {
            Log::error('Create OC API Error:'.($response['error_msg'] ? $response['error_msg']:""));
            throw new OrderException([
                'msg'   => 'Create OC API Error:'.($response['error_msg'] ? $response['error_msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }

    /*
     * OC平台支付宝支付
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params,"PDDALIH5");
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['pay_url'],
        ];
    }

    /*
   * OC平台支付宝支付
   */
    public function wap_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params,"PDDALIH5");
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['pay_url'],
        ];
    }

    /*
  * OC平台支付宝支付
  */
    public function guma_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['pay_url'],
        ];
    }

    /*
   * OC平台支付宝支付
   */
    public function pdd_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }

    /*
     *
     * OC平台支付回调处理
     */
    public function notify()
    {
        Log::error('data from xuanwu' .file_get_contents('php://input'));
        $json = json_decode(file_get_contents('php://input'), true);

        $data["out_trade_no"] =  $json['out_trade_no'];
        return $data;
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
