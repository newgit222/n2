<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/9
 * Time: 15:37
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WanshitongPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://admin.wanshitong8.com/api/pay/add';


        $key = 'ffeba319910741deb4ef63c69bc1caa3';


        $data = [
            'customer_id'   =>  '61',
            'out_order_id'   => $order['trade_no'],
            'pay_type'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'from_ip'   =>  get_userip(),
        ];

        $data['sign'] = $this->getSign($data,$key);

        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);

    }


    public function getSign($parameters,$key){
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = $signPars.'key='.$key;
        $sign = strtoupper(md5($signPars));
        return $sign;
    }




    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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
        Log::notice("WanshitongPay notify data".json_encode($notifyData));

        $input = file_get_contents("php://input");
        Log::notice("WanshitongPay notify data".$input);
        if($notifyData['notice'] == "1" ){
                echo "ok";
                $data['out_trade_no'] = $notifyData['out_order_id'];
                return $data;
        }
        echo "error";
        Log::error('WanshitongPay API Error:'.json_encode($notifyData));
    }
}
