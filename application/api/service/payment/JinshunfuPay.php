<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 23:06
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JinshunfuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){



        $url = 'http://api.payjf.org/recharge';

        $merkey = '1QVZYOW264W61YSH3C4M48ESUQEJ1O7B';

        $data = [
            'merCode'   =>  'xiaoxiannv666',
            'saveWayCode'   =>  $type,
            'bankCode'   =>  $type,
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'notifyUrl'   =>  $this->config['notify_url'],
            'merOrderId'   =>  $order['trade_no'],
        ];
        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create JinshunfuPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create JinshunfuPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['rechargeUrl'];
    }


    private function query($notifyData){
        $url = 'http://api.payjf.org/queryMerOrder';
        $merkey = '1QVZYOW264W61YSH3C4M48ESUQEJ1O7B';
        $data = [
            'merCode' =>  'xiaoxiannv666',
            'merOrderId'   =>  $notifyData['merOrderId'],
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query JinshunfuPay  API notice:'.json_encode($result));
        if(  $result['code'] != '200' ){
            Log::error('query JinshunfuPay  API Error:'.$result['message']);
            return false;
        }
        if($result['data']['stat'] != '2'   ){
            return false;
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
//        $result = strtoupper($sign);

        return $sign;
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
     * 微信
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbh5');
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
        Log::notice("JinshunfuPay notify data1".json_encode($notifyData));


        if($notifyData['stat'] =='2'   ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['merOrderId'];
                return $data;
            }
        }
        echo "error";
        Log::error('YitwoPay API Error:'.json_encode($notifyData));
    }
}