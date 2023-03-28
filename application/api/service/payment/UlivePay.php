<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/30
 * Time: 15:52
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class UlivePay extends ApiPayment 
{

    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'https://api.uuulive.net/integration/interface/create_task_order';
        $merkey = '2b35d188-8163-45ad-a987-243a7035c156';
        $data = [
            'app_id'   =>  '54',
            'cash'   =>  sprintf("%.2f",$order["amount"]),
            'payment_type'   =>  $type,
            'customer_order_code'   =>  $order['trade_no'],
            'callback_url'   =>  $this->config['notify_url'],
            'user_id'   =>  1,
            'timestamp'   =>  time(),
        ];
        $data['data_sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
//        if($result['code'] != '0' )
  //      {
    //        Log::error('Create BufanPay API Error:'.$result['msg']);
      //      throw new OrderException([
        //        'msg'   => 'Create BufanPay API Error:'.$result['msg'],
          //      'errCode'   => 200009
            //]);
        //}
        return $result['data']['pay_address'];
    }



    public function query($notifyData){

        $url = 'http://user.ak43lm.cn/api/v1/getOrderByOutTradeNo';

        $merkey = '649ad527b06a4143b1e5b272f33bd453';
        $data=array(
            'outTradeNo'=>$notifyData['outTradeNo'],
            'uid'=>'484007266421309440',
            'timestamp'=>msectime(),
        );
        $data['sign'] = $this->getSign($data,$merkey);

        $result =  json_decode(self::curlPost($url,$data),true);
var_dump( $result);die();;
        Log::notice('query BufanPay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query BufanPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['paySucc'] != true ){
            return false;
        }
        return true;
    }




    private function getSign($data,$secret )
    {
//        $data['token']  = $secret;

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
  //      $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a.'key='.$secret);

        // 签名步骤四：所有字符转为大写
//        $result = strtoupper($sign);
        return $sign;
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
        $notifyData =$_POST;
        Log::notice("Ulive notify data".json_encode($notifyData));
$input = file_get_contents("php://input");
        Log::notice("Ulive notify data".$input);
        $notifyData = json_decode($input,true);

//        channel=alipay_qrcode_auto&tradeNo=456931005714923520&outTradeNo=115868704409556&money=200&realMoney=200&uid=456844661697282048&sign=CBE1BE600AFEEA6FB5DEA2CC9698F865
       // if(isset($notifyData['outTradeNo'])){
         //   if($this->query($notifyData)) {
                echo '{"data":{"success":true}}';
                $data['out_trade_no'] = $notifyData['customer_order_code'];
                return $data;
        ///    }
       // }
      //  echo "error";
    //    Log::error('BufanPay API Error:'.json_encode($notifyData));
    }
}
