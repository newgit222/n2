<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/20
 * Time: 18:57
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XinchengPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_static_qrcode'){
        $url = 'http://xc.4n7s.com/gateway/merchant/order';
        $merkey = 'bIOgJafP';
        $data = [
            'merchant_user_id'  =>  '2977',
//            'sign_type'  =>  'MD5',
            'out_trade_no'  =>  $order['trade_no'],
//            'pay_mode'  =>  '',
            'pay_way'  =>  $type,
            'amount'  =>  sprintf("%.2f",$order["amount"]),
            'return_url'  =>  $this->config['return_url'],
            'notify_url'  =>  $this->config['notify_url'],
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $data['sign_type'] = 'MD5';
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['code'] != '200' )
        {
            Log::error('Create XinchengPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create XinchengPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }


    public function query($notifyData){
        $url = 'http://xc.4n7s.com/gateway/merchant/query';

        $merkey = 'bIOgJafP';
        $data=array(
            'merchant_user_id'=>'2977',
            'out_trade_no'=>$notifyData['out_trade_no']
        );

        $data['sign'] = $this->getSign($data,$merkey);
        $data['sign_type'] = 'MD5';
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        Log::notice('query XinchengPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query XinchengPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['order_status'] != 'SUCCESS' ){
            return false;
        }
        return true;
    }




    public function getSign($data,$merkey){
        ksort($data);

        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1).$merkey;

        return (md5($tmp_str));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_static_qrcode');
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
        Log::notice("XinchengPay notify data1".json_encode($notifyData));

        if(isset($notifyData['status']) && $notifyData['status'] == "SUCCESS" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('XinchengPay API Error:'.json_encode($notifyData));
    }
}
