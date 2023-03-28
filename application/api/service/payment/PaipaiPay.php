<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/14
 * Time: 20:15
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class PaipaiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='6002'){
        $data['notifyUrl'] = $this->config['notify_url'];
        $data['orderNo'] = $order['trade_no'];
    //    $data['body'] = 'goods';
        $data['tradeAmt'] = sprintf("%.2f",$order["amount"]);
        $data['payerID'] =md5($order['trade_no']);
        $data['merchantID'] = '2198';
        $data['bankCode'] = $type;
$data['returnUrl'] = 'http://www.baidu.com';
//$data['bankCode']='ICBC';
$data['payerIP']='127.0.0.1';
        $merkey = 'de936461fc1c4fe389d5f17c65bda735';
        $url = 'https://api.ppzf.net/heepay.aspx';
$str= 'merchantID='.$data['merchantID'].'&bankCode='.$data['bankCode'].'&tradeAmt='.$data['tradeAmt'].'&orderNo='.$data['orderNo'].'&notifyUrl='.$data['notifyUrl'].'&returnUrl='.$data['returnUrl'].'&payerIP='.$data['payerIP'].$merkey;
//echo $str;
$data['attach']='a';
//        ksort($data);
  //      $pay_data = urldecode(http_build_query($data));
    //    $pay_data .=  $merkey;
        $sign = md5($str);
        $data['sign'] = $sign;
//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data,null,30),true);
        if($result['errcode'] != '0' )
        {
            Log::error('Create Zhuque API Error:'.$result['error_msg']);
            throw new OrderException([
                'msg'   => 'Create Zhuque API Error:'.$result['error_msg'],
                'errCode'   => 200009
            ]);
        }
//var_dump($result);die();
        return $result['qrcode'];
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
        $url = $this->pay($params,'ALICODE');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * @return array
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WXCODE');
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
  //      $input = file_get_contents("php://input");
        Log::notice("Zhuque notify data".json_encode($_POST));
//        $notifyData = json_decode($input,true);
        if(1){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderNo'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.$input);
    }
}
