<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 15:18
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FuqiqiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='bank'){


        $data = [
            'return_type'   =>  'PC',
            'appid'   =>  '1072035',
            'pay_type'   =>  $type,
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'callback_url'   =>  $this->config['notify_url'],
            'success_url'   =>  $this->config['return_url'],
            'error_url'   =>  'http://www.baidu.com',
            'out_uid'   =>  '1',
            'out_trade_no'   =>  $order['trade_no'],
            'version'   =>  'v1.1',
        ];

        $merkey = '99970620569452668042409736491226';
        $url = 'https://api.taobao-assets.com/index/unifiedorder?format=json';
        $data['sign'] = $this->getSign($merkey,$data);
//        $data['request_post_url']= $url;
//        return "http://caishen.sviptb.com/pay.php?".htmlspecialchars(http_build_query($data));
//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create FUQIQIPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create FUQIQIPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['url'];
    }




    public function query($notifyData){
return true;
        $url = 'http://apipay.52jpp.com/index/getorder';

        $key = 'ytQtOg5rW1VG8aJPAkFkE0ZsBy013ADC';
        $data=array(
            'appid'=>'1053363',
            'out_trade_no'=>$notifyData['out_trade_no']
        );
        $data['sign'] = $this->getSign($key,$data);

        $result =  json_decode(self::curlPost($url,$data),true);
var_dump($result);die();
        Log::notice('query PixiuV2Pay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query PixiuV2Pay  API Error:'.$result['msg']);
            return false;
        }
        if(count($result['data']) <1 ){
            return false;
        }
        if($result['data'][0]['status'] != '4' ){
            return false;
        }
        return true;
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
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

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
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        Log::notice("PixiuV2Pay notify data".json_encode($notifyData));
        if($notifyData['callbacks'] == "CODE_SUCCESS" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('PixiuV2Pay API Error:'.json_encode($notifyData));
    }
}
