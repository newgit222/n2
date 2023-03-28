<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/27
 * Time: 20:08
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiaomiPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='926'){

        $pay_memberid = "10212";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "nccuarhlmimk74yie8ao67ltixnl981a";   //密钥
        $tjurl = "http://www.sdbqq1.cn/Pay_Index.html";   //提交地址
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

        $native["pay_md5sign"] = $this->createSign($Md5key,$native);
        $native['pay_productname'] ='goods';
        $native['request_post_url']= $tjurl;
        return "http://caishen.sviptb.com/pay.php?".http_build_query($native);
    }

    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }


    public function query($notifyData){
        $url = 'http://www.sdbqq1.cn/Pay_Trade_query.html';

        $Md5key = 'nccuarhlmimk74yie8ao67ltixnl981a';
        $native=array(
            'pay_memberid'=>'10212',
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
        Log::notice('query XiaomiPay  API notice:'.json_encode($result));
        if(  $result['returncode'] != '00' ){
            Log::error('query XiaomiPay  API Error:'.$result['trade_state']);
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
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'926');
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
        Log::notice("XiaomiPay notify data".$input);

        $notifyData = $_POST;
        Log::notice("XiaomiPay notify data1".json_encode($notifyData));
        if($notifyData['returncode'] == '00' ){
            if($this->query($notifyData)){
                echo "OK";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        Log::error("XiaomiPay error data".json_encode($notifyData));

    }
}