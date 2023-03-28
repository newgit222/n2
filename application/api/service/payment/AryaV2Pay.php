<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/5
 * Time: 20:39
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\migration\command\seed\Run;

class AryaV2Pay extends ApiPayment
{
    /*
     签名
     * @param $data  代签名参数
     * @param $key  秘钥
     * @return string
     */
    public static function getSign($data)
    {
        $md5str = '';
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $md5str=rtrim($md5str, "&");
        return md5($md5str.'78671a66b4ba4382b3a0541bd2ebe841');

    }





    /*
    * 统一下单
    *
    */
    private  function getAryaPayUnifiedOrder($order,$payType='alipay_wxred')
    {
        $unified['version'] = '3.0';
        $unified['method'] = 'Gt.online.interface';
        $unified['partner'] = '538857334588309504';
        $unified['banktype'] = $payType;
        $unified['paymoney'] = sprintf('%.0f',$order["amount"]);
        $unified['ordernumber'] =$order['trade_no'];
        $unified['callbackurl'] = $this->config['notify_url'];
        $unified['sign'] = $this->getSign($unified);
        $unified['hrefbackurl']= $this->config['notify_url'];;
        $unified['notreturnpage'] = true;
        $response = self::curlPost('http://woshixianyu.club/api/v1/getway',$unified);
        $response = json_decode($response,true);
//var_dump( $unified, $response);die();
        if($response['code'] !=0)
        {
            Log::error('Create AryaV2Pay API Error:'.($response['msg'] ? $response['msg']:""));
            throw new OrderException([
                'msg'   => 'Create AryaV2Pay API Error:'.($response['msg'] ? $response['msg']:""),
                'errCode'   => 200009
            ]);
        }
        return $response;
    }

    /**
     * 查询
     */
    public function query($data){
        $unified['version'] = '3.0';
        $unified['method'] = 'Gt.online.query';
        $unified['partner'] = '538857334588309504';
        $unified['ordernumber'] =$data['ordernumber'];
        $unified['sysnumber'] =$data['sysnumber'];
        $unified['sign'] = $this->getSign($unified);
        $response = self::curlPost('http://woshixianyu.club/api/v1/getway',$unified);
        $response = json_decode($response,true);
        Log::notice('query AryaV2Pay  API notice:'.json_encode($response));

        if($response['code'] != '0' ){
            Log::error('query AryaV2Pay  API Error:'.$response['msg']);
            return false;
        }
        if($response['data']['status'] != '1' ){
            return false;
        }
        if($response['data']['tradestate'] != '1' ){
            return false;
        }
        return true;
    }


    public function  test($params)
    {
        $data = $this->getAryaPayUnifiedOrder($params,'xydf');
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['data']['payUrl'],
        ];
    }



    /*
     * OC平台支付宝支付
     */
    public function ali_sdk($params)
    {
        //获取预下单
        $data = $this->getAryaPayUnifiedOrder($params,'xydf');
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['data']['payUrl'],  
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $data = $this->getAryaPayUnifiedOrder($params,'xydf');
        //todo  后面修改兼容所有pay_code支付测试
        return [
            'request_url' =>  $data['data']['qrcodeUrl'],
        ];
    }


    /*
     *
     * ARYA平台支付回调处理
     */
    public function notify()
    {
echo 'ok';
        Log::error('data from arya' .json_encode($_GET));
        $data = [];
        if(isset($_GET['ordernumber'])) {
            if($this->query($_GET)){
                $data["out_trade_no"] = $_GET['ordernumber'];
            }
        }
        return $data;
    }


    /*
     *
     *同步通知地址处理逻辑
     */
    public function  callback()
    {
        // $plat_order_no= $_POST['out_trade_no'];
        //todo 查询订单信息的同步通知地址

        return [
            'return_url' =>'http://www.baidu.com'
        ];
    }

}
