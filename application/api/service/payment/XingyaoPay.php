<?php 
/*reated by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/17
 * Time: 22:02
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XingyaoPay extends ApiPayment
{


    public function getSign($data, $secret = 'mfx5zOI4t3woohJM')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        $string_a = $string_a . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_a);
        // 签名步骤四：所有字符转为大写
        return $sign;
    }


    /**
     * 统一下单
     */
    private function pay($order, $type = 'alipay_qrcode')
    {
        $data = [
            'bid' => '1113',
            'money' => sprintf("%.2f", $order["amount"]),
            'order_sn' => $order['trade_no'],
            'notify_url' => $this->config['notify_url'],
            'pay_type' => $type,
        ];
        $key = 'mfx5zOI4t3woohJM';
        $data['sign'] = $this->getSign($data, $key);
        $url = 'https://pay.fast5566.com/api/index';
        $result = json_decode(self::curlPost($url, $data), true);
        if ($result['code'] != '100') {
            Log::error('Create FastPay API Error:' . $result['msg']);
            throw new OrderException([
                'msg' => 'Create FastPay API Error:' . $result['msg'],
                'errCode' => 200009
            ]);
        }
        return $result['data']['url'];
    }


    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, 'alipay_qrcode');
        return [
            'request_url' => $url,
        ];
    }
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, 'alipay_qrcode');
        return [
            'request_url' => $url,
        ];
    }
 public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'alipay_qrcode');
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
        Log::notice("FastPay notify data" . json_encode($_REQUEST));
        if ($_REQUEST['pay_state'] == 1) {
            echo "success";
            $data['out_trade_no'] = $_REQUEST['order_sn'];
            return $data;
        }
    }


}

