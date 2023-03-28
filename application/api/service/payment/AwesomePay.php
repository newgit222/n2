<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/4
 * Time: 16:13
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class AwesomePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='xjhb'){



        $data = [
            'fxid'  =>  '2020104',
            'fxddh'  =>  $order['trade_no'],
            'fxdesc'  =>  'goods',
            'fxfee'  => sprintf("%.2f",$order["amount"]),
            'fxnotifyurl'  =>  $this->config['notify_url'],
            'fxbackurl'  =>  $this->config['return_url'],
            'fxpay'  =>  $type,
            'fxip'  => '127.0.0.1',
            'fxbankcode'    =>  '',
            'fxfs'    =>  '',
            "fxattch" => 'mytest',
        ];

        $fxkey = 'xiyLMLCeBAgTfRrEApRJPbUhbZUCSDUl';

        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $fxkey);

        $url = 'http://www.awesomepay.cn/Pay';
//        return $url.'?'.urldecode(http_build_query($data));
        $result =  self::getHttpContent($url,'POST',$data);
	$result = json_decode($result,true);
//	var_dump($result);die();
         if($result['status'] != 1 )
        {
            Log::error('Create AwesomePay API Error:'.$result['error']);
            throw new OrderException([
                'msg'   => 'Create AwesomePay API Error:'.$result['error'],
                'errCode'   => 200009            ]);
        }
        return $result['payurl'];
    }


    public function getHttpContent($url, $method = 'GET', $postData = array()) {
        $data = '';
        $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36';
        $header = array(
            "User-Agent: $user_agent"
        );
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
                if(strstr($url,'https://')){
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                }

                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = '';
            }
        }
        return $data;
    }



    /**
     * @param $params
     * 支付宝
     */
    public function zfb_h51234($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbewm');
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
        Log::notice("AwesomePay notify data".$input);
	Log::notice("AWESOMEPAY notify data".json_encode($_REQUEST));
        $notifyData = json_decode($input,true);
        if($_REQUEST['fxddh'] ){
            echo "success";
            $data['out_trade_no'] = $_REQUEST['fxddh'];
            return $data;
        }
        echo "error";
        Log::error('AwesomePay API Error:'.$input);
    }
}
