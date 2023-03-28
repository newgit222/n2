<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/14
 * Time: 15:58
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


/**
 * 禅支付V2
 * Class ChanV2Pay
 * @package app\api\service\payment
 */
class KkPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_qrcode_auto'){

        $url = 'http://wap.0660428.com//api/gateway/create';
        $merkey = '0sAb18ive6f3DFsfTgWYKdWKessl8Qig';
        $data = [
            'mch_id'   =>  '1000517',
            'child_type' =>'H5',
            'out_trade_no'   =>  $order['trade_no'],
            'pay_type'=>$type,
            'total_fee'   =>  sprintf("%.2f",$order["amount"]),
            'notify_url'=>urlencode($this->config['notify_url']),
            'timestamp'   =>  time()*1000,
            'mch_secret'=>$merkey
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data,null,20),true);


        if($result['status'] != '1' ||   $result['code'] != 100)
        {
            Log::error('Create ChanV2Pay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create ChanV2Pay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
    }


    public function getSign($param)
    {
        ksort($param);
        $md5str = "";
        foreach ($param as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $md5str  =trim($md5str,'&');
        return strtoupper(md5($md5str));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'PERSONAL_RED_PACK');
        return [
            'request_url' => $url,
        ];
    }

  public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'PERSONAL_RED_PACK');
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
        Log::notice("ChanV2Pay notify data".json_encode($notifyData));
//        channel=alipay_qrcode_auto&tradeNo=456931005714923520&outTradeNo=115868704409556&money=200&realMoney=200&uid=456844661697282048&sign=CBE1BE600AFEEA6FB5DEA2CC9698F865
        if(isset($notifyData['out_trade_no'])){
            echo "success";
            $data['out_trade_no'] = $notifyData['out_trade_no'];
            return $data;
        }
        echo "error";
        Log::error('ChanV2Pay API Error:'.json_encode($notifyData));
    }

}
