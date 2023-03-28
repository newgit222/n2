<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/14
 * Time: 16:38
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FanqiesuningPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){



        $apiurl = 'http://suni.duodianshop.net/index/api/order'; // API下单地址
        $signkey = '711376d16c1b6ecf368f2a2bd10546c016b415c8e4054a6bcba89115294830b1'; // 商户KEY  PDD平台获取
        $data = array(
            'type' => $type, // 通道代码 alipay/wechat
            'total' => sprintf("%.2f",$order["amount"]), // 金额 单位 元
            'api_order_sn' => $order['trade_no'], // 订单号
            'notify_url' => $this->config['notify_url'], // 异步回调地址
            'client_id' => '46526b706c9245eb67b16ca9a0969c27',
            'timestamp' => $this->getMillisecond() // 获取13位时间戳
        );
        $data['sign'] = $this->sign($data,$signkey); // 生成签名

        $result =  json_decode(self::curlPost($apiurl,$data),true);
        if( !isset($result['status']) ||  $result['status'] != '1' )
        {
            Log::error('Create FanqiesuningPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create FanqiesuningPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['qr_url'];
    }


    /**
     * 签名
     * @param array $params
     * @param string $secret
     * @return string
     */
    function sign($params = [], $secret = '')
    {
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {

            $str = $str . $k . $v;
        }
        $str = $secret . $str . $secret;
        return strtoupper(md5($str));
    }


    /**
     * 返回13位时间戳
     */
    function getMillisecond() {

        list($t1, $t2) = explode(' ', microtime());

        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);

    }


    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
        return [
            'request_url' => $url,
        ];
    }



    /**
     * @param $params
     * 支付宝
     */
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'wechat');
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
        Log::notice("FanqiesuningPay notify data".json_encode($notifyData));
//        {"callbacks":"CODE_SUCCESS","type":"alipay","total":"127.00","api_order_sn":"115841764063627","order_sn":"39442999911","sign":"9F1C2B508CCE8ABFC8925B97D03991BD"}
        if($notifyData['callbacks'] == "CODE_SUCCESS" ){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['api_order_sn'];
            return $data;
        }
        echo "ERROR";
        Log::error('FanqiesuningPay API Error:'.json_encode($notifyData));
    }
}