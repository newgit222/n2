<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/29
 * Time: 19:12
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JiabaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='Alipay'){
        $data = [
            "merchantCode"  =>  'xxp123',
            "merchantTradeNo"  =>  $order['trade_no'],
            "userId"  =>  '1',
            "amount"  => sprintf("%.2f",$order["amount"]),
            "notifyUrl"  => $this->config['notify_url'],
            "returnUrl"  => $this->config['return_url'],
            "terminalType"  =>  '1',
            "channel"  =>  $type,
        ];
        $merkey = '40f437856a9447b489e510bfb33aacd5';
        $url = 'https://api.ctzbao.com/pay/center/deposit/apply';
        ksort($data);
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= $v;
        }
        $data['sign'] = md5($tmp_str.$merkey);//签名
        $param = [
            'merchantCode'  =>  'xxp123',
            'signType'  =>  'md5 ',
            'content'  =>  json_encode($data),
        ];
         $result =  json_decode(self::curlPost($url,json_encode($param)),true);
        if($result['status'] != 'SUCCESS' )
        {
            Log::error('Create Zhuque API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create Zhuque API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['content']['payUrl'];
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'Alipay');
        echo json_encode($url); die();
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'Wechat');
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
        Log::notice("JiabaoPay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['content']['tradeStatus'] == "PAYMENT_SUCCESS" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['content']['merchantTradeNo'];
            return $data;
        }
        echo "error";
        Log::error('JiabaoPay API Error:'.$input);
    }
}