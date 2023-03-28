<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/5
 * Time: 19:59
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\migration\command\seed\Run;

class HuifengPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='8007'){


        $data = [
            'mch_id'    =>  '34523734',
            'app_id'    =>  '49c69ab312154b55a990e8891d2ae53e',
            'pay_id'    =>  $type,
            'body'    =>  'goods',
            'money'    =>  sprintf("%.2f",$order["amount"])*100,
            'subject'    =>  'goods',
            'currency'    =>  'cny',
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    => $this->config['return_url'],
            'mch_order_no'    =>  $order['trade_no'],
        ];



        $key = 'ACWA99XHMMGYQAFH7DTHWWAFNUR8HZPJEB9RRHH9OS08MTGKYQGFNNHO0ALHQR7R3F6LQP2BVXZSWV61YU64X74LAX5IGVUDMOWA2EOLK2HDKCM6HHTZKASTY1FDGKCY';

        $data['sign'] = md5('app_id='.$data['app_id'].'&mch_id='.$data['mch_id'].'&mch_order_no='.$data['mch_order_no'].'&money='.$data['money'].'&notify_url='.$data['notify_url'].'&pay_id='.$data['pay_id'].'&key='.$key.'');
        $url = 'http://api.mosoju.com/api/pay/create_order';
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create HuifengPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create HuifengPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_url'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function zfb_h5($params)
    {
        //获取预下单
        $url = $this->pay($params,'8007');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function vx_h5($params)
    {
        //获取预下单
        $url = $this->pay($params,'8003');
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
        Log::notice("HuifengPay notify data".$input);
//        $notifyData = json_decode($input,true);
////        {"amount":200000,"merchantId":100027,"sign":"7FFE510C6756978FC666809B3B084C7C","tradeNo":"115833379806656"}
//        echo "SUCCESS";
//        $data['out_trade_no'] = $notifyData['tradeNo'];
//        return $data;  
    }

}