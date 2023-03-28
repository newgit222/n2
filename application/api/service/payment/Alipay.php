<?php
/**
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\api\service\payment;

use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\library\exception\SignatureException;
use think\Log;

class Alipay extends ApiPayment
{
    /**
     * 支付宝扫码支付
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $order
     *
     * @return array
     * @throws OrderException
     * @throws SignatureException
     */
   /* public function ali_qr($order){
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=> $order['trade_no'],
            'product_code'=> 'FAST_INSTANT_TRADE_PAY',
            'total_amount'=> sprintf("%.2f", $order['amount']), //支付宝交易范围  [0.01,100000000]
            'subject'=> $order['subject'],  //订单标题
            'timeout_express'=>'10m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );
        $result = self::getGenerateAlipayOrder($requestConfigs, 'alipay.trade.page.precreate');
        echo 3;die();
        var_dump($result);die();
        //        // alipay.trade.page.pay
        $result = self::getGenerateAlipayOrder($requestConfigs, 'alipay.trade.wap.pay');
        return [
            'request_url' => $result
        ];
    }*/

    /**
     * @param $order
     * @return array  支付宝app支付
     */
   public function app($order){
       $requestConfigs = array(
           'out_trade_no'=> $order['trade_no'],
           'total_amount'=> sprintf("%.2f", $order['amount']), //支付宝交易范围  [0.01,100000000]
           'subject'=> $order['subject'],  //订单标题
           'product_code'=> 'QUICK_MSECURITY_PAY',  //product_code
           'timeout_express'=>'10m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
       );

       vendor('alipay.aop.AopClient');
       vendor('alipay.aop.request.AlipayTradeAppPayRequest');
       $aop = new \AopClient();
       $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
       $aop->appId = $this->config['app_id'];
       $aop->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCqWxnsQmHpEYOfrcAwKjoITUrbRCazRATmzuBnloNuvzNQDXWWQKOtkmGZkTL2DagmxgalUVAXcj55bt83D+uZhA8E8cpDZR+qNp/OXnyTncox+VvJ3hTGdwP7VolulUfXBCiPr1h79hlMVt98+TyVxryVbWU05HHJbZKqRyYLwTZANRXnjmt3AThJ0lWs+1s+4qsjPeC0gecVC/tUoqsBdw4N1DZQrVV0pM722YTQGCsOM8o1Uw1aFGTEhuIIngmCcs7qg5ayTfoWf+VQYMtbw4gvSUE+cexKOfR0XryKZ6qESeAhyRP49N2p1AUGrAlOSxicdqIEAWLSxWDV+gtrAgMBAAECggEAH2M3rIMynQnAEaymy3kMRjlPgITXCJKQwKH/ULa3srEB2E8SikOQpMtitjO9iv5LLBGyacVIl3lSL3eRIwkI5LkjN0sBdFdudSMpYJGiLGSXO4vxMIl9lG+bSTTRj5Frsh8vBgJNsFFQqfMbrGATnJkIaBPG4O7yVCWfL0Z6qRF8W+8DZ+O5X2tfpKqfszbl3LTD3dsRDc0yw56j7pXEUXSZ9ljAh61hmbMXkkUqTChXoSWABiY+mk3BmrltJtKz5S2LybvUGhxo3IF/Kbu3ALftoU1Y+jBC39jR4rxCNfPNEc+BwELsK5vlGWbipBpwILZu2JQNJF25wq/BN2TEOQKBgQDW46OoRr8rUi6mSxqIRUsRm4TGqZ3HKhImqNkahw5Wh/XHvPum+plfe41DulzQzzROlru7oKO8KTk66eTA0jdLsdenRxDL/y1w1kY2csssTLIbY/tANHdHVVDTZL6j1e4pKAqtkixf01lXU1MjNlX2ntupilBukaJpsCbGHevoZwKBgQDK8mha44Eu/URlbhSMV9bD+a7o73CXM2xIrynglhT/9qJL9NG285SvtjW6ZoNDa5SNrI2X5+9Odyvwytv33ZgaW+hIv4wxoYpvUvWSY1JmKvhF/ePuLhmo6z/je2hGnxqcJS0UgMy25ElQavvYkgvoWBNz7PcT09WBG82b0XmyXQKBgClMOGCXsBewRJza5C96ObIZlEvlvxfOowxg+NBbUksY+Ag57pcppB4wjXaNs1bB64iK+WtWtQDtXz1ORBM2kz/t3sccJkM+OEpuM7I1H4zU9InFDj1Jl/7Si6UEwIqWC55HPr4a0IJeCNZ6ggjLYXvzDb4ogcquHGt5WgpLJ9JTAoGBAMYHhZZyemKslanXoUtUhucR4v++bIHNlrLR4S4ZpIIjWjh9uokn44UVahXp49nMxtb7ceqxUcyh8olymPOjEcFevykFZ7oiI9/+9ksSiZvSABLj7B5Ec5tleiDyEU0gASouCHACQj+QQI2s5vRrhh0AnHK43ltFkAb8Z0PNQk+tAoGAI8J8Manh1F17SIlWH39c33DYLiTgI52/Ax7E7OGtam7RS8MqigIpu2Dc46JmnVdCPJxiaFCWKcGlmy54qv6w8TyxshuJW1ysPwMGUVHtNskCH/F0UKKmy/s0cbGiYg2Q5U2YdWQCGYXNztZOxdsz0noc6CC4ObS7+AMkWBYQAYs=';
       $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAybTP3tZXH0FDJjes4TK1bI7obIK80CbWduVC9poLMNCyVfRJ3wubg1lskfD8fWFsoeGHogvjqorOlKiK3RGQaEkMOXIn4WTJ0acOImpxUmogIYEv1Z0dDv5rb2EqYhe8W/ykmPbMlCSSzk9o5B+ro8hckDSoQumFGUeUYno7Km6m50wjX/ygau7jH5fAfouQFTlov8gHyF+543BNQBqcP5PnOBDccFtwL1e5CmhtdzJskOZdKW8sKWFrO9dtoYQ/eEv0WvPYe+bvqREzWmfaaJQC1QIbqOgdY0DvcN8fFcxWZAVT473rqo57cowOSEalcWWGhYgSssqJl8CXslKL/QIDAQAB';
       $aop->apiVersion = '1.0';
       $aop->signType = 'RSA2';
       $aop->postCharset='utf8';
       $aop->format='json';
       $aop->format='json';
       $request = new \AlipayTradeAppPayRequest ();
       $request->setNotifyUrl($this->config['notify_url']);
       $request->setBizContent(json_encode($requestConfigs));
       $result = $aop->sdkExecute ( $request);
       return [
           'request_url' => $result
       ];
   }





    public function ali_qr($order){
        //alipay.trade.page.pay  product_code
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=> $order['trade_no'],
            'total_amount'=> sprintf("%.2f", $order['amount']), //支付宝交易范围  [0.01,100000000]
            'subject'=> $order['subject'],  //订单标题
            'product_code'=> 'FAST_INSTANT_TRADE_PAY',  //product_code
            'timeout_express'=>'10m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );
        $result = self::getGenerateAlipayOrder($requestConfigs, 'alipay.trade.page.pay');
    }

    public function wap_zfb($order){
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=> $order['trade_no'],
            'product_code'=> 'FAST_INSTANT_TRADE_PAY',
            'total_amount'=> sprintf("%.2f", $order['amount']), //支付宝交易范围  [0.01,100000000]
            'subject'=> $order['subject'],  //订单标题
            'timeout_express'=>'10m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );

        //        // alipay.trade.page.pay
        $result = self::getGenerateAlipayOrder($requestConfigs, 'alipay.trade.page.pay');

        return [
            'request_url' => $result
        ];
    }

    public function wap_zfb1($order){
        //请求参数

        $requestConfigs = array(
            'out_trade_no'=> $order['trade_no'],
            'product_code'=> 'FAST_INSTANT_TRADE_PAY',
            'total_amount'=> sprintf("%.2f", $order['amount']), //支付宝交易范围  [0.01,100000000]
            'subject'=> $order['subject'],  //订单标题
            'timeout_express'=>'10m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );
        //        // alipay.trade.page.pay
        $result = self::getGenerateAlipayOrder($requestConfigs, 'alipay.trade.wap.pay');
        return [
            'request_url' => $result
        ];
    }

    /**
     * 异步回调地址 /默认按类名称  【 https://pay.iredcap.cn/notify/alipay 】
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     * @return array
     * @throws OrderException
     * @throws SignatureException
     */
    public function notify(){
        return $this->verifyAliOrderNotify();
    }

    /**
     * 同步地址 【待测】
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     * @return mixed
     */
    public function callback(){
        //1.拿out_trade_no
        $out_trade_no = request()->param('out_trade_no');
        //2.查订单获取  商户return_url
        $order = self::getOrder($out_trade_no);
        //3.返回参数跳转
        return $order;
    }

    /******************************支付宝******************************************/

    /**
     * 支付宝统一
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $requestConfigs
     * @param string $trade_type
     *
     * @return mixed
     * @throws OrderException
     * @throws SignatureException
     */
    private function getGenerateAlipayOrder($requestConfigs, $trade_type = 'alipay.trade.pay'){
        $commonConfigs = array(

            'app_id' => $this->config['app_id'],
            'method' => $trade_type,             //接口名称
            'format' => 'JSON',
            'charset'=> 'utf-8',
            'sign_type'=>'RSA2',
            'version'=>'1.0',
            'timestamp'=> date('Y-m-d H:i:s'),
            'notify_url' => $this->config['notify_url'],
            'biz_content'=>json_encode($requestConfigs),
        );
var_dump($this->config);die();
        //签名
        $commonConfigs["sign"] = $this->generateAlipaySign($commonConfigs, $commonConfigs['sign_type']);
        $url = "https://openapi.alipay.com/gateway.do?".http_build_query($commonConfigs);
        $commonConfigs["request_url"] = "https://openapi.alipay.com/gateway.do";
        //请求
        Log::notice('Alipay API Response : '. json_encode($commonConfigs));
//        return $url;
//        $response = $this->curlZhongzhuanPost($commonConfigs);
        return $url;

//        $response = $this->curlPost("https://openapi.alipay.com/gateway.do",json_encode($commonConfigs));
//        var_dump($response); die();
        Log::notice('Alipay API Response1 : '. json_encode($response));
        $response = json_decode($response,true);

        //读数据
        $result = $response['alipay_trade_precreate_response'];
        if (!isset($result['code']) || $result['code'] != '10000') {
            Log::error('Create Alipay API Error:'. $result['msg'].' : '.$result['sub_msg']);
            throw new OrderException([
                'msg'   => 'Create Alipay API Error:'. $result['msg'].' : '.$result['sub_msg'],
                'errCode'   => 200009
            ]);
        }

        //数据返回
        return $result;
    }

    /**
     * 回调验签
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     * @return array
     * @throws OrderException
     * @throws SignatureException
     */
    public function verifyAliOrderNotify(){

        $response = convertUrlArray(file_get_contents('php://input')); //支付宝异步通知POST返回数据
        Log::notice('Alipay Sign response:'.file_get_contents('php://input'));
        //转码
        $response = self::encoding($response,'utf-8', $response['charset'] ? $response['charset']: 'gb2312');
        //读订单对应的支付渠道配置
        $this->config = self::getOrderPayConfig($response['out_trade_no']);
        //验签
        $result = $this->verify($this->getSignContent($response, true), $response['sign'], $response['sign_type']);
        if (!$result) {
            Log::error('Verify Alipay Sign Error: 请检查支付宝配置是否正确');
            throw new OrderException([
                'msg'   => 'Verify Alipay Sign Error. [Check RSA Public Key Configuration]',
                'errCode'   => 200010
            ]);
        }
        echo 'success';

        if($response['trade_status'] != "TRADE_SUCCESS")
        {
            throw new OrderException([
                'msg'   => 'ALI ORDER NET PAYED!',
                'errCode'   => 200011
            ]);
        }

        return $response;
    }

    /**
     * 支付宝签名
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $params
     * @param $signType
     *
     * @return string
     * @throws SignatureException
     */
    protected function generateAlipaySign($params, $signType){

        return $this->sign($this->getSignContent($params), $signType);
    }

    /**
     * 签名
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @param string $signType
     *
     * @return string
     * @throws SignatureException
     */
    protected function sign($data, $signType = "RSA") {
//        $priKey = $this->config['private_key'];

        $priKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCqWxnsQmHpEYOf
rcAwKjoITUrbRCazRATmzuBnloNuvzNQDXWWQKOtkmGZkTL2DagmxgalUVAXcj55
bt83D+uZhA8E8cpDZR+qNp/OXnyTncox+VvJ3hTGdwP7VolulUfXBCiPr1h79hlM
Vt98+TyVxryVbWU05HHJbZKqRyYLwTZANRXnjmt3AThJ0lWs+1s+4qsjPeC0gecV
C/tUoqsBdw4N1DZQrVV0pM722YTQGCsOM8o1Uw1aFGTEhuIIngmCcs7qg5ayTfoW
f+VQYMtbw4gvSUE+cexKOfR0XryKZ6qESeAhyRP49N2p1AUGrAlOSxicdqIEAWLS
xWDV+gtrAgMBAAECggEAH2M3rIMynQnAEaymy3kMRjlPgITXCJKQwKH/ULa3srEB
2E8SikOQpMtitjO9iv5LLBGyacVIl3lSL3eRIwkI5LkjN0sBdFdudSMpYJGiLGSX
O4vxMIl9lG+bSTTRj5Frsh8vBgJNsFFQqfMbrGATnJkIaBPG4O7yVCWfL0Z6qRF8
W+8DZ+O5X2tfpKqfszbl3LTD3dsRDc0yw56j7pXEUXSZ9ljAh61hmbMXkkUqTChX
oSWABiY+mk3BmrltJtKz5S2LybvUGhxo3IF/Kbu3ALftoU1Y+jBC39jR4rxCNfPN
Ec+BwELsK5vlGWbipBpwILZu2JQNJF25wq/BN2TEOQKBgQDW46OoRr8rUi6mSxqI
RUsRm4TGqZ3HKhImqNkahw5Wh/XHvPum+plfe41DulzQzzROlru7oKO8KTk66eTA
0jdLsdenRxDL/y1w1kY2csssTLIbY/tANHdHVVDTZL6j1e4pKAqtkixf01lXU1Mj
NlX2ntupilBukaJpsCbGHevoZwKBgQDK8mha44Eu/URlbhSMV9bD+a7o73CXM2xI
rynglhT/9qJL9NG285SvtjW6ZoNDa5SNrI2X5+9Odyvwytv33ZgaW+hIv4wxoYpv
UvWSY1JmKvhF/ePuLhmo6z/je2hGnxqcJS0UgMy25ElQavvYkgvoWBNz7PcT09WB
G82b0XmyXQKBgClMOGCXsBewRJza5C96ObIZlEvlvxfOowxg+NBbUksY+Ag57pcp
pB4wjXaNs1bB64iK+WtWtQDtXz1ORBM2kz/t3sccJkM+OEpuM7I1H4zU9InFDj1J
l/7Si6UEwIqWC55HPr4a0IJeCNZ6ggjLYXvzDb4ogcquHGt5WgpLJ9JTAoGBAMYH
hZZyemKslanXoUtUhucR4v++bIHNlrLR4S4ZpIIjWjh9uokn44UVahXp49nMxtb7
ceqxUcyh8olymPOjEcFevykFZ7oiI9/+9ksSiZvSABLj7B5Ec5tleiDyEU0gASou
CHACQj+QQI2s5vRrhh0AnHK43ltFkAb8Z0PNQk+tAoGAI8J8Manh1F17SIlWH39c
33DYLiTgI52/Ax7E7OGtam7RS8MqigIpu2Dc46JmnVdCPJxiaFCWKcGlmy54qv6w
8TyxshuJW1ysPwMGUVHtNskCH/F0UKKmy/s0cbGiYg2Q5U2YdWQCGYXNztZOxdsz
0noc6CC4ObS7+AMkWBYQAYs=';
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
//            wordwrap($priKey, 64, "\n", true) .
            $priKey.
            "\n-----END RSA PRIVATE KEY-----";

        try{
            if ("RSA2" == $signType) {
                openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
            } else {
                openssl_sign($data, $sign, $res);
            }
        }catch (\Exception $e){
            Log::error('Verify Alipay Sign Error: 支付宝私钥格式错误，请检查RSA私钥配置');
            throw new SignatureException([
                'msg'   => 'Verify Alipay Sign Error. [Alipay Private Key Format Error].'.$e->getMessage(),
                'errCode'   => 10009
            ]);
        }

        $sign = base64_encode($sign);

        return $sign;
    }

    /**
     * 验证
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @param $sign
     * @param string $signType
     *
     * @return bool
     * @throws SignatureException
     */
    protected function verify($data, $sign, $signType = 'RSA') {
//        $pubKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg2rltdobZf5k7gNGkTQdIIsPxmlOokNnRVp9hCWw15+rLOHldOFGNUZNutMF5knTb/UiyMtQxYGJoduJXgo/wpeZZexDQEIzESwjdzGMIEXW4n1/PXG2E86+uVmRUjPpDZt38eGpbokv27vehvyiMs1zmkpfoS19l+oI2FoEazQ6+YC8jHkh85NdHlAr03QNXkoXTWo2ZZm0gd0CvZtaM0fHY4YLgTh5mkC45YFEp4QaC4WcEOqbUy73F9PSbj5MJo6dHigRuCk0wkhgw5lqewEtjEs+ObuCDKLjSQAt7ppt1+M50z3SYEY30JkWCaJ+rsxILTpst1WF2ZOmcxguZQIDAQAB";
        $pubKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAybTP3tZXH0FDJjes4TK1bI7obIK80CbWduVC9poLMNCyVfRJ3wubg1lskfD8fWFsoeGHogvjqorOlKiK3RGQaEkMOXIn4WTJ0acOImpxUmogIYEv1Z0dDv5rb2EqYhe8W/ykmPbMlCSSzk9o5B+ro8hckDSoQumFGUeUYno7Km6m50wjX/ygau7jH5fAfouQFTlov8gHyF+543BNQBqcP5PnOBDccFtwL1e5CmhtdzJskOZdKW8sKWFrO9dtoYQ/eEv0WvPYe+bvqREzWmfaaJQC1QIbqOgdY0DvcN8fFcxWZAVT473rqo57cowOSEalcWWGhYgSssqJl8CXslKL/QIDAQAB";
        Log::error('Verify Alipay data'.$data." signType=".$signType." and sign:".$sign);
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        try{
            //调用openssl内置方法验签，返回bool值
            if ("RSA2" == $signType) {
                $result = (bool)openssl_verify($data, base64_decode($sign), $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
            } else {
                $result = (bool)openssl_verify($data, base64_decode($sign), $res);
            }
        }catch (\Exception $e){
            Log::error('Verify Alipay Sign Error: 支付宝公钥格式错误，请检查RSA公钥配置');
            throw new SignatureException([
                'msg'   => 'Verify Alipay Sign Error. [Alipay Public Key Format Error].',
                'errCode'   => 10009
            ]);
        }

        return $result;
    }

    /**
     * 校验$value是否非空
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $value
     *
     * @return bool
     */
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }

    /**
     * 签名排序
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $params
     * @param $verify
     *
     * @return string
     */
    public function getSignContent($params, $verify =false) {

        $data = self::encoding($params, $params['charset'] ? $params['charset'] : 'gb2312', 'utf-8');

        ksort($data);

        $stringToBeSigned = '';

        foreach ($data as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($verify && $k != 'sign' && $k != 'sign_type' && $k != 'channel') {
                    $stringToBeSigned .= $k . '=' . $v . '&';
                }
                if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                    $stringToBeSigned .= $k . '=' . $v . '&';
                }
            }
        }

        return trim($stringToBeSigned, '&');
    }


    /**
     * 编码转换
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $array
     * @param $to_encoding
     * @param string $from_encoding
     *
     * @return array
     */
    public static function encoding($array, $to_encoding, $from_encoding = 'gb2312')
    {
        $encoded = [];
        foreach ($array as $key => $value) {
            $encoded[$key] = is_array($value) ? self::encoding($value, $to_encoding, $from_encoding) :
                mb_convert_encoding(urldecode($value), $to_encoding, $from_encoding);
        }
        return $encoded;
    }

}
