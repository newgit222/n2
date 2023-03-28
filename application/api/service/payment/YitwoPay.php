<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 22:28
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class YitwoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='8006'){


        $url = 'http://api.naonaodi.com/api/pay/create_order';
        $merkey = 'Q85HLFFQL096NPBOOFOVCNQXZJ5TUGHZWBFGRDQDBNDZNGSFPWANNDRBG5LLW2U4DAES3XI5JBDJASFYSNGZTRSPVPPAK9RG34RBBUKYZDJRKAEEEGXFFO89IFR1EITM';

        $data = [
            'mchId' =>  '20000279',
            'appId' =>  '867b7b9a21124a3996412dedc3777766',
            'productId' =>  $type,
            'mchOrderNo' =>  $order['trade_no'],
            'currency' =>  'cny',
            'amount' =>  sprintf("%.2f",$order["amount"])*100,
            'notifyUrl' =>  $this->config['notify_url'],
            'subject' =>  'goods',
            'body' =>  'goods',
            'extra' =>  '{"payMethod":"urlJump"}',
        ];
        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create YitwoPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create YitwoPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function query($notifyData){
        $url = 'http://api.naonaodi.com/api/pay/query_order';

        $merkey = 'Q85HLFFQL096NPBOOFOVCNQXZJ5TUGHZWBFGRDQDBNDZNGSFPWANNDRBG5LLW2U4DAES3XI5JBDJASFYSNGZTRSPVPPAK9RG34RBBUKYZDJRKAEEEGXFFO89IFR1EITM';

        $data = [
            'mchId' =>  '20000279',
            'appId' =>  '867b7b9a21124a3996412dedc3777766',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query YitwoPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query YitwoPay  API Error:'.$result['retMsg']);
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
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'9002');
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
        $url = $this->pay($params,'9003');
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
        Log::notice("YitwoPay notify data1".json_encode($notifyData));
        if($notifyData['status'] =='2' || $notifyData['status'] =='3'  ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('YitwoPay API Error:'.json_encode($notifyData));
    }
}
