<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 20:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class AcePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'wx03')
    {
        $data['mchId'] = '880156';
        $data['productId'] = $type;
        $data['mchOrderNo'] = $order['trade_no'];
        $data['amount'] = sprintf("%.2f", $order["amount"]) * 100;
        $data['clientIp'] = request()->ip();
        $data['callbackUrl'] = $this->config['notify_url'];
        $data['reqTime'] = time();
        $data['sign'] = $this->getSign($data);
        $url = 'https://api.acepay88.com/create';
        $response = self::curlPost($url, $data);
        $result = json_decode($response, true);
        if ($result['retCode'] != 1) {
            Log::error('Create AcePay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create AcePay API Error:' . $result['retMsg'],
                'errCode' => 200009
            ]);
        }
        return $result['payUrl'];
    }


    /**
     * @param $params
     * 微信
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

public function test($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    public function getSign($data, $secret = 'ca066c8fa4a9c3861e16ba9ea21a405e')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        $string_a = $string_a . "&key=" . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_a);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }


    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        Log::notice("AcePay notify data" . json_encode($_REQUEST));
        if ($_REQUEST['status'] == 'succ') {
            echo "success";
            $data['out_trade_no'] = $_REQUEST['mchOrderNo'];
            return $data;
        }
    }


}

