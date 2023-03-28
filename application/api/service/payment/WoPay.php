<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/17
 * Time: 21:40
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class WoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='945'){

        $pay_memberid = "200622998";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "utzgeo1jqokeuyaicb63i1db3r1146x0";   //密钥
        $tjurl = "http://scan.worzhifu.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $type;   //银行编码
        //扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "123";
        $native['pay_productname'] ='goods';

        $result =  json_decode(self::curlPost($tjurl,($native)),true);
        if($result['status'] != 'success' )
        {
            Log::error('Create WoPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create WoPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['url'];

//        $native['request_post_url'] =$tjurl;
//        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($native);
    }

    public function getSign($native,$key){
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return strtoupper(md5($md5str . "key=" . $key));
    }


    public function query($notifyData){
        $url = 'http://scan.worzhifu.com/Pay_Trade_query.html';

        $Md5key = 'utzgeo1jqokeuyaicb63i1db3r1146x0';
        $native=array(
            'pay_memberid'=>'200622998',
            'pay_orderid'=>$notifyData['orderid']
        );

        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;

        $result =  json_decode(self::curlPost($url,$native),true);
        Log::notice('query WoPay  API notice:'.json_encode($result));
        if(  $result['returncode'] != '00' ){
            Log::error('query WoPay  API Error:'.$result['trade_state']);
            return false;
        }
        if($result['trade_state'] != 'SUCCESS' ){
            return false;
        }
        return true;
    }





    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'945');
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
        Log::notice("WoPay notify data1".json_encode($notifyData));
        if($notifyData['returncode'] == '00' ){
            if($this->query($notifyData)){
                echo "OK";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        Log::error("WoPay error data".json_encode($notifyData));

    }
}
