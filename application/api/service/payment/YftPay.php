<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *YftPay 支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class YftPay extends ApiPayment
{

    /*
     *获取签名
     * @param $data
     * @return string
     */

    public function getSign($data)
    {
        $data = array_filter($data);

        $salt = 'DDA5BE6CC6C9F1E35464F8168689BFD7';
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $md5str =$str . "key=" . $salt;
        //签名验证，查询数据是否被篡改
        return md5($md5str);
    }



    /*
    * Yft 统一下单
    *
    */
    private function getPayUnifiedOrder($order, $type = '0406')
    {
        $data = "";
        $data['version'] = '2.1';
        $data['orgNo'] = '0200100732';
        $data['custId'] = '20010500002348';
        $data['custOrderNo'] = $order['trade_no'];
        $data['tranType'] = $type;
        $data['payAmt'] = $order["amount"]*100;
        $data['backUrl'] = $this->config['notify_url'];
        $data['goodsName'] = 'goods';
        $data['sign'] = $this->getSign($data);
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Accept: application/json",
        );
        $request_url = "http://120.78.178.248:8086/mpcctp/cashier/pay.ac";
        $response = self::curlPost($request_url, $data, [CURLOPT_HTTPHEADER=>$headers]);
        $response = json_decode($response, true);
        if ($response['code'] != '000000') {
            Log::error('Create YftPay API Error:' . ($response['msg'] ? $response['msg'] : ""));
            throw new OrderException([
                'msg' => 'Create YftPay API Error:' . ($response['msg'] ? $response['msg'] : ""),
                'errCode' => 200009
            ]);
        }
        return $response;
    }


    /*
     *微信h5产品
     * @param $params
     * @return array
     * @throws OrderException
     */
    public function h5_vx($params)
    {
        //获取预下单
        $response = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $response['busContent'],
        ];
    }



    /*
     * Luckpay平台支付回调处理
     */
    public function notify()
    {
        try{
            $notifyData = $_POST;
            Log::notice("yftpay notify data".json_encode($_POST));
            $sign=$notifyData['sign'];
            unset($notifyData['sign']);
            $mysign = $this->getSign($notifyData);
            if(strtolower($mysign) == strtolower($sign) && $notifyData['ordStatus']=='01'){
                //处理业务逻辑
                echo 'SC000000';
                $data["out_trade_no"] = $notifyData['custOrderNo'];
                return $data;
            }
            throw new OrderException([
                'msg' => 'Create YftPay API Error:',
                'errCode' => 200009
            ]);

        }catch (\Exception $e){
            Log::error('YftPay API Error:'.$e->getMessage());
        }
    }

}
