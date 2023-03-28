<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/27
 * Time: 20:06
 */

namespace app\api\service\payment;

use app\common\library\exception\OrderException;
use app\api\service\ApiPayment;
use think\Log;

class XiongmaoPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1'){

        $data = [
            'appid' =>  '4',
            'money' => sprintf("%.2f",$order["amount"]),
            'code' =>  '1',
            'rand_str' => getRandChar('8','CHAR'),
            'trade_no' =>  $order['trade_no'],
            'notify' => $this->config['notify_url'],
            'callback' => '1',// $this->config['return_url'],
        ];
        $merkey = 'W3JOBKG6EKJ3PS6K';
        $url = 'http://47.52.44.249/pay/index';
        ksort($data);
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1);
        $data['sign'] = md5($tmp_str.$merkey);//签名
        $data['pay_other_type'] = $type;
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '200' )
        {
            Log::error('Create Zhuque API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create Zhuque API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }





    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'1');
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
        $url = $this->pay($params,'1');
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
        Log::notice("XiongmaoPay notify data".$input);
	Log::notice("xiongmao post data".json_encode($_POST));
      //  $notifyData = json_decode($input,true);
//        if($notifyData['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $_POST['trade_no'];
            return $data;
  //      }
        echo "error";
        Log::error('XiongmaoPay API Error:'.$input);
    }
}
