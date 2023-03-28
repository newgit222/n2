<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/18
 * Time: 14:53
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class QichengPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'WX-SM')
    {
        $p_data = array(
            'time' => time(),
            'mch_id' => 'XWWX002',
            'pay_code' => $type,
            'order_sn' => $order['trade_no'],
            'money' => sprintf("%.2f", $order["amount"]),//增加一定随机数金额
            'goods_desc' => 'goods',
            'notify_url' => $this->config['notify_url']
        );

        ksort($p_data);
        $key = 'ee040974a3e2853c45255688a2713c038181ab02';
        $sign = md5(http_build_query($p_data) . '&key=' . $key);
        $p_data['sign'] = $sign;
        $url = 'http://sf.ttzifu.cn/index.php?c=Pay';
        $p_data['request_post_url'] = $url;
         return "http://www.yingqianpay.com/pay.php?" . http_build_query($p_data);

    }


    /**
     * @param $params
     * weixin
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, 'WX-SM');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        Log::notice("QichengPay notify data1" . json_encode($notifyData));
        if ($notifyData['status'] == "success") {
            echo "success";
            $data['out_trade_no'] = $notifyData['out_order_sn'];
            return $data;
        }
        echo "error";
        Log::error('QichengPay API Error:' . json_encode($notifyData));
    }

}
