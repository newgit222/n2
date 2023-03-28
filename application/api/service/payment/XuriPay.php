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

class XuriPay extends ApiPayment
{
    /**
     * ç»Ÿä¸€ä¸‹å•
     */
    private function pay($order,$type='41'){
        $url = 'http://113.31.103.44//index/payapi.html';
        $merkey = '7f3d4d8fb2cb41ad463c0c6122390e39';
        $data = [
            'apiAccount'   =>  'ximen888',
            'orderNo'   => $order['trade_no'],
            'tradeAmount'    =>  sprintf("%.2f",$order["amount"]),
            'payType'   =>  $type,
         //   'attach'   =>  'goods',
            'backUrl'   => 'http://www.baidu.com',
            'ip'   =>  '127.0.0.1',
       //     'requestTime'   => date("YmdHis"),
        ];
$str ='orderNo='.$data['orderNo'].'&tradeAmount='.$data['tradeAmount'].'&payType='.$data['payType'].'&apiAccount='.$data['apiAccount'].'&backUrl='.$data['backUrl'].'&ip='.$data['ip'];
//echo $str;
        $data['token'] =md5(md5($str).'2021020907250134773278');
//echo $str;
//echo  $url.'?'.http_build_query($data);die();
       // $data['ca'] = $url;
        return $url.'?'.http_build_query($data);
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
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

  public function test($params)
    {
        //èŽ·å–é¢„ä¸‹å•
        $url = $this->pay($params);
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
        $sign = mad5(md5($string_a.'secretKey='.$secret));
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
        if(1){
            echo "OK";
            $data['out_trade_no'] = $notifyData['orderNo'];
            return $data;
        }
        echo "error";
        Log::error('ChuangshijiV2Pay API Error:'.json_encode($notifyData));
    }


}
?>
