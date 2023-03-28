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

class HaitunPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='8'){



        $data = [
            'appid'    =>  '1000765',
            'trade_id'    =>  $order['trade_no'],
            'ip'    =>  get_userip(),
            'money'    =>  sprintf("%.2f",$order["amount"]),
            'type'    =>  $type,
            'notify_url'    =>  $this->config['notify_url'],
//            'return_url'    => $this->config['return_url'],
            'time'    =>  time(),
//            'content_type'    =>  'application/json',
//            'attach'    =>  '1'
        ];


        $merkey = 'a514ef813cbc13cb36bb66d0f0cfe7d0';
        $url = 'http://shht.haitpay.com//api/order/create_order';
        $data['sign'] = $this->getSign($merkey,$data);
//	var_dump(self::curlPost($url,$data,null,20));die();
        $result =  json_decode(self::curlPost($url,$data,null,20),true);
        if($result['code'] != '1' )
        {
            Log::error('Create WanlaPay API Error:'.$result['errMsg']);
            throw new OrderException([
                'msg'   => 'Create WanlaPay API Error:'.$result['errMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['result']['url'];
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {
	return md5($data['time'].'&'.$data['trade_id'].'&'.$secret);
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
    public function guma_bzk($params)
    {
        //获取预下单
        $url = $this->pay($params,'8');
        return [
            'request_url' => $url,
        ];
    }

  public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params,'8');
        return [
            'request_url' => $url,
        ];
    }
  public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'7');
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
        if($notifyData['status'] == "2" ){
            echo "OK";
            $data['out_trade_no'] = $notifyData['trade_id'];
            return $data;
        }
        echo "error";
        Log::error('WanlaPay API Error:'.json_encode($notifyData));
    }
}
