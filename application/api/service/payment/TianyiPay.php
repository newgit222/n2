<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/22
 * Time: 19:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class TianyiPay extends ApiPayment
{

    /**
     * 统一下e
     */
    private function pay($order,$type='D0_WX_H5'){


        $url = 'https://api.tianypay.com/cashier/scanAPI';
        $merkey = '7128076462d04c5db5e38dd8d260658b';

        $data = [
            'merchantCode' =>  'ty120073111445831800',
            'commodityName' =>  'a2cc53dd',
            'payCode' =>  $type,
            'orderNumber' =>  $order['trade_no'],
            'submitIp' =>  '127.0.0.1',
            'amount' =>  $order["amount"],
            'syncRedirectUrl' =>  $this->config['notify_url'],
	    'asyncNotifyUrl' =>  $this->config['notify_url'],
            'remark' =>  'goods',
            'submitTime' =>  date('YmdHis',time()),
        ];
        $data['sign'] = $this->getSign($data,$merkey);

$data = self::curlPost($url,$data);

$result =  json_decode($data,true);
        if($result['returnCode'] != '0' )
        {
            Log::error('Create tianyiPay API Error:'.$data);
            throw new OrderException([
                'msg'   => 'Creat tianyi  API Error:'.$data,
                'errCode'   => 200009
            ]);
        }
        return $result['content'];

 //$data['request_post_url'] = $url;
   //     return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);



}


    private function getSign($data,$secret )
    {

        //签名步骤一：按字典序排序参数
//	var_dump($data);
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
  $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a.$secret);
//	var_dump($string_a.'&payKey='.$secret);die();
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_vx($params)
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
        Log::notice("tianyi notify data1".json_encode($notifyData));

        if($notifyData['payStatus'] =='orderPaid' ){
//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderNumber'];
                return $data;
  //          }
        }
        echo "error";
        Log::error('MxPay API Error:'.json_encode($notifyData));
    }


}
