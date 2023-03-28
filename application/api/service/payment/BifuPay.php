<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 19:18
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class BifuPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='zfbh5'){
        $data['notify_url'] = $this->config['notify_url'];
        $data['return_url'] = $this->config['return_url'];
        $data['out_order_no'] = $order['trade_no'];
        $data['subject'] = 'goods';
        $data['total_fee'] = sprintf("%.2f",$order["amount"]);
        $data['partner'] = '124984125661377';
        $data['user_seller'] = '556840';
//        $data['http_referer'] = '';//https 需要
        $merkey = 'CskSjbyBIerDRTVerFExp7XDag2ID8ME';
        $url = 'http://www.tudoupays.com/PayOrder/payorder';
        ksort($data);
        $pay_data = urldecode(http_build_query($data));
        $pay_data .=  $merkey;
        $sign = md5($pay_data);
        $data['sign'] = $sign;
        $data['pay_type'] = $type;
        $data['request_post_url'] = $url;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($data);
//        return $result =  self::curlPost($url,$data);
//        if($result['error_code'] != '0' )
//        {
//            Log::error('Create Zhuque API Error:'.$result['error_msg']);
//            throw new OrderException([
//                'msg'   => 'Create Zhuque API Error:'.$result['error_msg'],
//                'errCode'   => 200009
//            ]);
//        }
//        return $result['qr_code'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfbs($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbh5');
        echo ($url);die();
        return [
            'request_url' => $url,
        ];
    }

    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbh5');
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
        $url = $this->pay($params,'WXCODE');
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
//        $notifyData = json_decode($input,true);
//        if($notifyData['error_code'] == "0" ){
//            echo "success";
//            $data['out_trade_no'] = $notifyData['out_trade_no'];
//            return $data;
//        }
//        echo "error";
//        Log::error('hgpay API Error:'.$input);
    }
}