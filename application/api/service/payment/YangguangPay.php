<? /*d by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/17
 * Time: 15:46
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\BaseException;
use app\common\library\exception\OrderException;
use think\Log;


class YangguangPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ALIPAY_WAP'){


        $data = array(
            'userId' => '80050',
            'channel' => $type,
            'amount' => sprintf("%.2f",$order["amount"]),
            'request_id'  => $order['trade_no'],
            'return_url' => $this->config['return_url'],
            'notify_url' => $this->config['notify_url'],
            'request_time' => date('YmdHis'),
            'request_ip' => get_userip(),
            'goods_name' => 'goods',
            'remark' => 'remark'
        );

        $secret = 'd1149b90a0cbc1313fec930010b5c274';

        $url = 'http://pay.sunbeampay.com/API/Pay/Gateway.aspx';

        $data['sign'] = $this->getSign($data, $secret);

        $result =  json_decode(self::curlPost($url,$data),true);

        if($result['rescode'] != '0000' )
        {
            Log::error('Create YangguangPay API Error:'.$result['resMsg']);
            throw new OrderException([
                'msg'   => 'Create YangguangPay API Error:'.$result['resMsg'],
                'errCode'   => 200009
            ]);
        }
        return $result['qrcode'];
    }


    /**
     * 签名
     */
    public function getSign($parameters, $secret){

        ksort($parameters);
        unset($parameters['sign_type']);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = substr($signPars, 0, strlen($signPars)-1) . $secret;
        $sign = md5($signPars);
        return $sign;

    }



    /**
     * @param $params
     */
    public function wap_zfb($params)
    {
        $url = $this->pay($params,'ALIPAY_WAP');
        return [
            'request_url' => $url,
        ];
    }


    public function test($params)
    {
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @return mixed
     */
    public function notify()
    {
        $notifyData =$_GET;
        Log::notice("YangguangPay notify data1".json_encode($notifyData));
        if($notifyData['orderstatus'] == "1" ){
            echo "ok";
            $data['out_trade_no'] = $notifyData['request_id'];
            return $data;
        }
        echo "error";
        Log::error('YangguangPay API Error:'.json_encode($notifyData));
    }

}

