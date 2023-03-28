<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/2
 * Time: 15:47
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JuliPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='0'){

        $url = 'http://gateway.toulangpay888.com/pay/unifiedOrder';

        $data = [
            'customer_id'   =>  '500002',
            'account'   =>  '13812345678',
            'nonce_str'   =>  self::createNonceStr(),
            'customer_order_no'   =>  $order['trade_no'],
            'product_intro'   =>  'goods',
            'order_amount'   =>  sprintf("%.2f",$order["amount"])*100,
            'payment_method'   =>  '0',
            'place_ip'   =>  get_userip(),
            'place_area'   =>  '',
            'trade_type'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
            'expire_min'   =>  $this->config['return_url'],
            'attach'   =>  '1',
        ];

        $secret_key = '17eaac833f1341bf';
        $data['sign'] = $this->getSign($data,$secret_key);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create JuliPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create JuliPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }



    /**
     * 查询接口
     */
    public function query($notifyData){
        $url = 'http://gateway.toulangpay888.com/pay/orderQuery';

        $data = [
            'customer_id'   =>  '500002',
            'account'   =>  '13812345678',
            'nonce_str'   =>  self::createNonceStr(),
            'customer_order_no'   =>  $notifyData['customer_order_no'],
            'platform_order_no'   =>  $notifyData['platform_order_no'],
        ];
        $secret_key = '17eaac833f1341bf';
        $data['sign'] = $this->getSign($data,$secret_key);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query JuliPay  API notice:'.json_encode($result));
        if($result['code'] != '200' ){
            Log::error('query JuliPay  API Error:'.$result['message']);
            return false;
        }
        if($result['data']['order_status'] != '已完成' ){
            Log::error('query JuliPay  API Error:'.$result['data']['order_status']);
            return false;
        }
        return true;
    }



    public function getSign($data,$key){

        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1);
        return strtoupper(md5($tmp_str.$key));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1');
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

        $input = file_get_contents("php://input");
        Log::notice("JuliPay notify data".$input);
        $notifyData = json_decode($input,true);

        if(isset($notifyData['data'])){
            $res = $this->query($notifyData['data']);
            if($res){
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['data']['customer_order_no'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('JuliPay API Error:'.$input);
    }
}