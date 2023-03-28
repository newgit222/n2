<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/18
 * Time: 17:20
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class RonghuixinV2Pay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_qrcode_auto'){



        $url = 'http://rhxmall.com/api/v1/charges';

        $merkey = '82adae37e3f94dce89bba6cf9d8e8680';


        $data = [
            'uid'   =>  '458292991488950272',
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'channel'   =>  $type,
            'outTradeNo'   =>  $order['trade_no'],
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'timestamp'   =>  time()*1000,
        ];



        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create RonghuixinV2Pay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create RonghuixinV2Pay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }


    private function getSign($data,$secret )
    {
        $data['token']  = $secret;

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_qrcode_auto');
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
        $notifyData =$_POST;
        Log::notice("RonghuixinV2Pay notify data".json_encode($notifyData));
//        channel=alipay_qrcode_auto&tradeNo=456931005714923520&outTradeNo=115868704409556&money=200&realMoney=200&uid=456844661697282048&sign=CBE1BE600AFEEA6FB5DEA2CC9698F865
        if(isset($notifyData['outTradeNo'])){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['outTradeNo'];
            return $data;
        }
        echo "error";
        Log::error('RonghuixinV2Pay API Error:'.json_encode($notifyData));   
    }
}