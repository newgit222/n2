<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/1
 * Time: 19:12
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HongquanePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){
        $url = 'https://api.hqezf.com/gateway_pay';
        $merkey = 'c09a4b80c8cb7bbf05ade8712475b24e';
        $data = [
            "pid" =>$type,
            "pos_id" => '156',
            "out_order_id" => $order['trade_no'],
            "notify_url" => $this->config['notify_url'],
            "callback_url" => $this->config['return_url'],
            "amount" => sprintf("%.2f",$order["amount"]),
            "res_type" => "json" //是否JSON模式
        ];
        $data['sign'] = $this->md5Sign($data, $merkey);
        $result =  json_decode(self::curlPost($url,($data)),true);
        if($result['code'] != '0' )
        {
            Log::error('Create HongquanePay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create HongquanePay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
    }


    public function query($notifyData){
        $url = 'https://api.hqezf.com/gateway_status';
        $key = 'c09a4b80c8cb7bbf05ade8712475b24e';
        $data=array(
            'pos_id'=>'156',
            'order_id'=>$notifyData['order_id'],
        );
        $data['sign'] = $this->md5Sign($data, $key);
         $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query HongquanePay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query HongquanePay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data'] == '4' || $result['data'] == '1' || $result['data'] == '2' ){
            return true;
        }
        return false;

    }



    function md5Sign($data, $key, $connect = '', $is_md5 = true)
    {
        ksort($data);
        $string = '';
        foreach ($data as $k => $vo) {
            if ($vo !== '')
                $string .= $k . '=' . $vo . '&';
        }
        $string = rtrim($string, '&');
        $result = $string . $connect . $key;
        return $is_md5 ? md5($result) : $result;
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

        $notifyData =$_POST;
        Log::notice("HongquanePay notify data1".json_encode($notifyData));

        if(isset($notifyData['order_id'])){
            if($this->query($notifyData)) {
                echo "OK";
                $data['out_trade_no'] = $notifyData['order_id'];
                return $data;
            }
        }
        echo "error";
        Log::error('HongquanePay API Error:'.json_encode($notifyData));
    }
}