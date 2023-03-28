<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/23
 * Time: 15:39
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HaohanPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='121'){

        //这个支付有点坑
        $num=$order["amount"];
        if(floor($num)==$num){
            $amount = sprintf("%.1f",$order["amount"]);
        }else{
            $amount = floatval(sprintf("%.2f",$order["amount"]));
        }
        $memberid = '9070';//商户号
        $orderid = $order['trade_no'];
        $paymethod=$type;//支付方式（121：支付宝；122：微信）
        $applydate=utf8_encode(date("Y-m-d H:i:s"));//提交时间
        $notifyurl=utf8_encode($this->config['notify_url']);//
//        $amount=floatval(sprintf("%.2f",$order["amount"]));//订单金额
//        $amount=sprintf("%.2f",$order["amount"]);//订单金额
        $productname=utf8_encode('goods');//商品名  utf8_encode
        $productnum=1;//数量
        $s_key = '9c23110c0e622089a195ce9e467ba46d';//密钥
        $all=array(
            'memberid'=> $memberid,
            'orderid'=> $orderid,
            'paymethod'=> $paymethod,
            'applydate'=> $applydate,
            "notifyurl"=> $notifyurl,
            "amount"=> $amount,
            "productname"=> $productname,
            "productnum"=> $productnum
        );
        ksort($all);
        $arrayStr = http_build_query($all, null, '&amp;');
        $arrayStr = htmlspecialchars_decode($arrayStr);
        $arrayStr = trim($arrayStr).$s_key;
        //$data = md5(htmlentities($arrayStr));
        $data = md5($arrayStr);
        $all['sign']= strtoupper($data);//签名
//        return $result =  json_decode(self::curlPost($url,$all),true);
        //get
        $get = "http://pay.haohan2020.com/PayGateway/Pay?";
        $get .= "amount=$amount&applydate=$applydate&memberid=$memberid";
        $get .= "&notifyurl=$notifyurl&orderid=$orderid&paymethod=$paymethod&productname=$productname&productnum=$productnum&sign={$all['sign']}";
        return  $get; //打开这条链接下订单
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'121');  
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'121');
        return [
            'request_url' => $url,
        ];
    }
 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'121');
        return [
            'request_url' => $url,
        ];
    }
    /**
     * @param $params
     * 微信
     */
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'8003');
        return [
            'request_url' => $url,
        ];
    }


    /**
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
        Log::notice("HaohanPay notify data".$input);
        $notifyData = json_decode($input,true);
        if( $notifyData['code'] =='100'  ){
            if($notifyData['data']['result'] == '1' ) {
                echo "success";
                $data['out_trade_no'] = $notifyData['data']['orderid'];
                return $data;
            }
        }
        echo "error";
        Log::error('HaohanPay API Error:'.$input);
    }
}
