<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/22
 * Time: 19:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class Caijun567Pay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay1'){


        $url = 'http://caijun567.site/api/payment/create/web';

        $merkey = '2b8712dda375bb8b59404d022ce96d75';

        $data = [
            'code'  =>  '008476',
            'order_no'  =>  $order['trade_no'],
            'money'  =>  sprintf("%.2f",$order["amount"]),
            'type'  =>  $type,
            'notify_url'  =>  $this->config['notify_url'],
        ];

        $data['sign'] = $this->getSign($data,$merkey);


          $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create Caijun567Pay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create Caijun567Pay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }


    private function query($notifyData){
        $url = 'http://caijun567.site/api/payment/status';

        $merkey = '2b8712dda375bb8b59404d022ce96d75';

        $data = [
            'code' =>  '008476',
            'order_no' =>  $notifyData['order_no'],
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query Caijun567Pay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query Caijun567Pay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['received_flag'] != '1'   ){
           return false;
        }
        return true;
    }



    private function getSign($data,$secret )
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a.'api_token='.$secret);

        // 签名步骤四：所有字符转为大写
//        $result = strtoupper($sign);

        return $sign;
    }


    /**
 * @param $params
 * 支付宝
 */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay1');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'wechat1');
        return [
            'request_url' => $url,
        ];
    }



    /**
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

        $input = file_get_contents("php://input");
        Log::notice("Caijun567Pay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['result'] =='success'  ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('Caijun567Pay API Error:'.json_encode($notifyData));
    }

}