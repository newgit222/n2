<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiaoxiongPay extends ApiPayment
{

    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $type="pay_any")
    {
        $param = [
            "appid" =>  "1043390",
            "amount"=> sprintf("%.2f", $order['amount']),
            "callback_url"=> $this->config['notify_url'],
            "out_trade_no"=> $order['trade_no'],
            "version"=> "v1.0",
            "pay_type"=> $type,
        ];
        ksort($param);
        $param['sign'] =  $this->sign($param);
        $apiUrl="https://api.lbpy.cc/index/unifiedorder?format=json";
        $response = self::curlPost($apiUrl, $param);
        $response = json_decode($response,true);
        if(empty($response['url']))
        {
            Log::error('Create XIAOXIONG API Error:');
            throw new OrderException([
                'msg'   => 'Create XIAOXIONG API Error:'.$response['msg'],
                'errCode'   => 200009
            ]);
        }
        return $response['url'];
    }

    protected function sign($param){
        $param = urldecode(http_build_query($param));
        $string_sign_temp = $param . "&key=" . "HIMTXNzZuuvJTnkHQSVDbiYm8wbVkF3T";
        return strtoupper(md5($string_sign_temp));  //strtoupper
    }

    public function guma_vx($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }


    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }

    /*
     * Dn平台支付回调处理
     */
    public function notify()
    {
        Log::error('Post data from xiaoxiong' . json_encode($_POST));
        //商户名称
        $appid  = $_POST['appid'];
        $callbacks  = $_POST['callbacks'];
        $pay_type  = $_POST['pay_type'];
        $success_url  = $_POST['success_url'];
        $error_url  = $_POST['error_url'];
        $out_trade_no  = $_POST['out_trade_no'];
        $amount  = $_POST['amount'];
        $sign  = $_POST['sign'];
        $callback_url = "http://www.zhongtongpay.com/api/notify/notify?channel=XiaoxiongPay";
        $data = [
            'appid'        => $appid,
            'callbacks'     => $callbacks,
            'pay_type' => $pay_type,
            'amount'       => $amount,
            'callback_url' => $callback_url,
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'out_trade_no'      => $out_trade_no,
            'sign'      => $sign,
        ];

        if ($this->verifySign($data,'HIMTXNzZuuvJTnkHQSVDbiYm8wbVkF3T')||1){
            echo "success";
            $r["out_trade_no"] = $out_trade_no;
            return $r;
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

    function verifySign($data, $secret) {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


    function getSign($secret, $data)
    {

        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }

}
