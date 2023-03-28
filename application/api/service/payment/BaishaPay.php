<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/28
 * Time: 15:00
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use phpDocumentor\Reflection\Types\Self_;
use think\Log;

class BaishaPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){


        $url = 'http://116.62.155.195:4900/order/pay/';
        $signKey= '87382C7511645390BD18799AC494D93F';
        $signArr = [
            'MerId'=>'555555',//商户唯一标识
            'PayType'=>$type,
            'MerOrderNo'=>$order['trade_no'],
            'MerOrderTime'=>date("YmdHis"),
//            'UserId'=>$userId,
            'Amount'=> sprintf("%.2f",$order["amount"])*100,
//            'GoodsName'=>$goodsName,
//            'GoodsDesc'=>$goodsDesc,
//            'GoodsRemark'=>$goodsRemark,
            'NotifyUrl'=>$this->config['notify_url'],
//            'SuccessUrl'=>$this->config['return_url'],
//            'TimeoutUrl'=>$timeoutUrl,
//            'ErrorUrl'=>$errorUrl,
            'Version'=>2.1,//版本号
        ];
        //获取签名信息  签名参数 Amount|MerId|MerOrderNo|MerOrderTime|SignKey  参数组合然后md5加密
        $str = $signArr['Amount'].'|'.$signArr['MerId'].'|'.$signArr['MerOrderNo'].'|'.$signArr['MerOrderTime'].'|'.$signKey;
        $sign = md5($str);
        //添加签名参数
        $signArr['sign'] = $sign;
        //请求支付接口
        $result = json_decode(self::curlPost($url,$signArr),true);
        if($result['code'] != 'success' )
        {
            Log::error('Create BaishaPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create BaishaPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['PayUrl'];
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
        Log::notice("BaishaPay notify data1".json_encode($notifyData));
        if(isset($notifyData['PayStatus']) && $notifyData['PayStatus'] == "success" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['MerOrderNo'];
            return $data;
        }
        echo "error";
        Log::error('BaishaPay API Error:'.json_encode($notifyData));
    }
}