<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 14:34
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class BoxPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='8028'){


        $url = 'http://pay.uinla.com/api/pay/create_order';
        $merkey = 'NSWZPBWHBH9MQKNEBN3Y6QEXFYC70UA02V4BJWSMPOUP9WTZNGEGAPNNVA6DAGCIKP68SNW0LUDWSUSEX847OTFAGZTIKVFYALBVAH87J8DYOCXSM6LJUCASUPUOB5P6';

        $data = [
            'mchId' =>  '20000021',
            'appId' =>  '9330bba8258644c8bf7cf1b3d8d73ebd',
            'productId' =>  $type,
            'mchOrderNo' =>  $order['trade_no'],
            'currency' =>  'cny',
            'amount' =>  sprintf("%.2f",$order["amount"])*100,
            'notifyUrl' =>  $this->config['notify_url'],
            'subject' =>  'goods',
            'body' =>  'goods',
            'extra' =>  '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data,null,20),true);
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create BoxPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create HuaxinPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function query($notifyData){
        $url = 'http://pay.uinla.com/api/pay/query_order';

        $merkey = 'NSWZPBWHBH9MQKNEBN3Y6QEXFYC70UA02V4BJWSMPOUP9WTZNGEGAPNNVA6DAGCIKP68SNW0LUDWSUSEX847OTFAGZTIKVFYALBVAH87J8DYOCXSM6LJUCASUPUOB5P6';

        $data = [
            'mchId' =>  '20000021',
            'appId' =>  '9330bba8258644c8bf7cf1b3d8d73ebd',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query HuaxinPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query HuaxinPay  API Error:'.$result['retMsg']);
            return false;
        }
        if($result['status'] != '2'   ){
            if($result['status'] != '3') {
                return false;
            }
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
        $sign = md5($string_a.'key='.$secret);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function small_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
//        $url = $this->query(['mchOrderNo'=>'115869417346689']);
//        var_dump($url);die();
        return [
            'request_url' => $url,
        ];
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

    /**
     * @param $params
     * 微信
     */
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'8003');
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
        $notifyData =$_POST;
        Log::notice("HuaxinPay notify data1".json_encode($notifyData));
        if(1 ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('HuaxinPay API Error:'.json_encode($notifyData));
    }

}
