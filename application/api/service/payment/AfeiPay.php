<? /*d by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/17
 * Time: 15:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class AfeiPay extends ApiPayment
{
    /**
     * ç»Ÿä¸€ä¸‹å•
     */
    private function pay($order,$type='alipay_scan'){

       // $url = 'http://cy.w810.cn:808/index/Pay/order';
        $url ='http://cy.youxin123.cn/index/pay/create_order';
        $merkey ='ccde7065f6994a18455c772dfe51eadd';
        $data = [
            'merchant_id'   =>  '20201202144625352',
            'orderid'   =>  $order['trade_no'],
            'amount'   =>  (int)sprintf("%.2f",$order["amount"]),
            'notify_url'   =>  $this->config['notify_url'],
            'pay_type'   =>  $type,
        ];
        $sign = 'merchant_id='.$data['merchant_id'].'&orderid='.$data['orderid'].'&amount='.$data['amount'].'&notify_url='.$data['notify_url'].'&key='.$merkey;
        $data['sign'] = md5($sign);
        $result =  json_decode(self::curlPost($url,($data),null,115),true);

//var_dump($result);die();
        if($result['code']!=1)
        {
            Log::error('Create Jack API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create Jack API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }



    /**
     * @param $params
     * æ”¯ä»˜å®
     */
    public function wap_zfb($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,1001);
        return [
            'request_url' => $url,
        ];
    }

 public function ali_sdk($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,1004);
 
$header = array(
       'Accept: application/json',
    );
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 超时设置,以秒为单位
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
 
    // 超时设置，以毫秒为单位
    // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36');
    // 设置请求头
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //执行命令
    $data = curl_exec($curl);
$re= json_decode($data,true);
//var_dump($re['orderInfo']);die();
        return [
            'request_url' => $re['orderInfo'],
        ];
    }

 public function test($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,1001);
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @return mixed
     * å›žè°ƒ
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("Jack notify data1".json_encode($notifyData));
        if($notifyData['status'] == "1" ){
            echo "ok";
            $data['out_trade_no'] = $notifyData['orderid'];
            return $data;
        }
        echo "error";
        Log::error('Jack API Error:'.json_encode($notifyData));
    }

}

