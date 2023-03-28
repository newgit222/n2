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

class AiPay extends ApiPayment
{

    const DX_VX = 1;

    const DX_ZFB = 915;

    const DX_XV_CODE = 915;

    const DX_ORDER_LENGTH = 20;

    private $pay_memberid = 10079;

    private $pay_api_key = 'gdnl5hsvphewuoy2tipals3g7jmkysks';

    /*
      * 通道支付
      * @param $params
      * @return array
      * @throws OrderException
      */
    public function h5_zfb($params)
    {
        //商户号
        $pay_orderid = $params['trade_no'];
        $pay_amount = sprintf('%.2f', $params['amount']);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $this->config['notify_url'];   //服务端返回地址
        $pay_callbackurl = "/";  //页面跳转返回地址
        $pay_bankcode = self::DX_ZFB; //支付宝扫码  //商户后台通道费率页 获取银行编码
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
        $native['request_post_url'] = "http://www.jsydxr.com/Pay_Index.html";;
        $url = "http://aa.sviptb.com/pay.php?".http_build_query($native);
        return [
            'request_url' => $url,
        ];

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
     *本支付服务回调地址
     * @return mixed
     */
    public function notify()
    {
        Log::error('Post data from AI' . json_encode($_REQUEST));
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
                $data["out_trade_no"] = $returnArray['orderid'];
                return $data;
            }else{
                Log::error('单号faild' . json_encode($returnArray) . '超时处理');
            }
        }
    }

    /*
     *本支付同步通知地址
     *$params 本支付平台通知参数
     */
    public function returnCallback($params)
    {
           dd($params);
    }

}
