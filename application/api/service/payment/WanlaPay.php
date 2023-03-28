<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/11
 * Time: 23:47
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WanlaPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){



        $data = [
            'mch_id'    =>  'kyt11ca8c0f991e20fb',
            'trade_id'    =>  $order['trade_no'],
            'ip'    =>  get_userip(),
            'fee'    =>  sprintf("%.2f",$order["amount"]),
            'pay_type'    =>  $type,
            'notify_url'    =>  $this->config['notify_url'],
//            'return_url'    => $this->config['return_url'],
//            'method'    =>  'post',
//            'content_type'    =>  'application/json',
//            'attach'    =>  '1'
        ];


        $merkey = '4ce9f28c3755023548f4bbb102dcdd3a';
        $url = 'https://api.quickpayeasy.com/order/unified';
        $data['sign'] = $this->getSign($merkey,$data);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['errCode'] != '0' )
        {
            Log::error('Create WanlaPay API Error:'.$result['errMsg']);
            throw new OrderException([
                'msg'   => 'Create WanlaPay API Error:'.$result['errMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {

        // 去空
//        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
//        $string_a = http_build_query($data);
//        $string_a = urldecode($string_a);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a,0,strlen($string_a) - 1);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp =   $secret.$string_a .$secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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

        $notifyData = $_GET;
        Log::notice("WanlaPay notify data".json_encode($notifyData));
        if($notifyData['result_code'] == "0" ){
            echo "ok";
            $data['out_trade_no'] = $notifyData['trade_id'];
            return $data;
        }
        echo "error";
        Log::error('WanlaPay API Error:'.json_encode($notifyData));
    }
}