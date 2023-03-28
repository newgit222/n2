<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/19
 * Time: 20:15
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class Chanv3Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='YinLian'){


        $data = [
            'mch_uid'   =>  '17',
            //'appid'   =>  '1053363',
            'pay_type'   =>  $type,
            'total_fee'   =>  sprintf("%.2f",$order["amount"]),
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
           // 'error_url'   =>  'http://www.baidu.com',
            'order_name'   =>  '1',
            'out_trade_no'   =>  $order['trade_no'],
            'order_remark'   =>  'v11',
        ];

        $merkey = '831a6c6bc6053184fa008c657d26682d';
        $url = 'http://www.pdbpay.xyz/api.do';
        $data['sign'] = $this->getSign($merkey,$data);
//        $data['request_post_url']= $url;
//        return "http://caishen.sviptb.com/pay.php?".htmlspecialchars(http_build_query($data));

//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data,null,20),true);
        if($result['code'] != '200' )
        {
            Log::error('Create PixiuPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create PixiuPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
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
//var_dump((self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);

        Log::notice('query PixiuPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query PixiuPay  API Error:'.$result['msg']);
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
$str='mch_uid='.$data['mch_uid'].'&pay_type='.$data['pay_type'].'&out_trade_no='.$data['out_trade_no'].'&total_fee='.$data['total_fee'].'&order_name='.$data['order_name'].'&order_remark='.$data['order_remark'].'&notify_url='.$data['notify_url'].'&return_url='.$data['return_url'].'&'.$secret;
//echo $str;die();
return md5($str);
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
        Log::notice("PixiuPay notify data".json_encode($notifyData));
        if($notifyData['status'] == "success" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('PixiuPay API Error:'.json_encode($notifyData));
    }
}
