<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/21
 * Time: 23:52
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JiebaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){
        $url = 'http://p.hahshazi.com/api/startOrder';
        $merkey = '47a5a28e711e85ae4fd7f276b6b64b44';
        $data = [
            'merchantNum'   =>  'aszzxc1125112021',
            'orderNo'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'payType'   =>  $type,
        ];
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$data['amount'].$data['notifyUrl'].$merkey);
//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create JiebaoPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create JiebaoPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }

    public function query($notifyData){
        $url = 'http://p.hahshazi.com//api/getOrderInfo';
        $key = '47a5a28e711e85ae4fd7f276b6b64b44';
        $data=array(
            'merchantNum'=>'aszzxc1125112021',
            'orderNo'=>$notifyData['platformOrderNo'], 
        );
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$key);
        $result =  json_decode(self::curlGet($url.http_build_query($data)),true);
        Log::notice('query JiebaoPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query JiebaoPay  API Error:'.$result['msg']);
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
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }


 public function test($params)
    {
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

        $notifyData =$_GET;
        Log::notice("JiebaoPay notify data1".json_encode($notifyData));
        if($notifyData['state'] == "1" ){
//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderNo'];
                return $data;
//            }
        }
        echo "error";
        Log::error('JiebaoPay API Error:'.json_encode($notifyData));
    }
}
