<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 16:31
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class MingdaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='904'){

        $pay_memberid = "201239186";   //商户ID
        $pay_orderid = $order['trade_no'];    //订单号
        $pay_amount = sprintf("%.2f",$order["amount"]);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = $this->config['return_url'];  //页面跳转返回地址
        $Md5key = "0uvgrlxioqw2xouymhf1xhy3ynnpdkgt";   //密钥
        $tjurl = "http://45.207.58.234/Pay_Index.html";   //提交地址
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

         $native['request_post_url'] =$tjurl;
        return "http://paofen.byfbqgi.cn//pay.php?".http_build_query($native);

/*var_dump($native);var_dump(self::curlPost($tjurl,$native));die();
        $result =  json_decode(self::curlPost($tjurl,$native,null,15),true);
        if(!isset($result['payurl'])  ||  !$result['payurl'] ){
            Log::error('Create TianchengswPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create TianchengswPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payurl'];*/
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

	return true;

        $url = 'http://www.oyqfwtt.cn/Pay_Trade_query.html';

        $Md5key = "z3fy6t7d6bm4kl42ibwop0qlcds5fzyd";
        $native=array(
            'pay_memberid'=>'10028',
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
        Log::notice('query TianchengswPay  API notice:'.json_encode($result));
        if(  $result['returncode'] != '00' ){
            Log::error('query TianchengswPay  API Error:'.$result['trade_state']);
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
        $url = $this->pay($params,'903');
        return [
            'request_url' => $url,
        ];
    }




 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'904');
        return [
            'request_url' => $url,
        ];
    }

 public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'902');
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
        $url = $this->pay($params,904);
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
        Log::notice("TianchengswPay notify data1".json_encode($notifyData));
        if($notifyData['returncode'] == '00' ){
            if($this->query($notifyData)){
                echo "OK";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        Log::error("TianchengswPay error data".json_encode($notifyData));

    }
}
