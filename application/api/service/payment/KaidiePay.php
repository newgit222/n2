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

class KaidiePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='801H'){
        $url = 'http://api.zhangfupay.cn/layPayment/pay'; 
        $key ='c451f8b2ddbd4cb18b301979a526fc83';
        $data = [
            'merchant_no'   =>  '20100810354114500',
            'order_time'   =>  date('YmdHis'),
            'order_money'   =>  sprintf("%.2f",$order["amount"]),
            'product_name'   =>  'goods',
            'pay_type_id'   =>  $type,
            'order_no'   =>  $order['trade_no'],
            'pay_ip'   =>  get_userip(),
            'redirect_url'   =>  $this->config['return_url'],
            'notify_url'   =>  $this->config['notify_url'],
            'remark'   =>  'goods',
        ];
        $data['sign'] = $this->getSign($data,$key);
//var_dump(self::curlPost($url,$data));die();
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['returnCode'] != '0' ){
            Log::error('Create LuckyPay API Error:'.$result['message']);
            throw new OrderException([
                'msg'   => 'Create LuckyPay API Error:'.$result['message'],
                'errCode'   => 200009
            ]);
        }
        return $result['content'];
    }
    public function getSign($parameters,$key){
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = substr($signPars,0,strlen($signPars) - 1).$key;
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
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }
public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'701H');
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
     public function guma_vx($params)
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
//        {"order_no":"115863372782628","platformNo":"00720040817143840801","merchant_no":"00820031516405816300","sign":"6ede62b786f908c3e8ca6d6ac52f1851","order_money":"700.00","platformPayStatus":"success","remark":"goods","order_time":"20200408171438","platformPayTime":"20200408172959","product_name":"goods"}
        if($notifyData['platformPayStatus'] == "success" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('luck2pay API Error:'.json_encode($notifyData));
    }

}
