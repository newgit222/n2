<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/21
 * Time: 14:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class VPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='401'){

        $url = 'http://47.57.95.143/api/merchant/payOrder';

        $merkey = '02984d73bf12459188c76a1a833749d5';


        $data = [
            'app_id'    =>  '5662b5df35664cc6bb5286f659df4a77',
            'amount'    =>  sprintf("%.2f",$order["amount"]),
            'out_trade_id'    =>  $order['trade_no'],
            'payment_code'    =>  $type,
            'notify_url'    => $this->config['notify_url'],
            'callback_url'    =>  $this->config['return_url'],
        ];

        $data['md5_sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['state'] != 'ok' )
        {
            Log::error('Create VPay API Error:'.$result['err']);
            throw new OrderException([
                'msg'   => 'Create VPay API Error:'.$result['err'],
                'errCode'   => 200009
            ]);
        }
        return $result['data'];
    }


    public function query($notifyData){
        $url = 'http://47.57.95.143/api/merchant/queryOrder';

        $key = '02984d73bf12459188c76a1a833749d5';
        $data=array(
            'app_id'=>'5662b5df35664cc6bb5286f659df4a77',
            'out_trade_id'=>$notifyData['out_trade_id'],
        );
        $data['md5_sign'] = $this->getSign($data,$key);

        $result =  json_decode(self::curlPost($url,($data)),true);
        Log::notice('query VPay  API notice:'.json_encode($result));
        if(  $result['state'] != 'ok' ){
            Log::error('query VPay  API Error:'.$result['err']);
            return false;
        }
        if($result['order_status'] != '2' ){
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
        $signPars .= 'app_secret='.$key;
        $sign = md5($signPars);
        return strtoupper($sign);
    }

    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'401');
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
        $notifyData =$_GET;
        Log::notice("VPay notify data".json_encode($notifyData));
        if(isset($notifyData['order_status'])){
            if($notifyData['order_status'] == '2' ){
                if($this->query($notifyData)){
                    echo "success";
                    $data['out_trade_no'] = $notifyData['out_trade_id'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('VPay API Error:'.json_encode($notifyData));
    }
}