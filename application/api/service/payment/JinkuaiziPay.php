<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/17
 * Time: 14:04
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JinkuaiziPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ALIPAY'){
        $url="http://api.goldchopsticks.shop/api/otp/create";
        $timestamp=$this->getMillisecond();
        $appId="202012131607848561039";
        $outOrderNo=$order['trade_no'];
        $userUnqueNo="1";//用户唯一编号不能为空,填写用户的ID或者用户名，可加密后传值，用于订单申诉检查,请务必填写很重要
        $payType=$type;
        $secret="3cc833a7a646ffe582654d773cba655b0cbf731c6663737bf7b21697014971fb";

        $post_data['amount']       = sprintf("%.2f",$order["amount"]);// 金额，最低金额100元
        $post_data['attach']      = '123';// 自定义参数
        $post_data['payType'] = $payType;// 支付方式
        $post_data['orderDesc'] = "goods";// 商品信息描述，例如： 购买商品100元，充值话费200元
        $post_data['userUnqueNo']    = $userUnqueNo;// 用户唯一编号,务必填写用户ID或者用户账号，用于申诉，很重要
        $post_data['notifyUrl']    = $this->config['notify_url'];// 异步通知URL
        $post_data['returnUrl']    = $this->config['return_url'];// 支付结果URL
        $post_data['appId']    = $appId;// 商户ID
        $post_data['outOrderNo']    = $outOrderNo;// 商户站点的订单号
        $post_data['timestamp']    = $timestamp;// 13位的GMT+8时区时间戳
        $nonceStr=self::createNonceStr(18);
        $post_data['nonceStr']    = $nonceStr;//业务流水号，18位长度以上的随机字符串
        $post_data['signature']    = $this->getSign($outOrderNo,$post_data['amount'],$payType,$post_data['attach'],$appId,$timestamp,$nonceStr,$secret);
//var_dump( $post_data);;
        $result =  json_decode(self::curlPost($url,$post_data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create YuncaiPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create YuncaiPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data'];
    }


    // 毫秒级时间戳
    function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    function getSign($outOrderNo,$amount,$payType,$attach,$appId,$timestamp,$nonceStr,$secret){
        $params=array('timestamp'=>$timestamp,'secret'=>$secret,'nonceStr'=>$nonceStr,"outOrderNo"=>$outOrderNo,"amount"=>$amount,"payType"=>$payType,"attach"=>$attach,'appId'=>$appId);
        ksort($params);//把key，从小到大排序
        $paramUrl = "?".http_build_query($params, '' , '&');//封装成url参数
//ar_dump( $paramUrl);
  //      print_r($paramUrl);
//var_dump($paramUrl.$appId.$timestamp.$nonceStr);die();
        $md5Value=strtolower(md5($paramUrl));
        return strtolower($md5Value);
    }


    function getQueySign($appId,$outOrderNo,$timestamp,$nonceStr,$secret){
//        $params=array("outOrderNo"=>$outOrderNo,"amount"=>$amount,"payType"=>$payType,"attach"=>$attach);
//        ksort($params);//把key，从小到大排序
//        $paramUrl = "?".http_build_query($params, '' , '&');//封装成url参数
        //print_r($paramUrl);
        $md5Value=strtolower(md5($appId.$outOrderNo.$timestamp.$nonceStr));
        return strtoupper(sha1($md5Value.$secret));
    }


    /**
     * 查询
     */
    private function query($notifyData){
return true;
        $url = 'http://op.yuncai668.top/api/otp/query';
        $secret="cc5f3a1e091238290747a3f4eba5a52502f41e22da4dcf281c9885a4241e7160";

        $data = [
            'outOrderNo'    =>  $notifyData['outOrderNo'],
            'timestamp'    =>  $this->getMillisecond(),
            'nonceStr'    =>  self::createNonceStr(18),
            'appId'    =>  '202004171587055384733',
        ];

        $data['signature']  =   $this->getQueySign($data['appId'],$data['outOrderNo'],$data['timestamp'],$data['nonceStr'],$secret);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query YuncaiPay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query YuncaiPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['status'] != '4' ){
            return false;
        }
        return true;

    }



    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

  public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        $notifyData =$_POST;
        Log::notice("YuncaiPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "SUCCESS" ){
            if($this->query($notifyData)) {
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['outOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('YuncaiPay API Error:'.json_encode($notifyData));
    }

}
