<?php
/**
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\api\service\payment;

use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\model\OwnpayOrder;
use think\Log;
class PaofendarenPay extends ApiPayment
{
    public function taobao_pc($params){
        //
       /* $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);

        $mid  = $params["uid"];

        $timeStart = time();
        $id = $Order->createOrder($amount,$params['trade_no'],$mid);
        $timeFinish = time();
        Log::notice($params['trade_no']."create order take:".($timeFinish-$timeStart)."s");
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPaylink($id),
        ];*/
        $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];
        $id = $Order->createOrder($amount, $params['trade_no'], $mid, \app\common\model\Shop::TYPE_PDD,OwnpayOrder::PAY_TYPE_ZFB);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPDDPayZfbLink($id),
        ];
    }

    public function taobao_wap($params){
        $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];
        $id = $Order->createOrder($amount, $params['trade_no'], $mid, \app\common\model\Shop::TYPE_PDD,OwnpayOrder::PAY_TYPE_ZFB);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPDDPayZfbLink($id),
        ];
        //
      /*  $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];
        $id = $Order->createOrder($amount, $params['trade_no'], $mid);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPaylink($id),
        ];*/
    }








    public function pdd_vx_back($params)
    {
        $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];

        $id = $Order->createOrder($amount, $params['trade_no'], $mid,  \app\common\model\Shop::TYPE_PDD,OwnpayOrder::PAY_TYPE_VX);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPDDPayVxLink($id),
        ];
    }

    public function pdd_vx_h5($params)
    {
        $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];

        $id = $Order->createOrder($amount, $params['trade_no'], $mid,  \app\common\model\Shop::TYPE_PDD,OwnpayOrder::PAY_TYPE_VX_H5);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPDDPayVxLink($id),
        ];
    }

    public function pdd_zfb($params)
    {
        $Order = new \app\ownpay\logic\Order();
        $amount = sprintf('%.2f', $params['amount']);
        $mid  = $params["uid"];
        $id = $Order->createOrder($amount, $params['trade_no'], $mid, \app\common\model\Shop::TYPE_PDD,OwnpayOrder::PAY_TYPE_ZFB);
        if(empty($id))
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $Order->getPDDPayZfbLink($id),
        ];
    }

//    public function notify(){
//        $data["out_trade_no"] =  $_POST['out_trade_no'];
//        return $data;
//    }




/**********************************begin the services***************************************************************/
    /*
     *淘宝支付签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getTaobaoSign($data, $Md5key)
    {
        ksort($data);

        $signData = "";
        foreach ($data as $key=>$value)
        {
            $signData = $signData.$key."=".$value;
            $signData = $signData . "&";
        }

        $signData = $signData."key=".$Md5key;
        return  md5($signData);
    }


     /*
     *  taobao _pay  统一下单
     *
     */
    private  function getTabaopayUnifiedOrder($order, $trade_type = 'vx')
    {
        $this->config['mch_key'] = "d3e5fa3f91f436d6495fb1eab061955a";
        $this->config['mch_id'] ="100076";

        $unified = array(
            'mchid' => $this->config['mch_id'],
            'out_trade_no' => $order['trade_no'],
            'amount' => $order["amount"],
            'channel' =>$trade_type,
            'notify_url' => $this->config['notify_url'],
            'return_url' =>$this->config['return_url'],
            'time_stamp' => date("Ymdhis"),
            'body' => $order['subject'],
        );
        $unified['sign'] = self::getTaobaoSign($unified, $this->config['mch_key']);
        $response = self::curlPost('http://www.dafeipay.com/api/pay/unifiedorder', $unified);
        $response = json_decode($response,true);
        if($response['code'] !=0)
        {
            Log::error('Create Taobao API Error:'.($response['msg'] ? $response['msg']:""));
            throw new OrderException([
                'msg'   => 'Create Taobao API Error:'.($response['msg'] ? $response['msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }


    /*
     * taobaopay  pdd_vx
     */
    public function guma_yhk($params)
    {
        //获取预下单
        $unifiedOrder = self::getTabaopayUnifiedOrder($params, 'guma_yhk');
        return [
            'request_url' => $unifiedOrder['data']['request_url'],
        ];
    }
 public function guma_bzk($params)
    {
        //获取预下单
        $unifiedOrder = self::getTabaopayUnifiedOrder($params, 'guma_bzk');
        return [
            'request_url' => $unifiedOrder['data']['request_url'],
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $unifiedOrder = self::getTabaopayUnifiedOrder($params, 'guma_zfb');
        return [
            'request_url' => $unifiedOrder['data']['request_url'],
        ];
    }

 public function test($params)
    {
        //获取预下单
        $unifiedOrder = self::getTabaopayUnifiedOrder($params, 'guma_bzk');
        return [
            'request_url' => $unifiedOrder['data']['request_url'],
        ];
    }

    /*
     *
     * 淘宝代付回调处理
     */
    public function notify()
    {
        $this->config['mch_key']  = "0dc752dc06e33ab87d0fe5130bbcd9e3";
        //验签
//        $postSign= $_POST['sign'];
//        unset($_POST['sign']);
//        $calSign = $this->getTaobaoSign($_POST,$this->config['mch_key']);
//        if($postSign != $calSign)
//        {
//            //签名校验通过
//            Log::error('平台签名计算失败,但任然进行逻辑处理' . json_encode($_POST). '超时处理');
//        }
        Log::error('回调日志' . json_encode($_POST));
        if($_POST['order_status'] == 1)
        {
              //第三方发送已支付成功通知
            echo "SUCCESS";
        }
         $data["out_trade_no"] =  $_POST['out_trade_no'];
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
