<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/21
 * Time: 13:57
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class QianguiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='112'){
        $data['notify_url'] = $this->config['notify_url'];
        $data['return_url'] = $this->config['return_url'];
        $data['ordersn'] = $order['trade_no'];
        $data['subject'] = 'goods';
        $data['total_fee'] = sprintf("%.2f",$order["amount"]);
        $data['client_ip'] = get_userip();
        $data['mer_no'] = '937595273';
        $data['mode_type'] = $type;
        $merkey = '1CtyXqye2bC22Iisasvt2sA9TO1w5Hxx';
        $url = 'https://api.yzjsd-jx.com/api/paycreate';
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $pay_data .= '&key='.  $merkey;
        $sign = md5($pay_data);
        $data['sign'] = $sign;
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '10000' )
        {
            Log::error('Create QianguiPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create QianguiPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['cont']['url'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'112');
        return [
            'request_url' => $url,
        ];
    }
    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
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
        Log::notice("qiangui notify data".json_encode($notifyData));
//        {"mer_no":"937595273","mode_type":"112","total_fee":"0.01","pay_money":"0.01","ordersn":"115822672893472","other":"","order_code":"165912750643025221","pay_time":"1582267331","sign":"7cd105bdd1ceceb9bf99b43dd6d97a13"}
        echo "SUCCESS";
        $data['out_trade_no'] = $notifyData['ordersn'];
        return $data;

    }

}