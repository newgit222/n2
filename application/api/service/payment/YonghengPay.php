<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/2
 * Time: 20:11
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YonghengPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = '907')
    {


        $url = 'http://43.129.184.167:3020/api/pay/create_order';
        $merkey = '6Y8EQXFRKESY8MYISX8ZJRZNIGWXNRGYV8DRNK8RMBTQ1VFGQ8V9IMXZWXFC8WONYAZOIOQ1EHDHWQLHRYNUKHXNXSZRCQXLQMSP8CMVSRTPBOHZ4R7ZPTRGEJ2HKJHQ';

        $data = [
            'mchId' => '20000008',
            'appId' => '0470d25ab00147a2a6202371bca3f652',
            'productId' => $type,
            'mchOrderNo' => $order['trade_no'],
            'currency' => 'cny',
            'amount' => sprintf("%.2f", $order["amount"]) * 100,
            'notifyUrl' => 'http://www.zhongtongzhifu.com/api/notify/notify/channel/YonghengPay',
            'subject' => 'goods',
            'body' => 'goods',
            'extra' => '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data, $merkey);
        $result = json_decode(self::curlPost($url, $data), true);
        if ($result['retCode'] != 'SUCCESS') {
            Log::error('Create YonghengPay API Error:' . $result['retMsg']);
            throw new OrderException([
                'msg' => 'Create YonghengPay API Error:' . $result['retMsg'],
                'errCode' => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function getSign($data, $secret)
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a . 'key=' . $secret);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, '907');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @return array
     *  test
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,907);
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
        Log::notice("YonghengPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == '2' || $notifyData['status'] == '3') {
            echo "success";
            $data['out_trade_no'] = $notifyData['mchOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('YonghengPay API Error:' . json_encode($notifyData));
    }
}
