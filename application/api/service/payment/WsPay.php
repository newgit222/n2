<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *Ws extends支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class WsPay extends ApiPayment
{

    /*
     *获取签名
     * @param $data
     * @return string
     */
    public function getSign($native)
    {
        $Md5key = "ue07btx6puzph1yluxs2jak3o323o6uh";
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return  strtoupper(md5($md5str . "key=" . $Md5key));
    }



    /*
    *   统一下单
    *$pay_bankcode == 995  =>支付宝收款
    */
    private  function getPayUnifiedOrder($order, $pay_bankcode='995')
    {
        $pay_memberid = "200288643";   //商户后台API管理获取
        $pay_orderid = $order['trade_no'];
        $pay_amount= sprintf('%.2f',$order["amount"]);
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];
        $pay_callbackurl = $this->config['return_url'];
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        $sign = $this->getSign($native);
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "goods";
        $native['pay_productname'] =$order['subject'];
        $native['request_post_url'] ="http://47.75.51.252/Pay_Index.html";
        return "http://aa.sviptb.com/pay.php?".http_build_query($native);

    }



    public function h5_vx($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params,901);
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['qr_code'],
        ];
    }


    /*
     *H5支付宝
     * @param $params
     * @return array
     */
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



    /*
     * Ws平台支付回调处理
     */
    public function notify()
    {
        //$notifyData = file_get_contents('php://input');
        $notifyData =$_POST;
        Log::error('data from Ws' .json_encode($notifyData));
        echo  'OK';
        $data["out_trade_no"] =  $notifyData['orderid'];
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
