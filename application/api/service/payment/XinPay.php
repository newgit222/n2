<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 20:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XinPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order, $type = 'AlipayH5_LC')
    {
        $data['p1_merchantno'] = 'MER20230103145139638413';
        $data['p2_amount'] = sprintf("%.2f", $order["amount"]);
        $data['p3_orderno'] = $order['trade_no'];
        $data['p4_paytype'] = $type;
        $data['p5_reqtime'] = time();
        $data['p6_goodsname'] = 'goods';
        $data['p7_bankcode'] = '';
        $data['p8_returnurl'] = $this->config['return_url'];
        $data['p9_callbackurl'] = $this->config['notify_url'];
        $data['sign'] = $this->getSign($data);
        $url = 'https://api.happyxingfu.com/pay';
        $response = self::curlPost($url, $data);
        $result = json_decode($response, true);
        if ($result['rspcode'] != 'A0') {
            Log::error('Create XtePay API Error:' . $response);
            throw new OrderException([
                'msg' => 'Create XtePay API Error:' . $result['rspmsg'],
                'errCode' => 200009
            ]);
        }
        return $result['data'];
    }



    /**
     * @param $params
     * 微信
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'WechatScan');
        return [
            'request_url' => $url,
        ];
    }
 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'AlipayPersonal');
        return [
            'request_url' => $url,
        ];
    }



    public function guma_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'WechatScan');
        return [
            'request_url' => $url,
        ];
    }



    /**
     * @param $params
     * 微信
     */
    public function wxh5($params)
    {
        //获取预下单
        $url = $this->pay($params,'WechatH5_LC');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    public function getSign($data, $secret = 'c626d4cdaffc45d29ab926c7de9baa45')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        $string_a = $string_a . "&key=" . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_a);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }


    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        Log::notice("XtePay notify data" . json_encode($_REQUEST));
        if (1) {
            echo "SUCCESS";
            $data['out_trade_no'] = $_REQUEST['p3_orderno'];
            return $data;
        }
    }


}
