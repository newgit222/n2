<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 20:07
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class BolePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$appid,$type='alipay'){
        $data['merchantNo'] = '20200222190722999';
        $data['appid'] = $appid;
        $data['merchantOrderId'] = $order['trade_no'];
        $data['userId'] =  $order['trade_no'];
        $data['userName'] = 'test';
        $data['amount'] = sprintf("%.2f",$order["amount"]);
        $data['payType'] = $type;
        $url = 'https://www.bolezhifu-api.com/order/Create';
        $data = json_encode($data);
        $headers = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($data),
            "Accept: application/json"
        );
//        {"code":200,"msg":"\u8bf7\u6c42\u6210\u529f","data":{"merchantOrderId":100,"acceptanceAlipayUrl":"https:\/\/www.bole-houtai.com\/api.php\/order\/PcPay.html?id=101200"}}
        $result =  self::curl($url,$data,$headers);
        if($result['code'] != 200 )
        {
            Log::error('Create BolePay API Error:'.json_encode($result));
            throw new OrderException([
                'msg'   => 'Create BolePay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['acceptanceAlipayUrl'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'2020022220583010088999','alipay');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'20200222223211100889100','alipay');
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
        $url = $this->pay($params,'20200222223211100889100','alipay');
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
        Log::notice("BolePay notify data".$input);
        $notifyData = json_decode($input,true);
        if($notifyData['code'] == "200" ){
            echo "success"; 
            $data['out_trade_no'] = $notifyData['merchantOrderId'];
            return $data;
        }
        echo "error";
        Log::error('hgpay API Error:'.$input);
    }





    /**
     * 发起远程请求函数
     *
     */
    public function curl($url = '', $data = '', $header = null)
    {
        if (empty($url) || empty($data)) {
            print_r('请求地址或传值不能为空');
            die();
        }
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '0');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, '0');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $return = json_decode(curl_exec($ch), true);//运行curl
        curl_close($ch);
        return $return;
    }

}
