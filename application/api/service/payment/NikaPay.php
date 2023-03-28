<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/21
 * Time: 17:01
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class NikaPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='zfb'){
        $url = 'https://api.if990.cn/apis.php';
        $merkey = '9XYWLANM6C71SSAGHD0CBULPNY86ZW4I';
        $data = [
            'shid'  =>  'nika',
            'orderid'  =>  $order['trade_no'],
            'amount'  =>  sprintf("%.2f",$order["amount"]),
            'pay'  =>  $type,
            'notifyurl'  =>  $this->config['notify_url'],
            'url'  =>  $this->config['return_url'],
            'ip'  =>  get_userip(),
        ];
        $data['sign'] = md5(md5($merkey).md5($data['shid'].$data['amount'].$data['pay'].$data['orderid']));
//        $data['request_post_url']= $url;
//        return "http://caishen.sviptb.com/pay.php?".htmlspecialchars(http_build_query($data));


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create NikaPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create NikaPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payurl'];
    }




    public function query($notifyData){

        $url = 'https://api.if990.cn/cxapis.php';

        $key = '9XYWLANM6C71SSAGHD0CBULPNY86ZW4I';
        $data=array(
            'shid'=>'nika',
            'orderid'=>$notifyData['orderid']
        );
        $data['sign'] = md5(md5($key).md5($data['shid'].$data['orderid']));

         $result =  json_decode(self::curlPost($url,$data),true);

        Log::notice('query NikaPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query NikaPay  API Error:'.$result['msg']);
            return false;
        }
        return true;
    }




    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($secret, $data)
    {

        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }






    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfb');
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
        $notifyData = $_POST;
        Log::notice("NikaPay notify data".json_encode($notifyData));
        if(isset($notifyData['status'])){
            if($notifyData['status'] =='1' ){
                if($this->query($notifyData)){
                    echo "OK"; 
                    $data['out_trade_no'] = $notifyData['orderid'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('NikaPay API Error:'.json_encode($notifyData));
    }
}