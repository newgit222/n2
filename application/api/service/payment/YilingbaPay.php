<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/23
 * Time: 23:39
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YilingbaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1085'){
        $url = 'https://www.108zfpay.com/Apipay_Order_create.html';
        $merkey = '1n2485ezu6mj6xdgbvg9a29o8enz90in'; 
        $data = [
            'uid'   =>  '1065',
            'order_no'   =>  $order['trade_no'],
            'time'   =>  date('Y-m-d H:i:s'),
            'channel'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
        ];
        $data['sign'] = $this->md5Sign($data,$merkey);
        $data['from_type'] = '1';
          $result =  json_decode(self::curlPost($url,($data)),true);
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create YilingbaPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create YilingbaPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['url'];
    }


    public function query($notifyData){
        $url = 'https://www.108zfpay.com/Apipay_Trade_query.html';
        $merkey = '1n2485ezu6mj6xdgbvg9a29o8enz90in';
        $data=array(
            'uid'   =>  '1065',
            'order_no'   =>  $notifyData['order_no'],
        );
        $data['sign'] = $this->md5Sign($data,$merkey);

         $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query YilingbaPay  API notice:'.json_encode($result));
        if(!isset($result['status']) ){
            return false;
        }
        if($result['status'] != '1' ){
            return false;
        }
        return true;
    }


    function md5Sign($data, $key)
    {
        ksort($data);
        $string = '';
        foreach ($data as $k => $vo) {
            if ($vo !== '')
                $string .= $k . '=' . $vo . '&';
        }
//        $string = rtrim($string, '&');
        $result = $string  .'key='. $key;
//        echo $result;
        return  strtoupper(md5($result));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1085');
        return [
            'request_url' => $url,
        ];
    }

 public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'1081');
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
        Log::notice("YilingbaPay notify data1".json_encode($notifyData));
        if($notifyData) {
            if ($notifyData['code'] == "1") {
                if($this->query($notifyData)) {
                    echo "OK";
                    $data['out_trade_no'] = $notifyData['order_no'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('YilingbaPay API Error:'.json_encode($notifyData));
    }

}
