<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/9
 * Time: 20:51
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HgPay extends ApiPayment
{


    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){
        $data['pay_order'] = $order['trade_no'];
        $data['pay_type'] = $type;
        $data['pay_amount'] = $order["amount"]*100; //单位分
        $data['pay_nturl'] = $this->config['notify_url'];
        $data['pay_caurl'] = $this->config['return_url'];
        $data['pay_merchid'] = '100011';
        $data['pay_mark'] = 'goods';
        $data['pay_termain'] = 'wap';
        $merkey = 'F80C66A72FB7B8F12A8F29861A874580';
        $pay_data = '';
        ksort($data);
        foreach ($data as $key => $val)
        {
            $pay_data .= $key .'=' . $val . '&';
        }
        $pay_data .= 'key=' . $merkey;
        $sign = md5($pay_data);

        $data['pay_sign'] = $sign;
        return 'http://api.hgqhr.cn/v1/smpay?'.http_build_query($data);

//        return self::curlPost('http://api.hgqhr.cn/v1/smpay',$pay_data);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        Log::notice("hgpay notify data".$input);
//      $notifyData = '{"pay_amount":5,"pay_mark":"goods","pay_merchid":"100011","pay_order":"115812570567150","pay_payment":5,"pay_thisorder":"SM202002040594115E40115593B29867","status":1,"sign":"d2e953230c8d6797608b156114e7458b"}';
        $notifyData = json_decode($input,true);
        if($notifyData['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['pay_order'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.$input);   
    }

}