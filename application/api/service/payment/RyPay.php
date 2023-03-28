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

class RyPay extends ApiPayment
{
    //private $appid = 'gpu87219935206';
    private $appid = 'gpu278136450573';
   // private $secret = '09b4fc5c676e956e8f507468720a0db4';
    private $secret = 'fa68cfe6c8e8a9d96c98c2f475da9258';

    private $postUrl = "http://api.fumengs.com/api/order/unified";

    const BACK_CODE_ZHB_H5 = 901;

       /*
        * 容易付支付宝H5通道支付
        * @param $params
        * @return array
        * @throws OrderException
        */
    public function guma_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::BACK_CODE_ZHB_H5;
        $data['trade_no'] = $params['trade_no'];
        $response = $this->postThird(self::BACK_CODE_ZHB_H5, $params['amount'], $params);
        $response = json_decode($response,true);
        if($response['status'] == 1)
        {
            return [
                'request_url' => $response['data']['redirect_url'],
            ];
        }
        $data = [
            'errorCode' => '400003',
            'msg' => $response['message']
        ];
        throw new OrderException($data);
    }


    /*
      * 容易付支付宝H5通道支付
      * @param $params
      * @return array
      * @throws OrderException
      */
    public function h5_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::BACK_CODE_ZHB_H5;
        $data['trade_no'] = $params['trade_no'];
        $response = $this->postThird(self::BACK_CODE_ZHB_H5, $params['amount'], $params);
        $response = json_decode($response,true);
        if($response['status'] == 1)
        {
            return [
                'request_url' => $response['data']['redirect_url'],
            ];
        }
        $data = [
            'errorCode' => '400003',
            'msg' => $response['message']
        ];
        throw new OrderException($data);
    }

    /*
     *发送请求到第三方
     * @param $type
     * @param $amount
     * @param $orderNo
     * @return mixed
     */
    public function postThird($type, $amount, $orderInfo)
    {
        $native = array(
            "amount" => $amount*100,
            "appid" => $this->appid,
            "bank_code" =>$type,
            "notify_url" => $this->config['notify_url'],
            "order_no" =>$orderInfo['trade_no'],
            "product_name" =>$orderInfo['subject'],
            "return_url" =>$this->config['return_url'],
            "secret" =>$this->secret,
        );

        $native["sign"] = $this->getSign($native);

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
        $param2 = [];
        foreach($param as $k => $v){
            $param2[] = $k.'='.$v;
        }
        return strtolower(md5(implode('&',$param2)));
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
        Log::error('Post data' . json_encode($_POST). 'Ry');
        $secret= $this->secret;
        if($this->request_param($_POST,$secret)['sign']==$_POST['sign'])
        {
            if($_POST['pay_status']==1)
            {
                echo 'SUCCESS';
                $data["out_trade_no"] =  $_POST['order_no'];
                return $data;
            }
            else
            {
                echo "faild";die();
            }
        }
        echo "faild";die();
    }

    function request_param($param,$secret){
        $this->clear_null($param);
        if(isset($param['is_jump']))
        {
            unset($param['is_jump']);
        }
        unset($param['sign']);
        $param['secret'] = $secret;
        ksort($param);
        $param2 = [];
        foreach($param as $k => $v){
            $param2[] = $k.'='.$v;
        }
        $param['sign'] = strtolower(md5(implode('&',$param2)));
        return $param;
    }

    /**
     * @param string $data
     * 清楚数据内的null
     */
    function clear_null(&$data = ''){
        if($data === null || $data === false){
            $data = '';
        }
        if(is_array($data) && !empty($data)){
            foreach($data as &$v){
                if($v === null || $v === false){
                    $v = '';
                }else if(is_array($v)){
                    clear_null($v);
                }else if(is_string($v) && stripos($v,'.') === 0){
//				$v = '0'.$v;
                }
            }
        }
    }

    public function verify($postdata)
    {
        $token    = $this->secret;
        $postdata = json_decode($postdata,true);

        $sign = md5($postdata['user_order_no'] . $postdata['orderno'] . $postdata['tradeno'] . $postdata['price'] . $postdata['realprice'] . $token);
        if($postdata['sign'] == $sign)
        {
            return true;
        }
        return false;
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
