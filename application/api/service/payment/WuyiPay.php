<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/7
 * Time: 22:51
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class WuyiPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){


        $data = [
            'mchtId'    =>  'mcht001',
            'transAmt'    =>  sprintf("%.2f",$order["amount"]),
            'orderId'    =>  $order['trade_no'],
            'transType'    =>  $type,
            'notifyUrl'    => $this->config['notify_url'],
        ];
        $key ='09345gopwpeof';
        $data['sign'] = md5($data['mchtId'].$data['orderId'].$data['transAmt'].$data['transType'].$data['notifyUrl'].$key);
        $url = 'https://www.xunjie839.xyz/trans/api/createOrder';
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create WuyiPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create WuyiPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['code_url'];
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

    /**
     * @param $params
     * 支付宝
     */
    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'wx');
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

        //这里不太确定返回格式 看下日志就行了
        $input = file_get_contents("php://input");
        Log::notice("WuyiPay notify data".$input);
        $notifyData = json_decode($input,true);

        $notifyData = $_POST;
        Log::notice("WuyiPay notify post data1".json_encode($notifyData));

        echo "SUCCESS";
        $data['out_trade_no'] = $notifyData['orderId'];
        return $data;
    }

}
