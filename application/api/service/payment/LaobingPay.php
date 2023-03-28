<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/19
 * Time: 23:22
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class LaobingPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='11'){

        $url = 'http://103.88.35.78:81/payApiHtml';

        $merchantId = '95';
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
        $secret = 'c86c73a418224f73a74f7642e2108c63';
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
        $url = 'http://103.88.35.78:81/payOrderQry';

        $orderId = $notifyData['orderId'];
        $merchantId = '95';

        // 商户密钥后台查看
        $secret = 'c86c73a418224f73a74f7642e2108c63';
        $signStr = "orderId=" . ($orderId == null ? "" : $orderId) .
            "&merchantId=" . ($merchantId == null ? "" : $merchantId) .
            $secret;

        $data = [
            'merchantId'  =>  $merchantId,
            'orderId'  =>  $orderId,
        ];
        $data['sign'] = md5($signStr);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query LaobingPay  API notice:'.json_encode($result));
        if( ! $result['success']  ){
            Log::error('query LaobingPay  API Error:'.$result['desc']);
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
        $url = $this->pay($params,'11');
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
        Log::notice("LaobingPay notify data1".json_encode($notifyData));
//        {"orderId":"115918000379044968965","userId":"123","money":"200.0000","type":"2","sign":"51b430d1a7ae26683f0c37efb58a448d"}
        if(isset($notifyData['orderId']) ){
            if($this->query($notifyData)) {
                echo "ok";
                $data['out_trade_no'] = $notifyData['orderId'];
                return $data;
            }
        }
        echo "error";
        Log::error('LaobingPay API Error:'.json_encode($notifyData));
    }

}
