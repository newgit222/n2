<?php /*IT JUST THINK ]
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

use app\common\logic\Orders;
use think\Log;

class DxPay extends ApiPayment
{

    const DX_VX = 1;

    const DX_ZFB = 2;

    const DX_XV_CODE = 1;

    const DX_ORDER_LENGTH = 20;

    private $pay_memberid = 10092;

    private $pay_notifyurl = 'http://linus.zhifu.com/api/notify/notify?channel=dxpay';

    private $pay_callbackurl = 'http://www.sina.com';

    private $pay_api_key = 'tzrnkx6rncd6t109c2vonhtz6t28qgr6';


    /*
     *本支付平台对应第三方支付通道配置
     * @return array
     */
    protected function platChannelPayCodes()
    {
        return [
            self::  DX_ZFB => 926,
            self::  DX_XV_CODE => 928,
        ];
    }

    /*
     *初始化
     * DxPay constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->postUrl = "https://www.tang918.com/Pay_Index.html";
    }

    /*
     * 支付宝通道支付
     * @param $params
     * @return array
     * @throws OrderException
     */
    public function dx_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::DX_ZFB;
        $data['trade_no'] = $params['trade_no'];

        $response = $this->postThird(self::DX_ZFB, $params['amount'], $params['trade_no']);
        if(is_not_json($response) && !empty($response))
        {
            return [
                'request_url' => $response,
            ];
        }
        $data = json_decode($response, true);
        if (isset($data["status"]) && $data["status"] == "error") {
            $data = [
                'errorCode' => '400003',
                'msg' => $data['msg']
            ];
            throw new OrderException($data);
        }

    }

    /*
      * 通道支付
      * @param $params
      * @return array
      * @throws OrderException
      */
    public function dx_vx($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::DX_XV_CODE;
        $data['trade_no'] = $params['trade_no'];

        $response = $this->postThird(self::DX_XV_CODE, $params['amount'], $params['trade_no']);
        if(is_not_json($response) && !empty($response))
        {
            return [
                'request_url' => $response,
            ];
        }
        $data = json_decode($response, true);
        if (isset($data["status"]) && $data["status"] == "error") {
            $data = [
                'errorCode' => '400003',
                'msg' => $data['msg']
            ];
            throw new OrderException($data);
        }

    }



    /*
     *生成指定长度的数字
     * @param int $length
     * @return string
     */
    protected function makeOrderNo($length = 15)
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {         //通过循环指定长度
            $randcode = mt_rand(0, 9);     //指定为数字
            $code .= $randcode;
        }
        return $code;
    }


    /*
     *发送请求到第三方
     * @param $type
     * @param $amount
     * @param $orderNo
     * @return mixed
     */
    public function postThird($type, $amount, $orderNo)
    {
        //商户号
        $pay_orderid = $orderNo;
        $pay_amount = $amount;    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->pay_notifyurl;   //服务端返回地址
        $pay_callbackurl = $this->pay_callbackurl;  //页面跳转返回地址
        $pay_bankcode = $this->platChannelPayCodes()[$type]; //支付宝扫码  //商户后台通道费率页 获取银行编码
        $native = array(
            "pay_memberid" => $this->pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        $native["pay_md5sign"] = $this->getSign($native);
        $result = $this->query($this->postUrl, $native);
        return $result;
    }


    /*
     * 本支付平台签名方式
     * @param $param
     * @return string
     */
    public function getSign($param)
    {
        ksort($param);
        $md5str = "";
        foreach ($param as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        return strtoupper(md5($md5str . "key=" . $this->pay_api_key));
    }


    /*
     *
     * Curl方式发起请求
     * 获取json
     */
    function query($url, $data = null, $header = null, $referer = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        if (stripos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);   // 从证书中检查SSL加密算法是否存在
        }

        //  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));   //避免data数据过长问题
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($ch);

        if ($error = curl_error($ch)) {
            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($ch));
            echo '=====error=====' . "\r\n";
            print_r(curl_error($ch));
            echo '=====$response=====' . "\r\n";
            print_r($res);
            die($error);
        }
        curl_close($ch);
        return $res;
    }


    /*
     *本支付服务回调地址
     * @return mixed
     */
    public function notify()
    {
        $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
        $md5key = $this->pay_api_key;
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                echo "success";
            }else{
                Log::error('单号faild' . json_encode($returnArray) . '超时处理');
            }
        }
        //解析得到本平台传送过去的订单号
        $data["out_trade_no"] = explode('K',$returnArray['orderid'])[1];
        return $data;

    }

    /*
     *本支付同步通知地址
     *$params 本支付平台通知参数
     */
    public function returnCallback($params)
    {

          //todo


        //重定向到当前订单得同步地址
           dd($params);
    }

}
