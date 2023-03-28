<?php
/*by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 22:42
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


/**
 * 星支付
 * Class XingPay
 * @package app\api\service\payment
 */
class XingPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='wexinScan'){
$data['attach']='json';
        $data['user_id'] = '10288';
        $data['out_order_no'] =  $order['trade_no'];
        $data['amount'] =  sprintf("%.2f",$order["amount"]);
        $data['product_name'] = 'goods';
        $data['type'] = $type;
        $data['back_url'] = $this->config['notify_url'];
        $data['return_url'] = $this->config['return_url'];
        $key = "b9344ba240cdb94d5743ac1b1f9fc854";
        $data['sign'] = $this->getSign($data,$key);
//var_dump($data);
        $url = "https://mch.xinydck.com/cashier/pay/";
//var_dump(self::curlPost($url,$data,null,20));die();
        $result =  json_decode(self::curlPost($url,$data,null,20),true);
//var_dump( $result);die();
        if($result['returnCode'] != '0' )
        {
            Log::error('Create XingPay API Error:'.$result['ret_msg']);
            throw new OrderException([
                'msg'   => 'Create XingPay API Error:'.$result['ret_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['content'];
    }



    /**
     * 生成签名
     * @param $data
     * @param $secret
     * @return string
     */
    private function getSign($data,$secret )
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $sign = md5($string_a.'key='.$secret);
        return $sign;
    }



    /**
     * @param $params
     * @return array
     * 微信
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'weixinH5');
        return [
            'request_url' => $url,
        ];
    }


   public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'weixinH5');
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
        Log::notice("XingPay notify data".json_encode($notifyData));
        if($notifyData['status'] ==1){
            echo "success";
            $data['out_trade_no'] = $notifyData['out_order_no'];
            return $data;
        }
        echo "error";
        Log::error('XingPay API Error:'.json_encode($notifyData));
    }



}

