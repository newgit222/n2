<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/27
 * Time: 22:54
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YitaiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='2'){
        $url = 'http://www.yyfnbpay.com:8080/createOrder';

        $merkey = 'ce09d0f640494f6aab4c4527c521e28a';

        $data = [
            'payId'  =>  $order['trade_no'],
            'type'  =>  $type,
            'price'  =>  sprintf("%.2f",$order["amount"]),
            'param'  =>  'caishenzhifu',
            'notifyUrl'  =>  $this->config['notify_url'],
            'returnUrl'  =>  $this->config['return_url'],
        ];
        $data['sign'] = md5($data['payId'].'|'.$data['param'].'|'.$data['type'].'|'.$data['price'].'|'.$data['notifyUrl'].'|'.$data['returnUrl'].'|'.$merkey);//$this->getSign($data,$merkey);
        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }

    public function query($notifyData){
        $url = 'http://yyfnbpay.com:8080/checkOrder';

        $native=array(
            'orderId'=>$notifyData['payId'],
        );
        $result =  json_decode(self::curlPost($url,$native),true);
        Log::notice('query YitaiPay  API notice:'.json_encode($result));
        if(  $result['code'] != '1' ){
            Log::error('query YitaiPay  API Error:'.$result['msg']);
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
        $url = $this->pay($params,'2');
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
        $notifyData = $_GET;
        Log::notice("YitaiPay notify data".json_encode($notifyData));

        if(isset($notifyData['payId']) ){
            if($this->query($notifyData)) {
                echo "Success";
                $data['out_trade_no'] = $notifyData['payId'];
                return $data;  
            }
        }
        echo "error";
        Log::error('YitaiPay API Error:'.json_encode($notifyData));
    }
}