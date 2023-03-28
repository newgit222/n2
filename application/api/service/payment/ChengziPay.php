<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/12
 * Time: 20:14
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ChengziPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_scan'){


        $data = array(
            "fxid" => '2020204', //商户号
            "fxddh" => $order['trade_no'], //商户订单号
            "fxdesc" => 'goods', //商品名
            "fxfee" => sprintf("%.2f",$order["amount"]), //支付金额 单位元
            "fxattch" => 'goods', //附加信息
            "fxnotifyurl" => $this->config['notify_url'], //异步回调 , 支付结果以异步为准
            "fxbackurl" => $this->config['return_url'], //同步回调 不作为最终支付结果为准，请以异步回调为准
            "fxpay" => 'alipay_scan', //支付类型 此处可选项以网站对接文档为准 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
            "fxip" => get_userip(), //支付端ip地址
            'fxbankcode'=>'',
            'fxfs'=>'',
        );
        $fxkey= 'hYMarRpHROjbgiZqYyLlxKEoOUzBPRkt';
        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $fxkey); //加密
        $url = 'http://www.jidang88888.com/Pay';

        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['status'] != '1' )
        {
            Log::error('Create FuyaPay API Error:'.$result['error']);
            throw new OrderException([
                'msg'   => 'Create FuyaPay API Error:'.$result['error'],
                'errCode'   => 200009
            ]);
        }
        return $result['payurl'];
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_scan');
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
        Log::notice("ChengziPay notify data".json_encode($notifyData));
//        {"fxid":"2020204","fxddh":"115841090912834","fxorder":"pay202003132224304884952","fxdesc":"goods","fxfee":"100.00","fxattch":"goods","fxstatus":"1","fxtime":"1584109470","fxsign":"f5b8f0543d722ea6792c0ab4edd4f89e"}
        if($notifyData['fxstatus'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['fxddh'];
            return $data;
        }
        echo "error";
        Log::error('ChengziPay API Error:'.json_encode($notifyData));
    }
}