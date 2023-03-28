<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/25
 * Time: 23:12
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FanqiesuningV2Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){



        $apiurl = 'http://47.57.23.248:8001/api/order'; // API下单地址
        $signkey = '0d1446039256d9d496ae5d67ffada12f669ae6597397a3695f52d74b825948ea'; // 商户KEY  PDD平台获取
        $data = array(
            'type' => $type, // 通道代码 alipay/wechat
            'total' => sprintf("%.2f",$order["amount"]), // 金额 单位 元
            'api_order_sn' => $order['trade_no'], // 订单号
            'notify_url' => $this->config['notify_url'], // 异步回调地址
            'client_id' => '1fde927c4f2597338e81b8b51a261ee0',
            'timestamp' => $this->getMillisecond() // 获取13位时间戳
        );
        $data['sign'] = $this->sign($data,$signkey); // 生成签名

        $result =  json_decode(self::curlPost($apiurl,$data),true);
        if( !isset($result['code']) ||  $result['code'] != '200' )
        {
            Log::error('Create FanqiesuningV2Pay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create FanqiesuningV2Pay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['h5_url'];
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
        Log::notice("FanqiesuningV2Pay notify data".json_encode($notifyData));
//        {"callbacks":"CODE_SUCCESS","type":"alipay","total":"127.00","api_order_sn":"115841764063627","order_sn":"39442999911","sign":"9F1C2B508CCE8ABFC8925B97D03991BD"}
        if($notifyData['callbacks'] == "CODE_SUCCESS" ){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['api_order_sn'];
            return $data;
        }
        echo "ERROR";
        Log::error('FanqiesuningV2Pay API Error:'.json_encode($notifyData));
    }
}
