<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/1
 * Time: 19:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FeilongPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='8028'){


        $url = 'https://api668.wbpayer.com/api/pay/create_order';
        $merkey = 'WFICYRG3P02U2O4N6MW44W3CIDPG6RM3UOQU0MO3X22UPCN0NH5OQWSL1U4Z8G40HQSHTGUOSEFAHC7VTZT8P4Y1EJMJWCKXURNF5OWXX07B9OVJDNIB7SLKSKVQTEQI';

        $data = [
            'mchId' =>  '24',
            'appId' =>  '9d7d271cb7f048f6ad81b6d235603f13',
            'productId' =>  $type,
            'mchOrderNo' =>  $order['trade_no'],
            'currency' =>  'cny',
            'amount' =>  sprintf("%.2f",$order["amount"])*100,
            'notifyUrl' =>  $this->config['notify_url'],
            'subject' =>  'goods',
            'body' =>  'goods',
//            'extra' =>  '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create FeilongPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create FeilongPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function query($notifyData){
        $url = 'https://api668.wbpayer.com/api/pay/query_order';

        $merkey = 'WFICYRG3P02U2O4N6MW44W3CIDPG6RM3UOQU0MO3X22UPCN0NH5OQWSL1U4Z8G40HQSHTGUOSEFAHC7VTZT8P4Y1EJMJWCKXURNF5OWXX07B9OVJDNIB7SLKSKVQTEQI';

        $data = [
            'mchId' =>  '24',
            'appId' =>  '9d7d271cb7f048f6ad81b6d235603f13',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);


        Log::notice('query FeilongPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query FeilongPay  API Error:'.$result['retMsg']);
            return false;
        }
        if($result['status'] != '2'   ){
            if($result['status'] != '3') {
                return false;
            }
        }
        return true;
    }



    private function getSign($data,$secret )
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
//        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a.'key='.$secret);

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
        $url = $this->pay($params,'8028');
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
        $notifyData =$_POST;
        Log::notice("FeilongPay notify data1".json_encode($notifyData));
        if($notifyData['status'] =='2' || $notifyData['status'] =='3'  ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('FeilongPay API Error:'.json_encode($notifyData));
    }
}
