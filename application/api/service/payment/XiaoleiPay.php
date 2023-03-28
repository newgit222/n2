<?php


namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiaoleiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type)
    {
        $pay_memberid = "210738780";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f", $order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = md5($pay_orderid  .  "UVb69k1tcn3qql6phwzhv5v4q4yulsle2c");   //密钥
        $tjurl = "http://xiaolei8.a5188show.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $type;   //银行编码
        //扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
            "pay_amount" => $pay_amount,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_productname'] = 'goods';
        $native['pay_isJson'] = 1;
        $response = self::curlPost($tjurl, $native);
        $result = json_decode($response, true);
        if ($result['code'] != '200') {
            Log::error('Create XiaoleiPay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create XiaoleiPay API Error:' . $result['retMsg'],
                'errCode' => 200009
            ]);
        }
        return $result['url'];
    }

    /**
     * @param $params
     * 支付宝
     */
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, '930');
        return [
            'request_url' => $url,
        ];
    }
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '930');
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
        Log::notice("XiaoleiPay notify data" . json_encode($notifyData));
        if ($notifyData['returncode'] == '00') {
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        Log::error("XiaoleiPay error data" . json_encode($notifyData));

    }
}