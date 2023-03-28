<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *DC支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class DcPay extends ApiPayment
{

    /*
     *获取签名
     * @param $data
     * @return string
     */
    public function getSign($native)
    {
        $Md5key = "uozmvwddtncijuxfaw82f6skutn87n5n";
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return  strtoupper(md5($md5str . "key=" . $Md5key));
    }



    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $pay_bankcode='903')
    {
        $pay_memberid = "10292";   //商户后台API管理获取
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
        $native['request_post_url'] ="http://www.l2ez.cn/Pay_Index.html";
        return "http://aa.sviptb.com/pay.php?".http_build_query($native);

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
        dd($url);
        return [
            'request_url' => $url,
        ];
    }


    /*
     * WQ平台支付回调处理
     */
    public function notify()
    {

        $notifyData = file_get_contents('php://input');
        file_get_contents('./test.log',json_encode($notifyData),FILE_APPEND);
        $sign=$notifyData['sign'];
        unset($notifyData['sign']);
        $mysign = $this->getSign($notifyData);
        if($mysign == $sign && $notifyData['status']==1){
            //处理业务逻辑
            echo 'success';
            $data["out_trade_no"] = $notifyData['orderNo'];
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
