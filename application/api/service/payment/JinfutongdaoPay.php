<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/21
 * Time: 23:52
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JinfutongdaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='bankCard'){
$app_id="2012160740";                                                           //商户ID 在商户中心获取
$key="11c7a0e3143d620a13c1c9d5ecfe11bd";                   //商户密钥 在商户中心获取
$interface_version="V2.0";                                              //接口版本，默认值"V2.0"
$trade_type=$type;                                       //通道类型（例：支付宝扫码（ALIPAY_NATIVE））请参照开发文档 3支付类型代码对照表
$total_amount=$order["amount"]*100;                                                       //订单金额，单位为分
$out_trade_no= $order['trade_no'];                    //订单号，需唯一
$notify_url= $this->config['notify_url'];       //异步通知地址
$return_url= $this->config['return_url'];       //同步返回地址
$extra_return_param = 'cc';                                         //备注信息 有中文需要编码，回调时原样返回
$client_ip = "127.0.0.1";                                               //提交Ip 可为空
$posturl = "http://pay.jyzf2020.com/api/gateway"; //提交地址  支付网址请在商户中心获取

$sign ="app_id=".$app_id."&notify_url=".$notify_url."&out_trade_no=".$out_trade_no."&total_amount=".$total_amount."&trade_type=".$trade_type;
$sign=md5($sign.$key);
$PostUrl=$posturl."?app_id=".$app_id."&interface_version=".$interface_version."&trade_type=".$trade_type."&total_amount=".$total_amount."&out_trade_no=".$out_trade_no."&return_url=".$return_url."&notify_url=".$notify_url."&extra_return_param=".$extra_return_param."&client_ip=".$client_ip."&sign=".$sign;
 return $PostUrl;




   /*     $url = 'http://huiyuan.mazhou66888.com:6060/api/startOrder';
        $merkey = '3d202bd367516cb014757e5b8d7cde7e';
        $data = [
            'merchantNum'   =>  '2022008',
            'orderNo'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'payType'   =>  $type,
        ];
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$data['amount'].$data['notifyUrl'].$merkey);

        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create JiebaoPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create JiebaoPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];*/
    }

    public function query($notifyData){
        $url = 'http://pay.xg.you2pay.icu:6060/api/getOrderInfo';
        $key = '3d202bd367516cb014757e5b8d7cde7e';
        $data=array(
            'merchantNum'=>'2022008',
            'orderNo'=>$notifyData['platformOrderNo'], 
        );
        $data['sign'] = md5($data['merchantNum'].$data['orderNo'].$key);
        $result =  json_decode(self::curlGet($url.http_build_query($data)),true);
        Log::notice('query JiebaoPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query JiebaoPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['orderState'] != '4' ){
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
        $url = $this->pay($params,'alipayTransfer');
        return [
            'request_url' => $url,
        ];
    }

 public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipayTransfer');
        return [
            'request_url' => $url,
        ];
    }


 public function test($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipayTransfer');
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

        $notifyData =$_GET;
        Log::notice("JiebaoPay notify data1".json_encode($notifyData));
        if($notifyData['state'] == "1" ){
//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderNo'];
                return $data;
//            }
        }
        echo "error";
        Log::error('JiebaoPay API Error:'.json_encode($notifyData));
    }
}
