<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/22
 * Time: 13:51
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YixinPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='al101'){



        $data = [
//            'mchtId'    =>  '2000322000172342',
//            'version'    =>  '20',
//            'biz'    =>  'al101',
            'orderId'    =>  $order['trade_no'],
            'orderTime'    =>  date('YmdHis'),
            'amount'    =>  sprintf("%.2f",$order["amount"])*100,
            'currencyType'    =>  'CNY',
            'goods'    =>  'goods',
            'notifyUrl'    =>  $this->config['notify_url'],
        ];

        $merkey = '2e6ab533e1e5487d9791b5d4b2f99686';
        $url = 'http://47.56.198.191:12080/gateway/cashier/mchtCall';
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $pay_data .= '&key='. $merkey;
        $data['sign'] = strtoupper(md5($pay_data));
        $data['mchtId'] = '2000322000172342';
        $data['version'] = '20';
        $data['biz'] = $type;
        $data['request_post_url']= $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);

    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'al101');
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
        Log::notice("YixinPay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['body']['status'] == "SUCCESS" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderId'];
            return $data;
        }
        echo "error";
        Log::error('YixinPay API Error:'.$input);
    }
}