<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\model\OwnpayOrder;
use think\Log;

/*
 * ARYA支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class AryaPay extends ApiPayment
{

    /*
     签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getSign($data)
    {
        $md5str = '';
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

       $md5str=rtrim($md5str, "&");
//echo $md5str.'67637d3570d94cfeb99ca70bd5e3cb8';;
       return md5($md5str.'67637d3570d94cfeb99ca70bd5e3cb8d');

    }





    /*
    * 统一下单
    *
    */
    private  function getAryaPayUnifiedOrder($order,$payType='tbhb')
    {
        $unified['version'] = '3.0';
        $unified['method'] = 'Gt.online.interface';
        $unified['partner'] = '556237592954142720';
        $unified['banktype'] = $payType;
        $unified['paymoney'] = sprintf('%.2f',$order["amount"]);
        $unified['ordernumber'] =$order['trade_no'];
        $unified['callbackurl'] = $this->config['notify_url'];
        $unified['sign'] = $this->getSign($unified);
        $unified['hrefbackurl']="http://www.baidu.com";
        $unified['notreturnpage'] = true;
//var_dump($unified);die();
        $response = self::curlPost('http://47.57.235.244/api/v1/getway',$unified);
        $response = json_decode($response,true);

        if($response['code'] !=0)
        {
            Log::error('Create ARYA API Error:'.($response['msg'] ? $response['msg']:""));
            throw new OrderException([
                'msg'   => 'Create ARYA API Error:'.($response['msg'] ? $response['msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }


    public function  test($params)
    {
        $data = self::getAryaPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['data']['payUrl'],
        ];
    }



    /*
     * OC平台支付宝支付
     */
    public function h5_zfb($params)
    {

        //获取预下单
        $data = self::getAryaPayUnifiedOrder($params);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['data']['payUrl'],
        ];
    }

	/*
     * OC平�~O��~T���~X��~]�~T���~X
     */
    public function wap_zfb($params)
    {

        //�~N��~O~V��~D��~K�~M~U
        $data = self::getAryaPayUnifiedOrder($params);
        //todo  �~P~N�~]�修�~T��~E�容�~I~@�~\~Ipay_code�~T���~X��~K��~U
        return [
            'request_url' =>  $data['data']['payUrl'],
        ];
    }

    /*
     *
     * ARYA平台支付回调处理
     */
    public function notify()
    {
        Log::error('data from arya' .json_encode($_GET));
        $data["out_trade_no"] =  $_GET['ordernumber'];
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
