<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/23
 * Time: 22:05
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LangyunPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='102'){

        //开始创建订单，订单生成参数请根据相关参数自行调整。
        $post['paytype'] = $type; //类型请自行调整
        $post['out_trade_no'] = $order['trade_no']; //平台订单
        $post['notify_url'] = $this->config['notify_url']; //这个是订单回调地址，成功付款后定时通知队列会调这个地址。
        $post['return_url'] = $this->config['return_url']; //这个是订单回调地址，成功付款后实时跳回这个地址。
        $post['goodsname'] = "goods"; //商品名称
        $post['total_fee'] = $order["amount"]; //定单金额，不要带小数，必须是整数
        $post['remark'] = "123456"; //平台的名称，做区分用的。
        $post['requestip'] = get_userip(); //玩家的IP。
        //结束创建订单
        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm"; //随机数基本字符串

        //加入商户ID及算签名
        $post1['mchid'] = 23456; //商户ID，请自行调整
        $post1['timestamp'] = time(); //时间戳
        $post1['nonce'] = substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), 10);
        $post1['sign'] = $this->getSign(array_merge($post, $post1), "8a89ad4c72c94668a60c633942db3bd6");//商户密匙，请自行调整
        $post1['data'] = $post; //合并真正提交的参数JSON
        //网关地址
        $gateway = "http://lypay.upqwhp.cn/api/pay/gopay";
        //提交
         $result =  json_decode($this->curl_post($gateway,$post1),true);
        if($result['error'] != '0' )
        {
            Log::error('Create LangyunPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create LangyunPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payurl'];

    }



    private function query($notifyData){
        $gateway = 'http://lypay.upqwhp.cn/api/pay/orderinfo';
        $post = [
            'trade_no' =>  $notifyData['trade_no'],
        ];
        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm"; //随机数基本字符串

        //加入商户ID及算签名
        $post1['mchid'] = 23456; //商户ID，请自行调整
        $post1['timestamp'] = time(); //时间戳
        $post1['nonce'] = substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), 10);
        $post1['sign'] = $this->getSign(array_merge($post, $post1), "8a89ad4c72c94668a60c633942db3bd6");//商户密匙，请自行调整
        $post1['data'] = $post; //合并真正提交的参数JSON
        $result =  json_decode($this->curl_post($gateway,$post1),true);
        Log::notice('query LangyunPay  API notice:'.json_encode($result));
        if(  $result['error'] != '0' ){
            Log::error('query LangyunPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['state'] != '3'   ){
            return false;
        }
        return true;
    }



    public function curl_post($url, $data = array())
    {
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( //改为用JSON格式来提交
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
        $result = curl_exec($curl);//执行命令
        curl_close($curl);//关闭URL请求
        return $result;
    }






    public function getSign($array = array(), $key)
    {
        ksort($array);
        foreach ($array as $k => $v) {
            if ($array[$k] == '' || $k == 'sign' || $k == 'sign_type' || $k == 'key') {
                unset($array[$k]);//去除多余参数
            }
        }
        return strtolower(md5($this->createLinkString($array) . "&key=" . $key));
    }


    public function createLinkString($para)
    {
        $arg = "";
        foreach ($para as $key => $value) {
            $arg .= $key . "=" . $value . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'102');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
        //获取预下单
        $url = $this->pay($params);
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

        $input = file_get_contents("php://input");
        Log::notice("LangyunPay notify data".$input);
        $notifyData = json_decode($input,true);

        if(isset($notifyData['out_trade_no']) ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('hgpay API Error:'.$input);

    }
}
