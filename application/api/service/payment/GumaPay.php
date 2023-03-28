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

class GumaPay extends ApiPayment
{

    const GUMA_VX = 1;

    const GUMA_ZFB = 2;

    const GUMA_YHK = 3;

    const WAP_VX = 8;

    const WAP_ZFB = 6;

    const JHM = 4;

    protected $auth_key ='';

    public function __construct()
    {
        $this->postUrl  = db('config')->where(['name'=>'thrid_url_gumapay'])->value('value').'/api/api/addOrder';
        $this->auth_key= db('config')->where(['name'=>'auth_key'])->value('value');
    }

    public function baifutong($params){
        //
        $GemaOrder = new \app\gemapay\logic\GemaOrder();
        $amount = sprintf('%.2f', $params['amount']);
        $result = $GemaOrder->createOrder($amount, $params['trade_no']);
        if($result["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $GemaOrder->getPaylink($result["id"]),
        ];
    }






    public function guma_vx($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::GUMA_VX;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);

        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $data["data"]["pay_url"]
          //  'request_url' =>  "http://".$_SERVER['HTTP_HOST']."/vxpay.html?amount=". $data["data"]["reall_pay_amount"]."&iamge_url=".$data["data"]["pay_image"]."&trade_no=".$params['trade_no'],
        ];
    }

    public function wap_vx($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::WAP_VX;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);
        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $data["data"]["pay_url"]
        ];
    }


    public function wap_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::WAP_ZFB;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);

        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }
        return [
            'request_url' => $data["data"]["pay_url"]
        ];
    }



    public function guma_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::GUMA_ZFB;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);
        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }


        return [
            'request_url' =>  $data["data"]["pay_url"]
        ];
    }
 public function test($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::GUMA_ZFB;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);
        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }


        return [
            'request_url' =>  $data["data"]["pay_url"]
        ];
    }

public function h5_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::GUMA_ZFB;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $response = $this->curlPost($this->postUrl,$data);
        $data = json_decode($response, true);
        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }


        return [
            'request_url' =>  $data["data"]["pay_url"]
        ];
    }

    public function jhm($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::JHM;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;

        $response = $this->curlPost($this->postUrl,$data);

        $data = json_decode($response, true);

        if($data["code"] == 0)
        {
            $data = [
                'errorCode' => '400003',
                'msg' => '该金额没有可用的二维码'
            ];
            throw new OrderException($data);
        }

        return [
            'request_url' =>  $data["data"]["pay_url"]
        ];
    }




    /*
     *验签
     * @throws OrderException
     */
    public function checkSign($pfPlatformAuthKey)
    {

        if(strtolower($pfPlatformAuthKey)!=strtolower($this->auth_key))
        {
            throw new OrderException([
                'msg'   => 'ORDER NET PAYED!',
                'errCode'   => 200011
            ]);
        }
        return true;
    }



    public function notify(){

        //跑分平台秘钥
        $pfPlatformAuthKey =  $_POST['pfPlatformAuthKey']?$_POST['pfPlatformAuthKey']:'';
        $this->checkSign($pfPlatformAuthKey);
        $data["out_trade_no"] =  $_POST['out_trade_no'];
        return $data;
    }
}
