<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/8
 * Time: 16:40
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;
use think\migration\command\seed\Run;

class LingdangPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='1019'){
        $url = 'http://api.59di.cn/order/create'; 
        $key = 'de3d35b9e88c91f904c3721ade399277';
        $data = [
            'mch_id'   =>  '1093',
           /// 'order_time'   =>  date('YmdHis'),
            'amount'   =>  sprintf("%.2f",$order["amount"]),
           // 'product_name'   =>  'goods',
            'pay_type_id'   =>  $type,
            'out_order_no'   =>  $order['trade_no'],
            'ip'   =>  get_userip(),
           // 'redirect_url'   =>  'http://103.210.239.133/fan.php',
            'notify_url'   =>$this->config['notify_url'],
           // 'remark'   =>  'goods',
        ];
        $data['sign'] = $this->getSign($data,$key);
//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' ){
            Log::error('Create LuckyPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create LuckyPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];
    }
    public function getSign($parameters,$key){
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = $signPars.'key='.$key;
        $sign = md5($signPars);
        return $sign;
    }


    public function query($notifyData){
return true;
        $url = 'http://api.jutong688.com/layPayment/orderQuery';

        $key ='c76b32ad09bb4a4fa1c60137d80df6c0';
        $data=array(
            'merchant_no'=>'20090214195914700',
            'order_no'=>$notifyData['order_no']
        );
        $data['sign'] = $this->getSign($data,$key);

        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query LuckyPay  API notice:'.json_encode($result));
        if(  $result['returnCode'] != '0' ){
            Log::error('query LuckyPay  API Error:'.$result['message']);
            return false;
        }
        if($result['platformPayStatus'] != 'success' ){
            return false;
        }
        return true;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_yhk($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }

 /*
     *�~T���~X��~]产�~S~A
     * @param $params
     * @return array
     * @throws OrderException
     */
     public function wap_vx($params)
    {
        //�~N��~O~V��~D��~K�~M~U
        $response = self::getPayUnifiedOrder($params);
        return [
            'request_url' => $response['data']['url'],
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
        $notifyData =$_POST;
        Log::notice("jutongpay notify data1".json_encode($notifyData));
        $input = file_get_contents("php://input");
        Log::notice("chaonima data".$input);
         $notifyData = json_decode($input,true);
//        {"order_no":"115863372782628","platformNo":"00720040817143840801","merchant_no":"00820031516405816300","sign":"6ede62b786f908c3e8ca6d6ac52f1851","order_money":"700.00","platformPayStatus":"success","remark":"goods","order_time":"20200408171438","platformPayTime":"20200408172959","product_name":"goods"}
        if(1 ){
           if(1) {
                echo "ok";
                $data['out_trade_no'] = $notifyData['out_order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('luck2pay API Error:'.json_encode($notifyData));
    }

}
