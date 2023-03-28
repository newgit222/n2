<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/4
 * Time: 16:25
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class ZhongdaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='wxh51'){
        $url = 'http://gateway.jcsnhb.com/api/payOrder';


	$data['orderNo'] = $order['trade_no'];
        $data['account'] = '980688908';
        $data['money'] = intval(sprintf("%.2f",$order["amount"]));
        $data['method'] = 'wxh5'; 
        $data['notifyUrl'] = $this->config['notify_url'];
        $data['sign'] = $this->create_sign($data,'fgvo9xgusvhax9huxydqrt0f27ikkwzd');
//echo 'sign is'. $data['sign'];
        $data['body'] = '测试';
//var_dump($data);
 $response = self::curlPost($url, $data);
        $response = json_decode($response,true);
//var_dump($response);die();
        if($response['result'] !='success')
          {
              Log::error('Create JSPAY API Error:'.($response['msg'] ? $response['msg']:""));
              throw new OrderException([
                  'msg'   => 'Create JSPAY API Error:'.($response['msg'] ? $response['msg']:""),
                  'errCode'   => 200009
              ]);
        }
        return $response['payUrl'];


 //       $data['request_post_url'] =$url;
  //      return "http://www.wantongpays.com/pay.php?".http_build_query($data);

    }
public function create_sign($data,$key){
        $str =$this->json_encode_ex($data) . $key;
//echo 'origin data is:'.$str;
//echo $str;
        return strtoupper(md5($str));
    }
 public function json_encode_ex($value){
        $str = "account=".$value["account"]."&method=".$value["method"]."&money=".$value["money"]."&notifyUrl=".$value["notifyUrl"]."&orderNo=".$value["orderNo"]."&key=";
//echo $str;
//echo 3;die();
        return $str;
    }
    public function getSign($parameters,$key){
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = md5($signPars);
        return $sign;
    }


    public function query($notifyData){
return true;
        $url = 'http://pay.kaqisq.com/?c=Pay&a=query';

        $mch_key = '7a6e283f3cd8c00a8bb91792b079f529cdfc8481';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'zhong225',
            'out_order_sn'=>$notifyData['sh_order']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);

        $result =  json_decode(self::curlPost($url,$p_data,null,20),true);
        Log::notice('query ChuangshijiPay  API notice:'.json_encode($result));
        if($result['code'] != '1' ){
            return false;
        }
        if($result['data']['status'] != '9' ){
            return false;
        }
        return true;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'12');
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'12');
        return [
            'request_url' => $url,
        ];
    }


 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'12');
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
        $url = $this->pay($params,12);
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
        Log::notice("ChuangshijiPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "2" ){
            if(1){
                echo "success";
                $data['out_trade_no'] = $notifyData['orderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('ChuangshijiPay API Error:'.json_encode($notifyData));
    }
}
