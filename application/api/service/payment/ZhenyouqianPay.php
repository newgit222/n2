<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/9
 * Time: 21:41
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class ZhenyouqianPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='zfbh5'){


        $url = 'http://api.p5ckq.cn/pay';

        $merkey = 'QcHZDaOqFoUweRtudivfGBjMXELCkghn';

        $data = [
            'merId' =>  '202005162',
            'orderId' =>  $order['trade_no'],
            'orderAmt' =>  sprintf("%.2f",$order["amount"]),
            'channel' =>  $type,
            'desc' =>  'goods',
            'smstyle' =>  '1',
            'ip' =>  get_userip(),
            'notifyUrl' =>  $this->config['notify_url'],
            'returnUrl' =>  $this->config['return_url'],
            'nonceStr' =>  $this->createNonceStr(),
        ];

        $data['sign'] = $this->sign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '1' )
        {
            Log::error('Create ZhenyouqianPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create ZhenyouqianPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payurl'];
    }




    public function query($notifyData){

        $url = 'http://api.p5ckq.cn/pay/query';

        $key = 'QcHZDaOqFoUweRtudivfGBjMXELCkghn';
        $data=array(
            'merId'=>'202005162',
            'nonceStr'=>$this->createNonceStr(),
            'orderId'=>$notifyData['orderId']
        );
        $data['sign'] = $this->sign($data,$key);

         $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query ZhenyouqianPay  API notice:'.json_encode($result));
        if(  $result['code'] != '1' ){
            Log::error('query ZhenyouqianPay  API Error:'.$result['msg']);
            return false;
        }
        if(!isset($result['data'])){
            return false;
        }
        if($result['data']['status'] != '1' ){
            return false;
        }
        return true;
    }




    /**
     * 签名算法
     * @param $data         请求数据
     * @param $md5Key       md5秘钥
     */
    function sign($data,$md5Key)
    {
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $val) {
            //空值不参与签名
            if ($val == '' || $key == 'sign') {
                continue;
            }
            $arg .= ($key . '=' . $val . '&');
        }
        $arg = $arg . 'key=' . $md5Key;

        //签名数据转换为大写
        return strtoupper(md5($arg));
    }







    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'zfbewm');
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
        $notifyData = $_POST;
        Log::notice("ZhenyouqianPay notify data1".json_encode($notifyData));
        if($notifyData['status'] == "1" ){
            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['orderId'];
                return $data;
            }
        }
        echo "error";
        Log::error('ZhenyouqianPay API Error:'.json_encode($notifyData));
    }

}