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
class OcPay extends ApiPayment
{

    /*
     *OC支付平台支付签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getOcSign($data,$mch_id, $Md5key)
    {
        return strtolower(md5($data . $mch_id . $Md5key));
    }


    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order,$payType='CloudUnionPay')
    {
        $this->config['mch_key'] = "629cd76cff544fd4991342118967cea1";
        $this->config['mch_id'] = "macauvenice";
        $unified = array(
            'MerchantOrderNo'   => $order['trade_no'],
            'MerchantUid'       => '',
            'MerchantPayType'   => $payType,
            'MerchantPayAmount' =>$order["amount"],
            'MerchantReturnUrl' =>$this->config['return_url'],
            'MerchantNotifyUrl' => $this->config['notify_url'],
            'Remark'            => $order['subject'],
        );
        $json = json_encode($unified, 320);
        $sign = self::getOcSign($json, $this->config['mch_id'] , $this->config['mch_key']);
        //发送请求到OC
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
            'Authorization:' . $this->config['mch_id'],
            'Sign:' . $sign
        );

        $response = self::curlPost('https://moa.ocpay.top/api/pay/v3/create', $json,[CURLOPT_HTTPHEADER=>$headers]);
        $response = json_decode($response,true);
        if($response['Code'] !=0)
        {
            Log::error('Create OC API Error:'.($response['Msg'] ? $response['Msg']:""));
            throw new OrderException([
                'msg'   => 'Create OC API Error:'.($response['Msg'] ? $response['Msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }

    /*
     * OC平台支付宝支付
     */
    public function ysf($params)
    {
        //获取预下单
        $unifiedOrder = self::getOcPayUnifiedOrder($params);
        $data['PcPayUrl'] = $unifiedOrder['Data']['PcPayUrl'];
        $data['MobilePayUrl'] = $unifiedOrder['Data']['MobilePayUrl'];
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['PcPayUrl'],
        ];
    }
	
   public function guma_zfb($params)
    {
        //�~N��~O~V��~D��~K�~M~U
        $unifiedOrder = self::getOcPayUnifiedOrder($params,'Alipay');
        $data['PcPayUrl'] = $unifiedOrder['Data']['PcPayUrl'];
        $data['MobilePayUrl'] = $unifiedOrder['Data']['MobilePayUrl'];
        //todo  �~P~N�~]�修�~T��~E�容�~I~@�~\~Ipay_code�~T���~X��~K��~U
        return [
            'request_url' =>  $data['PcPayUrl'],
        ];
    }

    /*
     *
     * OC平台支付回调处理
     */
    public function notify()
    {
        $this->config['mch_key'] = "629cd76cff544fd4991342118967cea1";
        $this->config['mch_id'] = "macauvenice";
        //验签
      //  $postSign= $_POST['sign'];
       // unset($_POST['sign']);
        Log::error('oc支付平台通知参数' .file_get_contents('php://input'). '超时处理');;
        $json = json_decode(file_get_contents('php://input'), true);
//
	$postSign = "cc";
        $calSign = self::getOcSign(file_get_contents('php://input'), $this->config['mch_id'] , $this->config['mch_key']);
        if($postSign == $calSign)
        {
            //签名校验通过
            Log::error('平台签名计算失败,但任然进行逻辑处理' . json_encode($_POST). '超时处理');
        }
	echo 'success';
        $data["out_trade_no"] =  $json['MerchantOrderNo'];
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
