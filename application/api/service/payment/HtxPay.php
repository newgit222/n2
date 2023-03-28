<?php
 namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * 和天下支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class HtxPay extends ApiPayment
{


    /*
    *  HtxPay _pay  统一下单
    *
    */
    private  function getOcPayUnifiedOrder($order, $type = 'alipay')
    {

        $api = 'http://pay.senpay.vip:8081/gate/channel/pay';
        $appid = 'HCTX-010';
        $app_key = '3C27443FCBE394931411A8439A211135';
        $out_trade_no = $order['trade_no'];
        $pay_type = $type;
        $amount = sprintf("%.2f", $order['amount']);
        $notify_url = $this->config['notify_url'];
        $return_url = $this->config['return_url'];
        $data = [
            'tenantNo'        => $appid,
            'tenantOrderNo' => $out_trade_no,
            'notifyUrl' => $notify_url,
            'pageUrl' => $return_url,
            'amount'       => $amount,
            'payType'     => $pay_type,
            'remark' =>'goods'
        ];

//拿APPKEY与请求参数进行签名
        $sign = $this->getSign($app_key, $data);
        $data['sign'] = $sign;
        $response = self::curlPost($api, $data);
//var_dump($response);die();
        $response = json_decode($response,true);
        if($response['status'] != 200)
          {
              Log::error('Create HtxPay API Error:'.($response['msg'] ? $response['msg']:""));
              throw new OrderException([
                  'msg'   => 'Create HtxPay API Error:'.($response['msg'] ? $response['msg']:""),
                  'errCode'   => 200009
              ]);
        }
        return $response;
    }

    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
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

    /*
    * 支付宝支付
    */
    public function test($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $data['url'],
        ];
    }
public function wap_zfb($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params);
        return [
            'request_url' =>  $data['url'],
        ];
    }

    /*
  * vx
  */
    public function h5_vx($params)
    {
        //获取预下单
        $data = self::getOcPayUnifiedOrder($params, "wechat.h5");
        return [
            'request_url' =>  $data['data']['url'],
        ];
    }

    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("HtxPay notify data1".json_encode($notifyData));
    //    if($notifyData['status'] == "200" ){
 //           if($this->query($notifyData)){
                echo "success";
                $data['out_trade_no'] = $notifyData['tenantOrderNo'];
                return $data;
   //         }
      //  }
        echo "error";
        Log::error('HtxPay API Error:'.json_encode($notifyData));
    }

    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
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

