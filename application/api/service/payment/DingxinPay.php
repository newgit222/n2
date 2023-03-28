<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/11
 * Time: 0:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class DingxinPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='2'){

        $url = 'http://43.227.198.41:9005/api/WapAliPayOnlineSubmit.aspx';

        $key = '8zl7nqk4';

        $data = [
            'MerchantID'    =>  '12000426',
            'TenPayID'    =>  '1',
            'OrderNo'    =>  $order['trade_no'],
            'BankType'    =>  '1',
            'Desc'    =>  'goods',
            'Ip'    =>  get_userip(),
            'NotifyUrl'    =>  $this->config['notify_url'],
            'ShowUrl'    =>  $this->config['return_url'],
            'Money'    =>  sprintf("%.2f",$order["amount"]),
        ];
        $signStr = 'MerchantID='.$data['MerchantID'].'&PayID='.$data['TenPayID'].'&OrderNo='.$data['OrderNo'].'&Money='.$data['Money'].'&BankType='.$data['BankType'].
            '&PayDesc='.$data['Desc'].'&Ip='.$data['Ip'].'&NotifyUrl='.$data['NotifyUrl'].'&ShowUrl='.$data['ShowUrl'].'&MerchantKey='.$key;
        $data['sign'] = md5($signStr);

        $data['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".http_build_query($data);
    }









    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'2');
        return [
            'request_url' => $url,
        ];
    }

 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'2');
        return [
            'request_url' => $url,
        ];
    }
 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'2');
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
        $notifyData = $_GET;
        Log::notice("DingxinPay notify data2".json_encode($notifyData));
//        {"MerchantID":"12000426","Status":"1","OrderNo":"115918661251943858683","Sign":"71355e7991fdb24fbaea83bb012a8e09","Money":"1.00"}
        if($notifyData['Status'] == '1' ){
                echo "OK";
                $data['out_trade_no'] = $notifyData['OrderNo'];
                return $data;
        }
        echo "error";
        Log::error('DingxinPay API Error:'.json_encode($notifyData));
    }

}
