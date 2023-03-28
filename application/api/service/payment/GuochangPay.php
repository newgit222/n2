<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 * OC支付平台第三方出码
 * Class Ocpay
 * @package app\api\service\payment
 */
class GuochangPay extends ApiPayment
{
    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $type=32)
    {
        $param = array();
        $param['uid'] = '8fYY4LS5';
        $param['price'] = sprintf("%.2f", $order['amount']); //保留两位小数
        $param['paytype'] = $type; //支付宝
// $param['paytype'] = 2; //微信
        $param['notify_url'] = $this->config['notify_url'];
        $param['return_url'] = "http://www.a.com";
        $param['user_order_no'] = $order['trade_no']; //订单号必须唯一和数字

//获取sign签名串
        $param['sign'] = $this->getSign($param);

//以下参数不参与加密
        $param['note'] = 'my note';
        $param['cuid'] = 'user';//可选参数 一般填写用户名 邮箱 或者需要的主键ID
        $param['tm']	= date('Y-m-d H:i:s');
        $param['request_post_url'] ="http://guo.wuqiaodianqi9.cn/pay";
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($param);
  /*      $url = "http://ipzhifu.com/pay/json";
        $data = json_encode($param);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json;charset=utf-8"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        var_dump($res);die();
        curl_close($curl);
        $data = json_decode($res, true);
        if($data['Msg'] == "success")
        {
            return $data['QRCodeLink'];
        }
        throw new OrderException([
            'msg' => 'Create XK API Error:',
            'errCode' => 200009
        ]);*/
    }

    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }

    public function guma_yhk($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }
    public function test($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $url,
        ];
    }

    function getSign($param)
    {
        $string = '';

        $token = "6401aed613f34412bc40235d04ebe940";//此处请填写您的token值
        foreach($param as $value)
        {
            $string .= $value;
        }
        return md5($string.$token);
    }
    /*
     * Dn平台支付回调处理
     */
    public function notify()
    {
        $notifyData = file_get_contents('php://input');
        if(!$notifyData){
            exit('fail. no data');
        }
        $postdata = json_decode($notifyData,true);
        if($this->verify($postdata)){
            /******* 您业务逻辑 *******/
            echo "success";
            $data["out_trade_no"] = $postdata['user_order_no'];
            return $data;
        }
        throw new OrderException([
            'msg' => 'Create QH API Error:',
            'errCode' => 200009
        ]);
    }


    function verify($postdata)
    {
        $token    = "6401aed613f34412bc40235d04ebe940";

        $sign = md5($postdata['user_order_no'] . $postdata['orderno'] . $postdata['tradeno'] . $postdata['price'] . $postdata['realprice'] . $token);
        if($postdata['sign'] == $sign)
        {
            return true;
        }
        return false;
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
