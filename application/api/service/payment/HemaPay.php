<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/2
 * Time: 23:55
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\migration\command\seed\Run;

class HemaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='2'){


        $url = 'https://fx.hbmai.com/createOrder';
        $merkey = '18a6b262BdXCjAQhJWFkBLEF4KV2Q';


        $data = [
            'merchant'  =>  '10526',
            'payId'  =>  $order['trade_no'],
            'type'  =>  $type,
            'price'  =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'  =>  $this->config['notify_url'],
            'returnUrl'  =>  $this->config['return_url'],
            'param' =>  '123'
        ];


        $data['sign'] = md5($data['payId'].$data['param'].$data['type'].$data['price'].$merkey);


         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '1' )
        {
            Log::error('Create HemaPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create HemaPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }


    private function query($notifyData){
        $url = 'https://fx.hbmai.com/checkOrders';

        $merkey = '18a6b262BdXCjAQhJWFkBLEF4KV2Q';

        $data = [
            'merchant' =>  '10526',
            'orderId'   =>  $notifyData['payId'],
        ];
        $data['sign'] = md5($data['orderId'].$data['merchant'].$merkey);
         $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query HemaPay  API notice:'.json_encode($result));
        if(  $result['code'] == '1' || $result['code'] == '2' ){
            return true;
        }
        return false;
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
        Log::notice("HemaPay notify data1".json_encode($notifyData));
        if(isset($notifyData['payId'])   ){
//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['payId'];
                return $data;
//            }
        }
        echo "error";
        Log::error('HemaPay API Error:'.json_encode($notifyData));
    }
}