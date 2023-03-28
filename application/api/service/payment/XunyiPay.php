<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/4
 * Time: 17:39
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XunyiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ZFB'){


        $data = [
            'amount'    =>  sprintf("%.2f",$order["amount"])*100,
            'tradeNo'    =>  $order['trade_no'],
            'merchantId'    =>  '100027',
            'channelType'    =>  $type,
            'qrType'    =>  'HYM',
            'notifyUrl'    => $this->config['notify_url'],
//            'sign'    =>  '',
            'orderTime'    =>  time()*1000,
            'payType'    =>  'PC',
        ];

        $key = 'S6TQxBoHEKJF';
        $data['sign'] = MD5( $data['amount'] .'&'. $data['channelType'].'&'. $key .'&'. $data['merchantId'] .'&'. $data['orderTime'] .'&'. $data['qrType'] .'&'. $data['tradeNo'] );
        $url = 'https://www.xunyi.one:8888/pay';
        $headers = array("Content-type: application/json;charset='utf-8'");
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['code'] != '200' )
        {
            Log::error('Create XunyiPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create XunyiPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['qrUrl'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ZFB');
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
        Log::notice("XunyiPay notify data".$input);
        $notifyData = json_decode($input,true);
//        {"amount":200000,"merchantId":100027,"sign":"7FFE510C6756978FC666809B3B084C7C","tradeNo":"115833379806656"}
        echo "SUCCESS";
        $data['out_trade_no'] = $notifyData['tradeNo'];
        return $data;
    }

}