<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/1
 * Time: 15:35
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XinhuitongPay  extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='210'){

        $url = 'http://gate.tailtalk.club/API/PostOrderHandler.ashx';


        $data = [
            'attach'    =>  'goods',
            'callbackurl'    =>  $this->config['notify_url'],
            'hrefbackurl'    =>  $this->config['return_url'],
            'orderid'    =>  $order['trade_no'],
            'merchant'    =>  '0203989945',
            'type'    =>  $type,
            'value'    =>  sprintf("%.2f",$order["amount"]),
        ];

        $data['sign'] = $this->getSign($data);

        return $url.'?'.http_build_query($data);
    }


    /**
     * @param $notifyData
     * @return bool
     * 查询
     */
    public function query($notifyData){
        $url = 'http://gate.tailtalk.club/API/SearchOrderHandler.ashx';
        $data = [
            'merchant'   =>  '0203989945',
            'orderid'   =>  $notifyData['orderid'],
        ];

        $data['sign'] = $this->getSign($data);

        $result = json_decode(self::curlGet($url.'?'.http_build_query($data)),true);
        Log::notice('query XinhuitongPay  API notice:'.json_encode($result));
        if(  $result['result'] != 'OK' ){
            Log::error('query XinhuitongPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['status'] != '2' ){
            return false;
        }
        return true;
    }


    public function getSign($params){
        ksort($params);
        $merkey = '4dsRDRDWK9HxsEmiaPP6776jDknAzD2M';
        $tmp_str = '';
        foreach ($params as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1).$merkey;

        return (md5($tmp_str));

    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'210');
        return [
            'request_url' => $url,
        ];
    }

 public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'210');
        return [
            'request_url' => $url,
        ];
    }
 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'210');
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
        Log::notice("XinhuitongPay notify data2".json_encode($notifyData));

        if($notifyData['opstate'] == "0" ){

            if($this->query($notifyData)) {

                echo "success";
                $data['out_trade_no'] = $notifyData['orderid'];
                return $data;
            }
        }
        echo "error";
        Log::error('hgpay API Error:'.json_encode($notifyData));
    }

}
