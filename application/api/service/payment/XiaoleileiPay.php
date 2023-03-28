<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/22
 * Time: 16:48
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiaoleileiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    public function pay($order, $type = '1003')
    {
        $pay_memberid = '210738780';   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f", $order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $md5key = 'b69k1tcn3qql6phwzhv5v4q4yulsle2c';
                 
        $tjurl= 'http://api.xl.a5188show.shop/Pay_Index.html';
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
        $Md5key = md5($native['pay_orderid'].'UV'.$md5key);
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_productdesc'] = "123";
        $native['pay_productname'] = 'goods';
        $native['pay_isJson'] = 1;
        Log::error('Create TongYiPay Return Data:'.json_encode($native,true));
	$result = self::curlPost($tjurl,$native,null,15);
        Log::error('Create TongYiPay Return Data:'.$result);
        $result =  json_decode($result,true);
        if($result['code'] != 200)
        {
            Log::error('Create TongYiPay API Error:'.$result['msg']);
            var_dump($result['msg']);die;
        }
        return  $result['url'];
        //  $native['request_post_url'] = $tjurl;
        //  return "http://www.yingqianpay.com/pay.php?" . http_build_query($native);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '933');
        return [
            'request_url' => $url,
        ];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '933');
        return [
            'request_url' => $url,
        ];
    }
    public function test1($params)
    {
        //获取预下单
        $url = $this->pay($params, '1011');
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
        $notifyData = $_REQUEST;
        Log::notice("TongYiPay notify data" . json_encode($notifyData));
        if ($notifyData['returncode'] == '00') {
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        Log::error("TongYiPay error data" . json_encode($notifyData));

    }
}
