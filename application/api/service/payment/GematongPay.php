<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/3
 * Time: 21:06
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class GematongPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){
        $url = 'https://api.gematong.com/v1/pay/cashier';
        $sMd5Key = 'pVNz5c4PielMGWamJt2SEdkj';

        $arrPay = array(
            'pay_type'  => $type, //支付类型  wechat alipay qq ebank
            'mch_id'    => '840', //商户号
            'amount'    => intval($order["amount"]), //金额 单位元
            'order_id'  => $order['trade_no'], //商户订单号
            'version'   => 'v1', //版本号 暂时固定v1
            'cb_url'    => $this->config['notify_url'], //异步通知地址
            'desc'      => 'goods',
            'is_mobile' => '0', //是否移动端 0： 否 1： 是  新版接口建议传递  否则默认为0 并且注意 当为0返回收银台地址  为1返回支付宝wap地址
            'bank_code' => '' //为空或者没有这个字段 跳转我方收银台
        );
        // 签名
        $arrPay['sign'] = $this->getSign($arrPay, $sMd5Key); //签名
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
        );
        $result =  json_decode(self::curlPost($url,$arrPay,[CURLOPT_HTTPHEADER=>$headers]),true);
        if($result['code'] != 'A00000' )
        {
            Log::error('Create GematongPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create GematongPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }



    /**
     * 查询接口
     */
    public function query($notifyData){
        $url = 'http://3.0.154.75:9081/pay/payresult.php';
        $data = [
            'cpid'  =>  '2043',
            'cp_trade_no'  =>  $notifyData['cp_trade_no'],
            'pay_day'  =>  date('Y-m-d'),
        ];
        $key = '4bk77994or4wxs';
        $data['sign'] = $this->getSign($data,$key);
        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        Log::notice('query FeitianPay  API notice:'.json_encode($result));
        if( !isset($result['result_code']) ||  $result['result_code'] != '0' ){
            Log::error('query FeitianPay  API Error:');
            return false;
        }
        return true;
    }



    public function getSign($params,$key){
        ksort($params);
        $signPars = "";
        foreach ($params as $k => $v) {
            $signPars .= $k . $v;
        }
        $signPars .= $key;
        return sha1($signPars);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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
        Log::notice("GematongPay notify data".json_encode($notifyData));
        if($notifyData['status'] == '1' ){
            echo "SUCCESS";
            $data['out_trade_no'] = $notifyData['mch_order_id'];
            return $data;
        }
        echo "FAIL";
        Log::error('GematongPay API Error:'.json_encode($notifyData));
    }

}