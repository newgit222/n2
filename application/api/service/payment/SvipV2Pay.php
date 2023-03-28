<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/3
 * Time: 22:31
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class SvipV2Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay'){


        $url = 'http://1234567890pay.com/pay.php';
        $user_account = 'test';		//商户在SVIP的账号
        $key = 'E9piyrjeLJLzPT1v0VrQEVoqbBS6iEZQ';				//密钥

        $params = array(
            'notify_url'	=>$this->config['notify_url'],
            'return_url'	=> $this->config['return_url'],
            'user_account'	=>	$user_account,
            'out_trade_no'	=> $order['trade_no'],
            'payment_type'	=> $type,
            'total_fee'		=> sprintf("%.2f",$order["amount"]),
            'trade_time'	=> date('Y-m-d H:i:s', time()),
            'body'			=> 'goods',
        );

        $params['sign'] = $this->getSign($params, $key);
        $params['request_post_url']= $url;
        return "http://cc.byfbqgi.cn/pay.php?".(http_build_query($params));
    }



    /**
     * 查询接口
     */
    public function query($notifyData){

        $url = 'http://1234567890pay.com/query.php';
        $user_account = 'test';		//商户在SVIP的账号
        $key = 'E9piyrjeLJLzPT1v0VrQEVoqbBS6iEZQ';				//密钥

        $params = array(
            'user_account'	=>	$user_account,
            'out_trade_no'	=> $notifyData['out_trade_no'],
        );

        $params['sign'] = $this->getSign($params, $key);
//        return self::curlPost($url.'?'. http_build_query($params));
         $result = json_decode(self::curlPost($url.'?'. http_build_query($params)),true);
        Log::notice('query SvipV2Pay  API notice:'.json_encode($result));
        if( $result['status'] != 'SUCCESS' ){
            Log::error('query SvipV2Pay  API Error:'.$result['msg']);
            return false;
        }
        if($result['order_status'] != '1' ){
            Log::error('query SvipV2Pay  API Error: 订单未付款');
            return false;
        }
        return true;
    }



    public function getSign($data,$key){
        //签名步骤一：按字典序排序参数
        ksort($data);
        //签名步骤二：使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串
        $string = $this->_to_url_params($data);
        //签名步骤三：在string后加入KEY
        $string = $string . "&key=".$key;
        //签名步骤四：MD5加密
        $string = md5($string);
        //签名步骤五：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }


    public function _to_url_params($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
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
        Log::notice("SvipV2Pay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['status'] == 'SUCCESS' ){
            $res = $this->query($notifyData);
            if($res){
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['out_trade_no'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('SvipV2Pay API Error:'.$input);
    }
}