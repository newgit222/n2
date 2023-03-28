<?php
/*reated by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/15
 * Time: 13:48
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ChuangshijiV2Pay extends ApiPayment
{
    /**
     * ç»Ÿä¸€ä¸‹å•
     */
    private function pay($order,$type='12'){
        $url = 'https://api.bixin88.com/api/order/placeOrder';
        $merkey = '4692492e2eaa4ad6abf60b543e1d2f2b';
        $data = [
            'merchno'   =>  '371c72015e',
            'orderId'   => $order['trade_no'],
            'amount'    =>  sprintf("%.2f",$order["amount"]),
            'payType'   =>  $type,
            'attach'   =>  'goods',
            'asyncUrl'   =>  $this->config['notify_url'],
            'syncUrl'   =>  $this->config['return_url'],
            'requestTime'   => date("YmdHis"),
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $data['request_post_url'] = $url;
        return "http://www.wantongpays.com/pay.php?".http_build_query($data);
    }


    /**
     * @param $params
     * æ”¯ä»˜å®
     */
    public function h5_zfb($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,8);
        return [
            'request_url' => $url,
        ];
    }
    public function guma_zfb($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,8);
        return [
            'request_url' => $url,
        ];
    }
public function guma_yhk($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,12);
        return [
            'request_url' => $url,
        ];
    }

  public function test($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params,12);
        return [
            'request_url' => $url,
        ];
    }


    /**
     * ç”Ÿæˆç­¾å
     * @param $data
     * @param $secret
     * @return string
     */
    private function getSign($data,$secret )
    {
        //ç­¾åæ­¥éª¤ä¸€ï¼šæŒ‰å­—å…¸åºæŽ’åºå‚æ•°
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $sign = md5($string_a.'secretKey='.$secret);
        return $sign;
    }




    /**
     * @return mixed
     * å›žè°ƒ
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("ChuangshijiV2Pay notify data".json_encode($notifyData));
        if($notifyData['status'] ==1|| 1){
            echo "success";
            $data['out_trade_no'] = $notifyData['orderId'];
            return $data;
        }
        echo "error";
        Log::error('ChuangshijiV2Pay API Error:'.json_encode($notifyData));
    }


}
?>
