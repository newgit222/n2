<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/2
 * Time: 19:40
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FengshangPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='66'){

        $url = 'http://fs.ali88.fun/pay/ap.php';
        $data = [
            'cpid'  =>  '4297',
            'cp_trade_no'  =>  $order['trade_no'],
            'fee'  =>  sprintf("%.2f",$order["amount"])*100,
            'jump_url'  =>  $this->config['return_url'],
            'notify_url'  =>  $this->config['notify_url'],
            'pay_type'  =>  $type,
//            'bank_code' =>  'ICBC',
        ];
        $key = 'xaalo5s1ew1fc1';
        $data['sign'] = $this->getSign($data,$key);
        $param_str='';
        foreach($data as $k=>$value)
        {
            $param_str .=$k.'='.$value.'&';
        }
        $param_str = trim($param_str,'&');
        $to_pay_url = $url.'?'.$param_str;

        $result =  json_decode(self::curlPost($to_pay_url,[],null,20),true);
        if($result['result_code'] != '0' )
        {
            Log::error('Create FeitianPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create FeitianPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_info'];
    }



    /**
     * 查询接口
     */
    public function query($notifyData){
        $url = 'http://3.0.154.75:9081/pay/payresult.php';
        $data = [
            'cpid'  =>  '2043',
            'cp_trade_no'  =>  $notifyData['cp_trade_no'],
            'pay_day'  =>  date('Y-m-d'),
        ];
        $key = '4bk77994or4wxs';
        $data['sign'] = $this->getSign($data,$key);
        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        Log::notice('query FeitianPay  API notice:'.json_encode($result));
        if( !isset($result['result_code']) ||  $result['result_code'] != '0' ){
            Log::error('query FeitianPay  API Error:');
            return false;
        }
        return true;
    }



    public function getSign($parameters,$key){
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $key;
        $sign = strtoupper(md5($signPars));
        return $sign;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params,'66');
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
        Log::notice("FeitianPay notify data".$input);

        $notifyData = json_decode($input,true);
        if($notifyData['result_code'] == '0' ){
           // $res = $this->query($notifyData);
            if(1){
                echo "success";
                $data['out_trade_no'] = $notifyData['cp_trade_no'];
                return $data;
            }
        }
        echo "FAIL";
        Log::error('FeitianPay API Error:'.$input);
    }

}
