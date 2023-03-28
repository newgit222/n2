<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/13
 * Time: 14:29
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WangcaiPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){

        $url = 'http://admin.gxfc.one/api/pay/add';


        $key = '08cd46eb55264516b4db98d65415859f';


        $data = [
            'customer_id'   =>  '174',
            'out_order_id'   => $order['trade_no'],
            'pay_type'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'from_ip'   =>  get_userip(),
        ];

        $data['sign'] = $this->getSign($data,$key);

        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);

    }


    /**
     * 订单查询
     */
    public function query($notifyData){
        $url = 'http://admin.gxfc.one/api/pay/query';

        $mch_key = '08cd46eb55264516b4db98d65415859f';
        $data=array(
            'customer_id'    =>  '174',
            'out_order_id'    =>  $notifyData['out_order_id'],
        );
        $data['sign'] = $this->getSign($data,$mch_key);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query WangcaiPay  API notice:'.json_encode($result));
        if($result['status'] != '1' ){
            return false;
        }
        if($result['notice'] != '1' ){
            return false;
        }
        return true;
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


    /**
     * @param $params
     * 支付宝
     */
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
        Log::notice("WangcaiPay notify data".json_encode($notifyData));

        $input = file_get_contents("php://input");
        Log::notice("WangcaiPay notify data".$input);
        if($notifyData['notice'] == "1" ){
            if($this->query($notifyData)) {
                echo "ok";
                $data['out_trade_no'] = $notifyData['out_order_id'];
                return $data;
            }
        }
        echo "error";
        Log::error('WangcaiPay API Error:'.json_encode($notifyData));
    }
}
