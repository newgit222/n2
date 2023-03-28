<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/29
 * Time: 22:00
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class PaopaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ZFB'){


        $url = 'http://apiserver.paopaopay98.com/payserver/server/thirdOrderserver/createv3';
        $merkey = '4fe414b99d4249038e6b3c51e8aa24eb';
        $data = [
//            'appId' =>  '',
            'storeCode' =>  '1001',
            'orderCode' =>  $order['trade_no'],
            'payType' =>  $type,
            'orderTotal' =>  sprintf("%.1f",$order["amount"]),
            'noticeUrl' =>  $this->config['notify_url'],
            'remarks' =>  'goods',
            'payerCode' =>  '123',
        ];
         $signStr = $data['storeCode'] . $data['orderCode'] . $data['payType'].$data['noticeUrl'] . $data['orderTotal'].$data['payerCode'].'|'.$merkey;
        $data['sign'] = md5($signStr );
        $headers = array("Content-type: application/json;charset='utf-8'");
         $result =  json_decode(self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['code'] != '0' )
        {
            Log::error('Create PaopaoPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create PaopaoPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['comments'];
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
        Log::notice("PaopaoPay notify data".$input);
        $notifyData = json_decode($input,true);
        if(isset($notifyData['orderCode']) ){
            echo  json_encode(['code'=>'0','msg'=>'SUCCESS']);
            $data['out_trade_no'] = $notifyData['orderCode'];
            return $data;
        }
        echo "error";
        Log::error('PaopaoPay API Error:'.$input);
    }
}