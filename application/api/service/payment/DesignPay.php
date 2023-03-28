<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/3
 * Time: 3:06
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\migration\command\seed\Run;

class DesignPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='2'){


        $url = 'http://121.40.246.105:2019/pay/create.do';
        $merkey = 'e0b302a553bd271d449caf5f32d505e9';

        $data = [
            'appId'  =>  '20200503030717030345120606530015',
            'userId'  =>  '123',
            'orderId'  =>  $order['trade_no'],
            'amount'  =>  sprintf("%.2f",$order["amount"]),
        ];

        $data['sign'] = $this->getSign($data,$merkey);

         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['mvpStatus'] != true )
        {
            Log::error('Create DesignPay API Error:'.$result['mvpError']);
            throw new OrderException([
                'msg'   => 'Create DesignPay API Error:'.$result['mvpError'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['url'];
    }


    private function query($notifyData){
        $url = 'http://121.40.246.105:2019/pay/query.do';

        $merkey = 'e0b302a553bd271d449caf5f32d505e9';

        $data = [
            'appId' =>  '20200503030717030345120606530015',
            'userId' =>  '123',
            'orderId' =>  $notifyData['orderId'],
            'date' =>  date("Y-m-d H:i:s"),
        ];
        $data['sign'] =$this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query DesignPay  API notice:'.json_encode($result));
        if($result['mvpStatus'] != true ){
            Log::error('query DesignPay  API Error:'.$result['mvpError']);
            return false;
        }
        if(  $result['data']['status'] == '2'  ){
            return true;
        }
        return false;
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
        $url = $this->pay($params,'2');
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
        Log::notice("DesignPay notify data2".json_encode($notifyData));
        if(isset($notifyData['orderId'])   ){
            if($this->query($notifyData)) {
                echo "OK";
                $data['out_trade_no'] = $notifyData['orderId'];
                return $data;
            }
        }
        echo "NO";
        Log::error('DesignPay API Error:'.json_encode($notifyData));
    }

}