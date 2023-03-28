<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/15
 * Time: 21:33
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class DaniuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='904'){

        $data = [
            'pay_memberid'  =>  '10136',
            'pay_orderid'  =>  $order['trade_no'],
            'pay_applydate'  =>  date("Y-m-d H:i:s"),
            'pay_bankcode'  =>  $type,
            'pay_notifyurl'  =>  $this->config['notify_url'],
            'pay_callbackurl'  =>  $this->config['return_url'],
            'pay_amount'  =>  sprintf("%.2f",$order["amount"]),
        ];
        $Md5key = 'pini8j2po2ka7l72v11tv1df6gg8f61j';
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $data["pay_md5sign"] = $sign;
        $url = 'http://d0yun.com/Pay_Index.html';
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
        $url = $this->pay($params,'904');
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
        $notifyData = $_POST;
        Log::notice("DaniuPay notify data1".json_encode($notifyData));
//        {"memberid":"10136","orderid":"115842801426652","transaction_id":"20200315214909534952","amount":"100.0000","datetime":"20200315215351","returncode":"00","sign":"7A6FF7F77A9FB55DD87A90A945D34E12","attach":""}
        if($notifyData['returncode'] == "00" ){
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "ERROR";
        Log::error('DaniuPay API Error:'.json_encode($notifyData));
    }

}