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

class LuckyPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='701H'){

        $url = 'http://api.tianqfu.cn/layPayment/pay'; 


        $key = '25928b25f6154219bd26a8382eecfc67';

<<<<<<< HEAD
    /*
    *  taobao _pay  统一下单
    *
    */
    private function getPayUnifiedOrder($order, $type = 'wechat_qrcode')
    {
        header("Content-type: text/html; charset=utf8");
        $data = "";
        $data['merchant_order_uid'] = '3716';
        //商户订单号
        $data['merchant_order_sn'] = $order['trade_no'];
        $data['merchant_order_money'] = sprintf('%.2f', $order["amount"]);
        $data['merchant_order_channel'] = $type;
        $data['merchant_order_date'] = date("Y-m-d H:i:s");
        $data['merchant_order_callbak_confirm_duein'] = $this->config['notify_url'];
        $data['merchant_order_sign'] = $this->getSign($data);
        $json = json_encode($data, 320);
        $headers = array(
            "Content-type: application/json;charset='utf8'",
            "Accept: application/json",
        );
=======
        $data = [
            'merchant_no'   =>  '00820031516405816300',
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
>>>>>>> a15a33bb0cd408275dfe46271c4ab8116bd57004

        $data['sign'] = $this->getSign($data,$key);

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
//        $signPars .=  $key;
        $sign = md5($signPars);
        return $sign;
    }


    public function query($notifyData){

        $url = 'http://api.tianqfu.cn/layPayment/orderQuery';

        $key = '25928b25f6154219bd26a8382eecfc67';
        $data=array(
            'merchant_no'=>'00820031516405816300',
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
    public function wap_zfb($params)
    {
        //获取预下单
<<<<<<< HEAD
        $response = self::getPayUnifiedOrder($params,'alipay_qrcode');
=======
        $url = $this->pay($params,'701H');
>>>>>>> a15a33bb0cd408275dfe46271c4ab8116bd57004
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
<<<<<<< HEAD
            $notifyData = $_POST;
		Log::error('oc支付平台通知参数' .file_get_contents('php://input'). '超时处理');
             $notifyData1 = json_decode(file_get_contents('php://input'), true);
          $notifyData = $notifyData1['data'];
            Log::notice("Luckpay notify data".json_encode($_POST));
            $sign=$notifyData['sign'];
            unset($notifyData['sign']);

            $mysign = $this->getSign($notifyData);

            if($mysign == $sign && $notifyData1['code']==200){
                //处理业务逻辑
                echo 'success';
                $data["out_trade_no"] = $notifyData['merchant_order_sn'];
                return $data;
            }
            throw new OrderException([
                'msg' => 'Create QH API Error:',
                'errCode' => 200009
            ]);
=======
        $notifyData =$_POST;
        Log::notice("HougePay notify data1".json_encode($notifyData));
//        {"order_no":"115863372782628","platformNo":"00720040817143840801","merchant_no":"00820031516405816300","sign":"6ede62b786f908c3e8ca6d6ac52f1851","order_money":"700.00","platformPayStatus":"success","remark":"goods","order_time":"20200408171438","platformPayTime":"20200408172959","product_name":"goods"}
        if($notifyData['platformPayStatus'] == "success" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['order_no'];
                return $data;
            }
        }
        echo "error";
        Log::error('HougePay API Error:'.json_encode($notifyData));
>>>>>>> a15a33bb0cd408275dfe46271c4ab8116bd57004
    }

}
