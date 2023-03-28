<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/2
 * Time: 18:33
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class LengfengPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='tbxjhb'){

        $url = 'http://103.88.34.98:5566/api/gateway/createOrder';

        $data = [
            'merchantId'  =>  '2020110662718304',
            'merchantOrderId'  =>  $order['trade_no'],
            'channelCode'  =>  $type,
            'notifyUrl'  =>  $this->config['notify_url'],
          //  'goodsName'  =>  'goods',
            'amount'  =>  sprintf("%.2f",$order["amount"]),
        ];
        $key = 'c2fb356faa9a35cb77b9cf3f329d8552';

        $data['sign'] = $this->getSign($data,$key);

 $data = json_encode($data);
        $headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($data),
            "Accept: application/json"
        );
//        {"code":200,"msg":"\u8bf7\u6c42\u6210\u529f","data":{"merchantOrderId":100,"acceptanceAlipayUrl":"https:\/\/www.bole-houtai.com\/api.php\/order\/PcPay.html?id=101200"}}
        $result =  self::curlPost($url,$data,$headers);




//var_dump($result);die();
         $result =  json_decode($result ,true);
        if($result['code'] != '0' )
        {
            Log::error('Create TwogPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create TwogPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }





    public function getSign($data,$key){
        ksort($data);

   //     $data['key'] = $key;
        $tmp_str = '';
        foreach ($data as $k=>$v) {
            $tmp_str .= "{$k}={$v}&";
        }
        $tmp_str = substr($tmp_str,0,strlen($tmp_str) - 1);
        return strtoupper(md5($tmp_str.$key));
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params);
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
        $notifyData =$_POST;
           $input = file_get_contents("php://input");
        Log::notice("LengfenPay notify data".$input);
        $notifyData = json_decode($input,true);

        Log::notice("TwogPay notify data".json_encode($notifyData));
        if(1){
            echo "success";
            $data['out_trade_no'] = $notifyData['merchantOrderId'];
            return $data;
        }
        echo "FAIL";
        Log::error('TwogPay API Error:'.json_encode($notifyData));
    }

}
