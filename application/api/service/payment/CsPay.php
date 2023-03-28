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

class CsPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ALIPAY'){
        $data['merchantNo'] = 'PC00000108L';
        $data['payType'] = $type;
        $data['orderNo'] = $order['trade_no'];
        $data['amount'] = sprintf("%.2f",$order["amount"])*100;
        $data['userID'] =  $order['trade_no'];
        $data['clientIP'] = request()->ip();
        $data['productName'] = 'goods';
        $data['notifyUrl'] =  $this->config['notify_url'];
        $data['pageUrl'] = 'http://www.baidu.com';
        $data['sign'] = $this->getSign($data);
        $url = 'http://18.138.224.113/api/pay/merorder.do';
        $response = self::curlPost($url, $data);
        $result = json_decode($response,true);
        if($result['retCode'] != '00000')
        {
            Log::error('Create CsPay API Error:'.$response);
            throw new OrderException([
                'msg'   => 'Create CsPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['qrCode'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'ALIPAY');
        return [
            'request_url' => $url,
        ];
    }
  public function guma_yhk($params)

  {
        //获取预下单
        $url = $this->pay($params,'ALIPAY');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ALIPAY');
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
    public  function getSign($data, $secret='1e5194c6506550384b0eb86c944db759')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        $string_a = $string_a."&key=".$secret;
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
        Log::notice("CsPay notify data".json_encode($_POST));
        //notify message
        $notifyData = base64_decode($_POST['message']);
        $notifyData =  json_decode($notifyData,true);

        if($notifyData['status'] == 1 ){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderNo'];
            return $data;
        }
    }



}
