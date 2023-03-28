<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/15
 * Time: 23:41
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class XinfutongPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='10106'){
        $url = 'https://newapi.xfuoo.com/unionOrder';

        $userkey = 'yxjfngrqdpducugnzf';
        $data = [
            'partner'   =>  '1010056476',
            'service'   =>  $type,
            'tradeNo'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'extra'   =>  'goods',
        ];
        $data['sign'] = $sign = md5("amount=".$data['amount']."&extra=".$data['extra']."&notifyUrl=".$data['notifyUrl']."&partner=".$data['partner']."&service=".$data['service']."&tradeNo=".$data['tradeNo']."&".$userkey);

//        http://cc.byfbqgi.cn/
        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }



    /**
     * 查询接口
     */
    public function query($notifyData,$type){
        $url = 'https://newapi.xfuoo.com/orderQuery';
        $data = [
            'partner'  =>  '1010056476',
            'service'  =>  $type, //支付订单 10302   代付订单 10301
            'outTradeNo'  =>  $notifyData['outTradeNo'],
        ];
        $userkey = 'yxjfngrqdpducugnzf';
        $data['sign']=md5("outTradeNo=".$data['outTradeNo']."&partner=".$data['partner']."&service=".$data['service']."&".$userkey);
        $result =  json_decode(self::curlPost($url,($data)),true);
        Log::notice('query XinfutongPay  API notice:'.json_encode($result));
        if(  $result['isSuccess'] != 'T' ){
            Log::error('query XinfutongPay  API Error:');
            return false;
        }
        if($result['status'] != '1'){
            return false;
        }
        return true;
    }



    public function getSign($parameters,$key){
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .=  $key;
        $sign = (md5($signPars));
        return $sign;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'10106');   
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
        Log::notice("XinfutongPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == '1' ){
            $res = $this->query($notifyData,'10302');
            if($res){
                echo "success";
                $data['out_trade_no'] = $notifyData['outTradeNo'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('XinfutongPay API Error:'.json_encode($notifyData));
    }
}