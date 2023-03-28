<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/27
 * Time: 0:52
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class LqPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='69'){

        $url = 'http://154.197.26.176:8092/backend/order/optimalPay';

        $mch_key = 'ecb4c7d71f127a3a16ecf05aab9e0420';
     $data = [
            'merchantNum'    =>  'DCMR1600578170560',
            'payWayId'    =>  $type,
            'remark'    =>  'goods',
            'payWay'    =>  'ali',
            'merchantOrderNo'    =>  $order['trade_no'],
            'money'    =>  sprintf("%.2f",$order["amount"]),
            'ip'    =>  '171.208.0.0',
            'return_url'    =>  $this->config['notify_url'],
        ];
       $data['sign'] = $this->sign($data,$mch_key);
        $result =  json_decode(self::curlPost($url,($data)),true);
        if($result['code'] != '200' )
        {
            Log::error('Create SitongPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create LqPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['url'];

    }


   private function curl_post($url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
//    var_dump($headers);
        curl_close($ch);
        return $data;
    }



    /**
     * 签名验证-平台
     * $datas 数据数组
     * $key 密钥
     */
    private function sign ($datas = [], $key = "")
    {
        $sign = md5($datas['merchantNum'].$datas['money'].$key);
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

        $notifyData = $_POST;
  $input = file_get_contents("php://input");
        Log::notice("SitongPay notify data".$input);
        $notifyData = json_decode($input,true);

//        $notifyData =$_POST;
        Log::notice("LqPay notify data1".json_encode($notifyData));
                if ($notifyData['status']=='SuccessPay') {
                    echo "success";
                    $data['out_trade_no'] = $notifyData['merchantOrderNo'];
                    return $data;
                }
        echo "error";
        Log::error('SitongPay API Error:'.json_encode($notifyData));
    }
}
