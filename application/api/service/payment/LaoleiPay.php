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

class LaoleiPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='qrcode_alipay'){



        $data = [
            'app_key'    =>  '20201012135204LJQU7i',
            'out_trade_sn'    =>  $order['trade_no'],
     //       'ip'    =>  get_userip(),
            'money'    =>  sprintf("%.2f",$order["amount"]),
            'pay_type'    =>  $type,
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    => $this->config['return_url'],
            'timestamp'    =>  time(),
           'method_type'    =>  'json',
//            'attach'    =>  '1'
        ];


        $merkey = 'KaMWqLGMVqNC0U6o9v7PiIuE3xB4c3VGNoxjemmaZuXZjFX8';
        $url = 'https://pay.huayuanqixin.com/order/api/pay/'.$data['out_trade_sn'];
        $data['sign'] = $this->getSign($merkey,$data);
//	var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
//var_dump($result);die();
        if($result['code'] != '200' )
        {
            Log::error('Create WanlaPay API Error:'.$result['errMsg']);
            throw new OrderException([
                'msg'   => 'Create WanlaPay API Error:'.$result['errMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_data']['page_url'];
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {
	$str='app_key='.$data['app_key'].'&money='.$data['money'].'&notify_url='.$data['notify_url'].'&out_trade_sn='.$data['out_trade_sn'].'&timestamp='.$data['timestamp'].'&'.$secret;
	return strtoupper(md5($str));


        // 去空''
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
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
 public function h5_zfb($params)
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
        Log::notice("HaitunPay notify data".json_encode($notifyData));
        if($notifyData['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['out_trade_sn'];
            return $data;
        }
        echo "error";
        Log::error('WanlaPay API Error:'.json_encode($notifyData));
    }
}
