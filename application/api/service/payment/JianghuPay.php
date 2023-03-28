<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/4
 * Time: 15:00
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JianghuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='105'){


        $data = [
            //'return_type'   =>  'PC',
            'uid'   =>  '250',
           'geway'   =>  $type,
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'asynch_url'   =>  $this->config['notify_url'],
            'success_url'   =>  $this->config['return_url'],
            'name'   =>  'test',
            'body'=>'test',
            'client_ip'   =>  '127.0.0.1',
            'order_pay'   =>  $order['trade_no'],
            'encry_type'   =>  'MD5',
        ];

        $merkey = '6wiin5jgw7iz6kcn6r2hd5vaw77zjlcv';
         $url = 'https://www.xjianghupay.com/paybank/cashier/payRequest';
        $data['sign'] = $this->getSign($merkey,$data);
//        $data['request_post_url']= $url;
//        return "http://caishen.sviptb.com/pay.php?".htmlspecialchars(http_build_query($data));
        $data['pay_type']=1;

        $result =  json_decode(self::curlPost($url,$data),true);
//var_dump( $result);die();
        if($result['errcode'] != '0' )
        {
            Log::error('Create XiongmaoV2Pay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create XiongmaoV2Pay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay']['codeUrl'];
    }


    /**
     * 查询接口
     */
        public function query($notifyData){
        $url = 'http://api.8798996.cn/index/getorder';

        $key = 'n6x1RXajkamHNuFSqY4XRaiOBd9PXzqK';
        $data=array(
            'out_trade_no'  =>  $notifyData['out_trade_no'],
            'appid' => '1061331',
        );
        $data['sign'] = $this->getSign($key,$data);

         $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query XiongmaoV2Pay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query XiongmaoV2Pay  API Error:'.$result['msg']);
            return false;
        }

        if(count($result['data']) < 1 ){
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
        $string_sign_temp = $string_a . $secret;
//echo $string_sign_temp;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtolower($sign);

        return $result;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
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
        Log::notice("FuwaPay notify data".json_encode($notifyData));
 //       if($notifyData['callbacks'] == "CODE_SUCCESS" ){
            if(1) {
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['order_pay'];
                return $data;
            }
   //     }
        echo "error";
        Log::error('FuwaPay API Error:'.json_encode($notifyData));
    }
}
