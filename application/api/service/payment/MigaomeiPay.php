<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/13
 * Time: 0:51
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class MigaomeiPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){


        $signArr = [
            'MerId'=>'10004',//商户唯一标识
            'PayType'=>$type,
            'MerOrderNo'=>$order['trade_no'],
            'MerOrderTime'=>date('Y-m-d H:i:s'),
            'UserId'=>'1',
            'Amount'=>sprintf("%.2f",$order["amount"])*100,
            'GoodsName'=>'goods',
            'GoodsDesc'=>'goods',
            'GoodsRemark'=>'goods',
            'NotifyUrl'=>$this->config['notify_url'],
            'Version'=>2.1,//版本号
        ];
        $signKey = '5EEEDFCCA84E173DDA1DA6EF4EA6AA1B';
        //获取签名信息  签名参数 Amount|MerId|MerOrderNo|MerOrderTime|SignKey  参数组合然后md5加密
        $str = $signArr['Amount'].'|'.$signArr['MerId'].'|'.$signArr['MerOrderNo'].'|'.$signArr['MerOrderTime'].'|'.$signKey;
        $sign = md5($str);
        //添加签名参数
        $signArr['sign'] = $sign;
        $url = 'https://www.uniqlo.jl.cn:5900/order/pay/';

        return $result =  json_decode(self::curlPost($url,$signArr),true);
//        if($result['code'] != '200' )
//        {
//            Log::error('Create FuyaPay API Error:'.$result['msg']);
//            throw new OrderException([
//                'msg'   => 'Create FuyaPay API Error:'.$result['msg'],
//                'errCode'   => 200009
//            ]);
//        }
//        return $result['url'];
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
        Log::notice("MigaomeiPay notify data".json_encode($notifyData));
//        if($notifyData['callbacks'] == "CODE_SUCCESS" ){
//            echo "success";
//            $data['out_trade_no'] = $notifyData['out_trade_no'];
//            return $data;
//        }
//        echo "error";
//        Log::error('FuwaPay API Error:'.json_encode($notifyData));
    }
}