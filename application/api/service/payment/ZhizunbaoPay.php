<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/10
 * Time: 23:40
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ZhizunbaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='10001'){
        $url = 'http://118.178.91.216/game/pay/alipayGrab';
        $merkey = '16697b9b-c721-48a6-a6c7-35550058827b';
        $data = [
            'uid'   =>  'WLW2005101678691985',
            'price'   =>  sprintf("%.2f",$order["amount"]),
            'istype'   =>  $type,
            'notify_url'   =>  $this->config['notify_url'],
            'return_url'   =>  $this->config['return_url'],
            'format'   =>  '123',
            'orderid'   =>  $order['trade_no'],
            'orderuid'   =>  '123',
            'goodsname'   =>  'goods',
        ];
        $data['key'] = $this->getSign($data,$merkey);

        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create ZhizunbaoPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create ZhizunbaoPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];

    }

    public function query($notifyData){
        $url = 'http://118.178.91.216/game/pay/queryOrder';
        $key = '16697b9b-c721-48a6-a6c7-35550058827b';
        $data=array(
            'uid'=>'WLW2005101678691985',
            'price'=> $notifyData['price'],
            'orderid'=>$notifyData['ordno'],
            'orderuid'=>$notifyData['orderuid'],
        );
        $data['key'] = $this->getSign($data,$key);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query ZhizunbaoPay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query ZhizunbaoPay  API Error:'.$result['message']);
            return false;
        }
        if($result['data']['status'] != '1' ){
            return false;
        }
        return true;
    }

    public function getSign($parameters,$key){
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .=  'key='.$key;
        $sign = (md5($signPars));
        return $sign;
    }

    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'10001');
        return [
            'request_url' => $url,
        ];
    }
 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'10001');
        return [
            'request_url' => $url,
        ];
    }

  public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'10001');
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
        Log::notice("ZhizunbaoPay notify data1".json_encode($notifyData));
        if( isset($notifyData['status']) && $notifyData['status'] == "1" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        echo "error";
        Log::error('OtcstarPay API Error:'.json_encode($notifyData));
    }
}
