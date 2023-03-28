<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 14:34
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class HaojiePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='8017'){


        $url = 'http://47.254.44.144:3020/api/pay/create_order';
        $merkey = 'JD7OLRFUW1FDW54YHAVHGQOT6ZLYMZIAS9THDULVBLZI0RUPZC6EZBCVYFVGLOMJE0H7EMUSDLKPEQGMNCJT9MSSUQ4LDLLO5RJIY0EOA12CND7T3IPOMHVDPQM2ZTID';

        $data = [
            'mchId' =>  '39673',
            'appId' =>  'f7fe7d2cdc0c4b8c9bb7a246e264228c',
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
$result =  json_decode(self::curlPost($url,['params'=>json_encode($data)]),true);
        
if($result['retCode'] != 'SUCCESS' )
        {
            Log::error('Create HuaxinPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create HuaxinPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function query($notifyData){
        
return true;
$url = 'http://47.254.44.144:3020//api/pay/query_order';

        $merkey = 'JD7OLRFUW1FDW54YHAVHGQOT6ZLYMZIAS9THDULVBLZI0RUPZC6EZBCVYFVGLOMJE0H7EMUSDLKPEQGMNCJT9MSSUQ4LDLLO5RJIY0EOA12CND7T3IPOMHVDPQM2ZTID';

        $data = [
            'mchId' =>  '39673',
            'appId' =>  'f7fe7d2cdc0c4b8c9bb7a246e264228c',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query HuaxinPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query HuaxinPay  API Error:'.$result['retMsg']);
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
        $url = $this->pay($params,'8017');
//        $url = $this->query(['mchOrderNo'=>'115869417346689']);
//        var_dump($url);die();
        return [
            'request_url' => $url,
        ];
    }
  public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params,'8017');
//        $url = $this->query(['mchOrderNo'=>'115869417346689']);
//        var_dump($url);die();
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
        $url = $this->pay($params,'8017');
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
        $notifyData =$_POST;
        Log::notice("HuaxinPay notify data1".json_encode($notifyData));

 echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
        if($notifyData['status'] =='2' || $notifyData['status'] =='3'  ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('HuaxinPay API Error:'.json_encode($notifyData));
    }

}
