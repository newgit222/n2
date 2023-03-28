<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/11
 * Time: 23:47
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class XiongmaoyouPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='aliscan'){
$data = array(
	'appid' => '673898235',
	'orderid'=>$order['trade_no'],
	'money'=>sprintf("%.2f",$order["amount"]),
	'goodsname'=>'goodsname',
	'paycode'=>$type,
	'device'=>'pc',
	'clientip'=>'127.0.0.1',
	'notifyurl'=>$this->config['notify_url'],
	'returnurl'=>$this->config['return_url'],
	'remark'=>'mark',
	'istype'=>1,
	'signtype'=>2
);

   


        $merkey = '0d6c2bb053ca3029e905112887db8031';
        $url = 'https://www.xm765.com/pay/api';
        $data['sign'] =$this->getSign($merkey,$data);
//	var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
//var_dump($result);die();
        if(!$result['state'] )
        {
            Log::error('Create WanlaPay API Error:'.$result['errMsg']);
            throw new OrderException([
                'msg'   => 'Create WanlaPay API Error:'.$result['errMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['url'];
    }


    /**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    private function getSign($appkey, $array)
    {
ksort($array);
		$buff = "";
		foreach ($array as $k => $v){			if($v != "" && !is_array($v) && $k!='sign'){
				$buff .= $k . "=" . $v . "&";
			}
		}
		$buff .='appkey='.$appkey;
		return strtolower(md5($buff));



//	return md5($data['time'].'&'.$data['trade_id'].'&'.$secret);
        // 去空
//        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
//        $string_a = http_build_query($data);
//        $string_a = urldecode($string_a);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a,0,strlen($string_a) - 1);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp =   $secret.$string_a .$secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params,'8');
        return [
            'request_url' => $url,
        ];
    }

  public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
  public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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

        $notifyData = $_POST;
        Log::notice("XiongmaoyouPay notify data".json_encode($notifyData));
$data = $_POST['data'];
$data=json_decode(base64_decode($data,true),true);
Log::notice("XiongmaoyouPay notify data".json_encode($data));
        if($data['status'] == "1" ){
            echo "success";
            $data['out_trade_no'] = $data['orderid'];
            return $data;
        }
        echo "error";
        Log::error('WanlaPay API Error:'.json_encode($notifyData));
    }
}
