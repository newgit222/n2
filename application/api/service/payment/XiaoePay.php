<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/4
 * Time: 16:25
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class XiaoePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='WechatScan'){
        $url = 'https://api.powergosling.com/pay';

        $mch_key = '7f6ca454d9e34f2098bd160f9ee56345';
        $p_data=array(
            'p5_reqtime'=>date("yyMMDDHHmmss"),
            'p1_merchantno'=>'MER20210404131850901601',
            'p4_paytype'=>$type,
            'p3_orderno'=>$order['trade_no'],
            'p2_amount'=> sprintf("%.2f",$order["amount"]),//增加一定随机数金额
            'p6_goodsname'=>'goods',
            'p7_bankcode'=>'ABC',
            'p8_returnurl'=>'page',
            'p9_callbackurl'=>$this->config['notify_url']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=strtoupper(md5($sign_str));
         $result =  json_decode(self::curlPost($url,$p_data),true);
//var_dump( $result);die();
        if($result['rspcode'] != 'A0' )
        {
            Log::error('Create PixiuPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create PixiuPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data'];

   //     $p_data['request_post_url'] =$url;
     //   return "http://cc.byfbqgi.cn/pay.php?".http_build_query($p_data);

    }


    public function getSign($parameters,$key){
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = md5($signPars);
        return $sign;
    }


    public function query($notifyData){

        $url = 'https://www.cnxle.cn/?c=Pay&a=query';

        $mch_key = '028b68900c1ad05f0c2ad6c58b549f8cc551ad45';
        $p_data=array(
            'time'=>time(),
            'mch_id'=>'zhong225',
            'out_order_sn'=>$notifyData['sh_order']
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$mch_key}";
        $p_data['sign']=md5($sign_str);

        $result =  json_decode(self::curlPost($url,$p_data),true);
        Log::notice('query ChuangshijiPay  API notice:'.json_encode($result));
        if($result['code'] != '1' ){
            return false;
        }
        if($result['data']['status'] != '9' ){
            return false;
        }
        return true;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1');
        return [
            'request_url' => $url,
        ];
    }

 public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

 public function guma_vx($params)
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
        $notifyData =$_GET;
//        Log::notice("ChuangshijiPay notify data1".json_encode($notifyData));
        if(1 ){
            if(1){
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['p3_orderno'];
                return $data;
            }
        }
        echo "error";
        Log::error('ChuangshijiPay API Error:'.json_encode($notifyData));
    }
}
