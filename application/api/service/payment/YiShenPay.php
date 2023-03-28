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

class YiShenPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'AlipayH5_LC')
    {
        $data['sid'] = '90047';
        $data['paytype'] = $type;
        $data['order'] = $order['trade_no'];
        $data['notify_url'] = $this->config['return_url'];
        $data['return_url'] = $this->config['notify_url'];
        $data['name'] = 'goods';
        $data['money'] = sprintf("%.2f", $order["amount"]);
        $data['date'] = time();
        $data['sign'] = $this->getSign($data);
        $url = 'http://154.209.236.28/api';
        $response = self::curlPost($url, $data);
        $result = json_decode($response, true);
        if ($result['code'] != '1') {
            Log::error('Create YiShenPay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create YiShenPay API Error:' . $result['rspmsg'],
                'errCode' => 200009
            ]);
        }
        return $result['payurl'];
    }


    /**
     * @param $params
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'ttwechat57');
        return [
            'request_url' => $url,
        ];
    }
    
public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'ttwechat57');
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
    public function getSign($data, $secret = 'ECE1328F6746F28195E379A3FC582644')
    {

        $str = "miyao={$secret}&money={$data['money']}&order={$data['order']}&sid={$data['sid']}";
        $sign = md5($str);
        return $sign;
    }


    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $input = file_get_contents("php://input");
        Log::notice("YiShenPay notify data" . $input);
        $data = json_decode($input, true);
        if (1) {
            echo "success";
            $data['out_trade_no'] = $data['out_order'];
            return $data;
        }
    }


}

