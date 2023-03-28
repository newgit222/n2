<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/14
 * Time: 20:15
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class Zhuque2Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='WXCODE'){
        $data['notify_url'] = $this->config['notify_url'];
        $data['out_trade_no'] = $order['trade_no'];
        $data['body'] = 'goods';
        $data['total_fee'] = sprintf("%.2f",$order["amount"]);
        $data['client_ip'] = get_userip();
        $data['mch_id'] = '13196490703';
        $data['type'] = $type;
        $merkey = '.Nev,_2uJXYxPo7^QeD`OJdP';
        $url = 'http://shydo.zzdklpp.com/api/pay';
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $pay_data .=  $merkey;
        $sign = md5($pay_data);
        $data['sign'] = $sign;
        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        if($result['error_code'] != '0' )
        {
            Log::error('Create Zhuque API Error:'.$result['error_msg']);
            throw new OrderException([
                'msg'   => 'Create Zhuque API Error:'.$result['error_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['qr_code'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ALICODE');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WXCODESMALL');
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
        Log::notice("Zhuque notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['error_code'] == "0" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['out_trade_no'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.$input);
    }
}
