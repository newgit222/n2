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

class XunhePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='15'){


        $url = 'http://api.xunhe.in:5858/go/gateway.go?format=json';
        $merkey = '86b42b1676da8cf38c5759debe107f51';

        $data = [
            'pid' =>  '11880019',
            'type' =>  '',
            'cid' =>  $type,
            'oid' =>  $order['trade_no'],
            'uid' =>  '333',
            'amount' =>  sprintf("%.2f",$order["amount"])*100,
            'nurl' =>  $this->config['notify_url'],
		'burl'=>'2',
            'eparam' =>  'goods',
            'ip' =>  '127.0.0.1',
            'stype' =>  'MD5',
//'format'=>'json'
        ];
        $data['sign'] = $this->getSign($data,$merkey);
$data['format']='json';
//var_dump(self::curlPost($url,$data));die();

        $result =  json_decode(self::curlPost($url,$data,null,15),true);
        if($result['code'] != '101' )
        {
            Log::error('Create YitwoPay API Error:'.$result['retMsg']);
            throw new OrderException([
                'msg'   => 'Create YitwoPay API Error:'.$result['retMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payurl'];
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
        $result = strtolower($sign);

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
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        $notifyData =$_GET;
        Log::notice("YitwoPay notify data1".json_encode($notifyData));
        if(1  ){
            if(1) {
                echo "Success";
                $data['out_trade_no'] = $notifyData['oid'];
                return $data;
            }
        }
        echo "error";
        Log::error('YitwoPay API Error:'.json_encode($notifyData));
    }
}
