<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/1
 * Time: 20:10
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class OtcstarPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){
        $url = 'http://api.bxltgcx.cn/api/startOrder';
        $merkey = '87bb9b855ab707c7ec5dbee3b8e710c1';
        $data = [
            'merchantNum'   =>  '778899',
            'orderNo'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'payType'   =>  $type,
        ];
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$data['amount'].$data['notifyUrl'].$merkey);

        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create OtcstarPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create OtcstarPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }

    public function query($notifyData){
        $url = 'http://api.bxltgcx.cn/api/getOrderMerchantCode?';
        $key = '87bb9b855ab707c7ec5dbee3b8e710c1';
        $data=array(
            'merchantNum'=>'778899',
            'orderNo'=>$notifyData['platformOrderNo'],
        );
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$key);
        $result =  json_decode(self::curlGet($url.http_build_query($data)),true);
        Log::notice('query OtcstarPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query OtcstarPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['orderState'] != '4' ){
            return false;
        }
        return true;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
        return [
            'request_url' => $url,
        ];
    }


 public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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
        Log::notice("OtcstarPay notify data1".json_encode($notifyData));
        if($notifyData['state'] == "1" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('OtcstarPay API Error:'.json_encode($notifyData));
    }
}
