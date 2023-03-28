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

class NiuNiuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'wxqr')
    {
        $data['mch_id'] = 'M90VKD62O';
        $data['service'] = $type;
        $data['out_trade_no'] = $order['trade_no'];
        $data['total_fee'] = sprintf("%.2f", $order["amount"]) * 100;
        $data['body'] = 'goods';
        $data['client_ip'] = request()->ip();
        $data['notify_url'] = $this->config['return_url'];
        $data['return_url'] = $this->config['notify_url'];
        $data['sign'] = $this->getSign($data);
        $url = 'https://api.98niuniupay.com/gateway/payment';
        $response = self::curlPost($url, $data);
        $result = json_decode($response, true);
        if ($result['code'] != 'success') {
            Log::error('Create NiuNiuPay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create NiuNiuPay API Error:' . $result['rspmsg'],
                'errCode' => 200009
            ]);
        }
        return $result['code_url'];
    }


    /**
     * @param $params
     * 微信
     */
    public function wxh5($params)
    {
        //获取预下单
        $url = $this->pay($params, 'wxqr');
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

    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    public function getSign($obj, $key = '248e343c2bd44f21ae3db835bd0d703e')
    {
        $sign_content = "";
        ksort($obj);
        foreach ($obj as $k => $v) {
            if ($v !== '' && !in_array($k, ['sign', 'code', 'msg'])) {
                $sign_content .= $k . '=' . $v . '&';
            }
        }
        $sign_content .= 'key=' . $key;
        $sign = md5($sign_content);
        return $sign;
    }

    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $input = file_get_contents("php://input");
        Log::notice("NiuNiuPay notify data" . $input);
        $data = json_decode($input, true);
        if ($data['status'] == '1') {
            echo "success";
            $data['out_trade_no'] = $_REQUEST['out_trade_no'];
            return $data;
        }
        echo "fail";
    }


}

