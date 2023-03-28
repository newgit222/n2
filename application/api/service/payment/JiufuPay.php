<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/8
 * Time: 22:38
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\Request;

class JiufuPay extends ApiPayment
{


    /**
     * 获取银行编码
     */
      public function getBankCode($bank_code){
        $bankList = [
            'ICBC'=>'ICBC', //工商
            'ABC'=>'ABC',   //农业
            'CMB'=>'CMB',   //招商
            'BOC'=>'BOC',   //中国
            'CCB'=>'CCB',   //建设
            'COMM'=>'COMM',   //交通
            'CITIC'=>'CITIC',   //中信
            'CEB'=>'CEB',   //光大
            'HXB'=>'HXB',   //华夏
            'CMBC'=>'CMBC',   //民生
            'GDB'=>'GDB',   //光大
            'PAB'=>'SZPAB',   //平安
            'CIB'=>'CIB',   //兴业
            'BOB'=>'BCCB',   //北京
            'SPDB'=>'SPDB',   //浦发
            'HKBCHINA'=>'HKBCHINA',   //汉口
            'HCCB'=>'HCCB',   //杭州
            'SXJS'=>'SXJS',   //晋城
            'NJCB'=>'NJCB',   //南京
            'NBCB'=>'NBCB',   //宁波
            'BOS'=>'BOS',   //上海
            'PSBC'=>'PSBC',   //邮政

        ];
        if(!isset($bankList[$bank_code])){
            return false;
        }
        return $bankList[$bank_code];
    }


    /**
     * 统一下单
     */
    private function pay($order,$type='10106'){
        $url = 'https://newapi.9pay.vip/unionOrder';

        $userkey = 'khrsbabmzvundkpsbq';
        $data = [
            'partner'   =>  '1005765046',
            'service'   =>  $type,
            'tradeNo'   =>  $order['trade_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'extra'   =>  'goods',
        ];
        $data['sign'] = $sign = md5("amount=".$data['amount']."&extra=".$data['extra']."&notifyUrl=".$data['notifyUrl']."&partner=".$data['partner']."&service=".$data['service']."&tradeNo=".$data['tradeNo']."&".$userkey);

//        http://cc.byfbqgi.cn/
        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }

    /**
     * 代付下单
     */
     public function daifuPay($order){
        $url = 'https://newapi.9pay.vip/agentPay';
        $userkey = 'khrsbabmzvundkpsbq';
        $data = [
            'partner'   =>  '1005765046',
            'service'   =>  '10201',
            'tradeNo'   =>  $order['cash_no'],
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'bankCode'  =>  $this->getBankCode($order['bank_code']),
            'bankCardNo'  =>  $order['account'],
            'bankCardholder'  =>  $order['account_name'],
            'subsidiaryBank'  =>  '123',
            'subbranch'  =>  '123',
            'province'  =>  '123',
            'city'  =>  '123',
            'notifyUrl'  =>  Request::instance()->domain().DS.'Api'.DS.'notify'.DS.'daifuNotify?channel=JiufuPay',  //回调地址先写死
            'extra'  =>  'daifupay',
        ];
        $data['sign'] = $this->getSign($data,$userkey);
         $result =  json_decode(self::curlPost($url,($data)),true);
//        {
//                    "isSuccess": "T",
//            "failCode": null,
//            "failMsg": null,
//            "sign": "2331a4e43af476bed0b4612892a56973",
//            "charset": "utf-8",
//            "signType": "md5",
//            "tradeId": "JF2020051020650952"
//        }
         Log::notice("JiufuPay daifuPay param".json_encode($result));
        if($result['isSuccess'] != 'T' )
        {
            Log::error('Create JiufuPay API Error:'.$result['msg']);
            return ['code'=>'0','msg'=>'Create JiufuPay API Error:'.$result['msg']];
        }
        return ['code'=>'1','msg'=>'请求成功','data'=>$order['cash_no']];
    }



    /**
     * 查询接口
     */
    public function query($notifyData,$type){
        $url = 'https://newapi.9pay.vip/orderQuery';
        $data = [
            'partner'  =>  '1005765046',
            'service'  =>  $type, //支付订单 10302   代付订单 10301
            'outTradeNo'  =>  $notifyData['outTradeNo'],
        ];
        $userkey = 'khrsbabmzvundkpsbq';
        $data['sign']=md5("outTradeNo=".$data['outTradeNo']."&partner=".$data['partner']."&service=".$data['service']."&".$userkey);
         $result =  json_decode(self::curlPost($url,($data)),true);
        Log::notice('query JiufuPay  API notice:'.json_encode($result));
        if(  $result['isSuccess'] != 'T' ){
            Log::error('query JiufuPay  API Error:');
            return false;
        }
        if($result['status'] != '1'){
            return false;
        }
        return true;
    }



    public function getSign($parameters,$key){
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .=  $key;
        $sign = (md5($signPars));
        return $sign;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'10106');
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
     * 代付回调地址
     */
     public function daifuNotify(){
        $notifyData =$_POST;
        Log::notice("JiufuPay daifuNotify data1".json_encode($notifyData));
        if($notifyData['status'] == '1' ){
            $res = $this->query($notifyData,'10301');
            if($res){
                echo "success";
                $data['out_trade_no'] = $notifyData['outTradeNo'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('JiufuPay daifuNotify API Error:'.json_encode($notifyData));
    }



    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("JiufuPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == '1' ){
            $res = $this->query($notifyData,'10302');
            if($res){
                echo "success";
                $data['out_trade_no'] = $notifyData['outTradeNo'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('JiufuPay API Error:'.json_encode($notifyData));
    }
}