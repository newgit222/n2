<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/1
 * Time: 19:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class DouluoPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1901'){


        $url = 'http://45.195.53.178/douluo/api/pay/create_order';
        $merkey = '1E51ED98D57741FA9295B92ECCCF81A6';

        $data = [
            'mchId' =>  '201453',
            'timestamp' => time()*1000,
            'projectId' =>  $type,
            'mchOrderNo' =>  $order['trade_no'],
            'signType' =>  'MD5',
            'amount' =>  sprintf("%.2f",$order["amount"])*100,
            'notifyUrl' =>  $this->config['notify_url'],
            'subject' =>  'goods',
//            'body' =>  'goods',
//            'extra' =>  '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data,null,15),true);
//var_dump($result);die();
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create FeilongPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create FeilongPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data'];
    }


    private function query($notifyData){
        $url = 'http://45.195.53.178/douluo/api/pay/query_order';

        $merkey = '1E51ED98D57741FA9295B92ECCCF81A6';

        $data = [
            'mchId' =>  '201453',
            'reqId' =>  md5($notifyData['mchOrderNo']),
		'signType'=> 'MD5',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);


        Log::error('query FeilongPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query FeilongPay  API Error:'.$result['retMsg']);
            return false;
        }
        if($result['data']['status'] != '1'   ){
   //         if($result['status'] != '3') {
                return false;
     //       }
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
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        Log::notice("FeilongPay notify data1".json_encode($notifyData));
        if($notifyData['status'] =='1'  ){
            if($this->query($notifyData)) {
//		if(1){
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('FeilongPay API Error:'.json_encode($notifyData));
    }
}
