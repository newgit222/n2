<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 13:48
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class HawkeyePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='12'){
        $url = 'http://118.25.70.159:6080/hipay/openapi';

        $merkey = '3F96ABC19B583D1C0FE60AAAE32A01CD';

        $data = [
            'tradeNo'   => $order['trade_no'],
            'reqCmd'   =>  'req.trade.order',
            'merchNo'   =>  '444441000003',
            'charset'   =>  'utf-8',
            'signType'   =>  'MD5',
            'reqIp'   =>  get_userip(),
            'payType'   =>  $type,
            'amount'   =>  sprintf("%.2f",$order["amount"]),
            'goodsName'   =>  'goods',
            'goodsDesc'   =>  'goods',
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'currency'   =>  'CNY',
            'remark'   =>  'goods',
        ];

        $data['sign'] = $this->getSign($data,$merkey);


        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '10000' )
        {
            Log::error('Create HawkeyePay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create HawkeyePay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['bankUrl'];
    }

    private function query($notifyData){
        $url = 'http://118.25.70.159:6080/hipay/openapi';

        $merkey = '3F96ABC19B583D1C0FE60AAAE32A01CD';

        $data = [
            'reqCmd'   =>  'req.query.trade',  
            'merchNo'   =>  '444441000003',
            'charset'   =>  'utf-8',
            'signType'   =>  'MD5',
            'reqIp'   =>  get_userip(),
            'tradeNo'   =>  $notifyData['tradeNo'],
            'remark'   =>  'goods',
            'amount'    =>  '123'
        ];


        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query HawkeyePay  API notice:'.json_encode($result));
        if(  $result['code'] != '10000' ){
            Log::error('query HawkeyePay  API Error:'.$result['msg']);
            return false;
        }
        if($result['status'] != '4' ){
            return false;
        }
        return true;
    }



    private function getSign($data,$secret )
    {
//        $data['token']  = $secret;

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
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'12');
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
        $input = file_get_contents("php://input");
        Log::notice("HawkeyePay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['code'] == '10000' ){
            if($notifyData['status'] == '4' ){
                if($this->query($notifyData)) {
                    echo "SUCCESS";
                    $data['out_trade_no'] = $notifyData['tradeNo'];
                    return $data;
                }
            }

        }
        echo "error";
        Log::error('HawkeyePay API Error:'.$input);
    }


}