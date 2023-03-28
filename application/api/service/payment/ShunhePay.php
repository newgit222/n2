<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/9
 * Time: 23:27
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\helper\hash\Md5;
use think\Log;

class ShunhePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='2'){

        $url = 'http://8.210.93.47:81/payApiHtml';

        $merchantId = '11';
        // 玩家id
        $userId = '123';
        // 订单号
        $subOrderNo = $order['trade_no'];
        // 产品id, 不用写
        $productId = null;
        // 金额, 元, 最多两位小数
        $money = sprintf("%.2f",$order["amount"]);
        // 支付宝H5 类型是2
//        $type = ;
        // 玩家银行卡编号, 不用写
        $userBankCardId = null;
        // 银行类型, 不用写
        $bankType = null;
        // 异步通知地址, http或者https开头
        $notifyUrl = $this->config['notify_url'];
        // 同步通知地址, http或者https开头
        $returnUrl = $this->config['return_url'];
        // 商户密钥后台查看
        $secret = '4e74856699a14eca84ab21adbf93a0db';
        $signStr = "merchantId=" . ($merchantId === null ? "" : $merchantId) .
            "&userId=" . ($userId === null ? "" : $userId) .
            "&subOrderNo=" . ($subOrderNo === null ? "" : $subOrderNo) .
            "&productId=" . ($productId === null ? "" : $productId) .
            "&money=" . ($money === null ? "" : $money) .
            "&type=" . ($type === null ? "" : $type) .
            "&userBankCardId=" . ($userBankCardId === null ? "" : $userBankCardId) .
            "&bankType=" . ($bankType === null ? "" : $bankType) .
            "&notifyUrl=" . ($notifyUrl === null ? "" : $notifyUrl) .
            "&returnUrl=" . ($returnUrl === null ? "" : $returnUrl) .
            $secret;

        $data = [
            'merchantId'  =>  $merchantId,
            'userId'  =>  $userId,
            'subOrderNo'  =>  $subOrderNo,
//            'productId'  =>  '',
            'money'  =>  $money,
            'type'  =>  $type,
            'notifyUrl'  =>  $notifyUrl,
            'returnUrl'  =>  $returnUrl,
        ];
        $data['sign'] = md5($signStr);

        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }


    public function query($notifyData){
        $url = 'http://8.210.93.47:81/payOrderQry';

        $orderId = $notifyData['orderId'];
        $merchantId = '11';

        // 商户密钥后台查看
        $secret = '4e74856699a14eca84ab21adbf93a0db';
        $signStr = "orderId=" . ($orderId == null ? "" : $orderId) .
            "&merchantId=" . ($merchantId == null ? "" : $merchantId) .
            $secret;

        $data = [
            'merchantId'  =>  $merchantId,
            'orderId'  =>  $orderId,
        ];
        $data['sign'] = md5($signStr);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query ShunhePay  API notice:'.json_encode($result));
        if( ! $result['success']  ){
            Log::error('query ShunhePay  API Error:'.$result['desc']);
            return false;
        }
        return true;
    }





    public function getSign($data,$userkey){
        ksort($data);
        $string1 = '';
        foreach ($data as $key => $v) {
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key=" . $userkey;

        return strtoupper(md5($string1));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单  
        $url = $this->pay($params,'2');
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
        Log::notice("ShunhePay notify data1".json_encode($notifyData));
//        {"orderId":"115918000379044968965","userId":"123","money":"200.0000","type":"2","sign":"51b430d1a7ae26683f0c37efb58a448d"}
        if(isset($notifyData['orderId']) ){
            if($this->query($notifyData)) {
                echo "ok";
                $data['out_trade_no'] = $notifyData['orderId'];
                return $data;
            }
        }
        echo "error";
        Log::error('ShunhePay API Error:'.json_encode($notifyData));
    }
}
