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

    public function __construct()
    {
        $this->postUrl = "http://192.168.254.105:90/gemapay-api-addorder";
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
            'request_url' =>  "http://".$_SERVER['HTTP_HOST']."/vxpay.html?amount=". $data["data"]["reall_pay_amount"]."&iamge_url=".$data["data"]["pay_image"]."&trade_no=".$params['trade_no'],
        ];
    }

    public function guma_zfb($params)
    {
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = self::GUMA_ZFB;
        $data['trade_no'] = $params['trade_no'];
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
            'request_url' =>  "http://".$_SERVER['HTTP_HOST']."/index/index/pay?"."trade_no=".
                $params['trade_no']."&amount=".$data["data"]["reall_pay_amount"]."&image_url=".$data["data"]["pay_image
                
                "],
        ];
    }

   /* public function guma_vx()
    {

    }
    public function guma_vx()
    {

    }
    public function guma_vx()
    {

    }*/


    public function notify(){
        $data["out_trade_no"] =  $_POST['out_trade_no'];
        return $data;
    }
}
