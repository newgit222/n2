<?php /*reated by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 20:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/**
 *
 * 飞恒支付
 * Class CsPay
 * @package app\api\service\payment
 */
class FeihengPay extends ApiPayment
{


    /**"
     * 生成签名
     * @param $data
     * @param $md5Key
     * @return string
     */
    public function getSign($data, $md5Key)
    {
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $val) {
            //空值不参与签名
            if ($val == '' || $key == 'sign') {
                continue;
            }
            $arg .= ($key . '=' . $val . '&');
        }
        $arg = $arg . 'paySecret=' . $md5Key;

        //签名数据转换为大写
        $sig_data = strtoupper(md5($arg));
        return $sig_data;

    }


    /**
     * 统一下单
     */
    private function pay($order, $type = 'wx_h5')
    {
        $pay_key = '465027c4ff8c48558a8730c4b0a442a4';
        //请求地址
        $form_url           = 'http://47.57.180.157/gateway/api/trade';
        $mchNo              = '68822021011010000118';
        $extra              = $data = array();
        $data["tradeType"]  = 'cs.pay.submit';//交易类型
        $data["version"]    = '2.0';//版本号
        $data["channel"]    = $type;//支付渠道
        $data["mchNo"]      = $mchNo;//商户号
        $data["body"]       = 'goods';//商品描述
        $data["mchOrderNo"] = $order['trade_no'];//商户支付订单号
        $data["amount"]     = strval(sprintf("%.2f", $order["amount"]) * 100);//交易金额，要转string类型
        $data["timePaid"]   = date("YmdHis");//订单提交支付时间
        //生成extra拓展参数
        $extra['notifyUrl']   = $this->config['notify_url'];//后台通知地址
        $extra['callbackUrl'] = $this->config['return_url'];//页面返回地址
        $extra['memberId']    = rand(0000, 9999);//买家用户标识
        $params               = array_merge($data, $extra);
        //签名
        $data['sign'] = $this->getSign($params, $pay_key);
        //extra转换为键值对
        $data['extra'] = json_encode($extra);
        $response      = self::curlPost($form_url, $data);
        $result        = json_decode($response, true);
        if ($result['resultCode'] == '0' && $result['status'] == '0') {
            return $result['codeUrl'];
        }
        throw new OrderException([
            'msg'     => 'Create FeihengPay API Error:' . $result['errMsg'],
            'errCode' => 200009
        ]);

    }


    /**
     * 微信扫码支付
     * @param $params
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, 'wx_h5');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'wx_h5');
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
        Log::notice("FeihengPay notify data" . json_encode($_POST));
        $input      = file_get_contents('php://input');
        $notifyData = json_decode($input, true);
        if ($notifyData['resultCode'] == '0' && $notifyData['status'] == '0') {
            echo "success";
            $data['out_trade_no'] = $notifyData['mchOrderNo'];
            return $data;
        }
    }


}

