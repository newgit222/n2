<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/22
 * Time: 19:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class MxPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='8006'){


        $url = 'http://47.114.1.239:3020/api/pay/create_order';
        $merkey = '0677QCLYIWE1ZEVFWO1QZJTVZBRJZRK8EV3TGTR9GZGASHZJY9PKUT7ZAME9FI4QGCO8NVSSUIOHYZQBXAXLP9NCILYCUTUL6MOIRGWG72ADVNDSAENMEJATRMXXRMSD';

        $data = [
            'mchId' =>  '20000020',
            'appId' =>  'a2cc53dd4c25408986b3c585868041f9',
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
            Log::error('Create MxPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create MxPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['payParams']['payUrl'];
    }


    private function query($notifyData){
        $url = 'http://47.114.1.239:3020/api/pay/query_order';

        $merkey = '0677QCLYIWE1ZEVFWO1QZJTVZBRJZRK8EV3TGTR9GZGASHZJY9PKUT7ZAME9FI4QGCO8NVSSUIOHYZQBXAXLP9NCILYCUTUL6MOIRGWG72ADVNDSAENMEJATRMXXRMSD';

        $data = [
            'mchId' =>  '20000020',
            'appId' =>  'a2cc53dd4c25408986b3c585868041f9',
            'mchOrderNo'   =>  $notifyData['mchOrderNo'],
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,['params'=>json_encode($data)]),true);
        Log::notice('query MxPay  API notice:'.json_encode($result));
        if(  $result['retCode'] != 'SUCCESS' ){
            Log::error('query MxPay  API Error:'.$result['retMsg']);
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
        $url = $this->pay($params,'8006');
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
        Log::notice("MxPay notify data1".json_encode($notifyData));

        if($notifyData['status'] =='2' || $notifyData['status'] =='3'  ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['mchOrderNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('MxPay API Error:'.json_encode($notifyData));
    }


}
