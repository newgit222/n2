<?php
/*
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 16:31
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class TeshilaPay extends ApiPayment
{
    /*
     * 统一下单
     */
    public function pay($order,$type='936'){

        $pay_memberid = '10415';   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $order['return_url'];  //页面跳转返回地址
        $Md5key = '1wdx0jp86h0qqptszwauj2992lfbvvj7';   //密钥
        $tjurl = 'https://pay.teslaweb.cc/Pay_Index.html';   //提交地址
        $pay_bankcode = $type;   //银行编码
        //扫码
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
        $native['pay_attach'] = "123";
        $native['pay_productname'] ='goods';

        Log::error('Create TeshilaPay Api data:'.json_encode($native,true));
        $result = self::curlPost($tjurl,$native);
        Log::error('Create TeshilaPay Return data:'.$result);
        $result =  json_decode($result,true);
        if($result['status'] != 1){
            var_dump($result);die;
        }
        return $result['payment_url'];
    }
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '940');
        return [
            'request_url' => $url,
        ];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '940');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData = $_POST;
        Log::notice("TeshilaPay notify data1".json_encode($notifyData));
        if(1 ){
            if(1){
                echo "OK";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        Log::error("TeshilaPay error data".json_encode($notifyData));

    }
}
