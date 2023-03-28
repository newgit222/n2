<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/1
 * Time: 1:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\logic\Orders;
use think\Log;

class GumaV5Pay extends ApiPayment
{

    const GUMA_ZFB = 2;
    protected $auth_key ='';
    protected $postUrl = '';

    protected $config = '';
    
    public function __construct($config = array())
    {
        $this->config = $config;
        $this->postUrl  = db('config')->where(['name'=>'thrid_url_gumapay'])->value('value');
        $this->auth_key= db('config')->where(['name'=>'auth_key'])->value('value');
    }
    /**
     * 统一下单
     */
    private function pay($params,$type= self::GUMA_ZFB){
        $data['money'] = sprintf('%.2f', $params['amount']);
        $data['code_type'] = $type;
        $data['trade_no'] = $params['trade_no'];
        $data['merchant_order_no'] = $params['out_trade_no'];
        $data['auth_key'] = $this->auth_key;
        $data['notify_url'] = $this->config['notify_url'];
        $data['admin_id'] = $this->config['remarks'];
//var_dump($this->postUrl);die();
        Log::notice('GumaV1Pay postUrl '.$this->postUrl.'  param'.json_encode($data));
        $response = $this->curlPost($this->postUrl,$data);
        Log::notice('GumaV1Pay  response'.$response);
        $data = json_decode($response, true);
        return $data;
    }
    public function test($params)
    {
        $data = $this->pay($params,3);
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

 public function yhk($params)
    {
        $data = $this->pay($params,3);
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
 public function guma_yhk($params)
    {
        $data = $this->pay($params,3);
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

 public function guma_bzk($params)
    {
        $data = $this->pay($params,3);
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

    public function guma_zfb($params)
    {
        $data = $this->pay($params);
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
        $data = $this->pay($params);
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
$notifyData=$_POST;
Log::notice("TianchengswPay notify data1".json_encode($notifyData));Log::notice("TianchengswPay notify data1".json_encode($notifyData));Log::notice("TianchengswPay notify data1".json_encode($notifyData));
        //跑分平台秘钥
        $pfPlatformAuthKey =  $_POST['pfPlatformAuthKey']?$_POST['pfPlatformAuthKey']:'';
        $this->checkSign($pfPlatformAuthKey);
        $data["out_trade_no"] =  $_POST['out_trade_no'];
        echo "SUCCESS";
        return $data;
    }
}
