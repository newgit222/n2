<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\logic\EwmOrder;
use app\common\logic\Orders;
use app\common\model\Config;
use think\Db;
use think\Log;
use think\Request;


class GumaV2Pay extends ApiPayment
{
    /**
     * var string $secret_key 加解密的密钥
     */
    protected $secret_key  = '8e70f72bc3f53b12';

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv = '99b538370c7729c7';


    public function USDT($params)
    {
        $codeType = Db::name('pay_code')->where('code','USDT')->find();
        if (!$codeType){
//            return $this->error('未识别的通道');
        }
        $data = $this->pay($params, $codeType['id'],false,$codeType['code']);
        return [
            'request_url' => $data
        ];
    }


    private function pay($params, $type, $is_bzk = false,$codes='kzk'){
        //直接出码取得码的信息
        $money = sprintf('%.2f', $params['amount']);
        $EwmOrderLogic = new EwmOrder();
        $configModel = new Config();
        $userModel = new \app\common\model\User();
        $user = $userModel->where(['uid'=>$params['uid']])->find();
//        Log::error('接口商户：'.json_encode($user,true));
        $response = $EwmOrderLogic->createOrder($money, $params['trade_no'], $type, $params['out_trade_no'],1, $this->config['notify_url'],$user['pao_ms_ids'],$params['body']);
        if ($response['code'] != 1) {
            Db::name('orders')->where('trade_no',$params['trade_no'])->update(['remark'=>$response['msg']]);
            Log::error('Create GumaV2Pay API Error:' . ($response['msg'] ? $response['msg'] : ""));
            throw new OrderException([
                'msg' => 'Create GumaV2Pay API Error:' . ($response['msg'] ? $response['msg'] : ""),
                'errCode' => 200009
            ]);
        }
        $code = $response['data']['code'];
        $order = Db::name('ewm_order')->where('order_no',$params['trade_no'])->find();
        $data['is_bzk'] = $is_bzk;
        $data['account_name'] = $this->encrypt($code['account_name']);
        $data['bank_name'] = $this->encrypt($code['bank_name']);
        $data['account_number'] = $this->encrypt($code['account_number']);
        $data['trade_no'] = $params['trade_no'];
        $data['order_pay_price'] = $response['data']['money'];
        $data['key'] = config('inner_transfer_secret');
        $data['sign'] = $this->getSign($data);
        $data['user'] = $this->encrypt($_SERVER['HTTP_HOST']);
        $data['remark'] = $order['id'];
        $data['is_pay_name'] = 2;
        unset($data['key']);
        $zhongzhuan_url = 'http://47.96.133.197/';
        if ($codes == 'USDT'){
            $Config = new Config();
            $data['usdt_rate'] = $this->encrypt($Config->where(['name'=>'usdt_rate'])->value('value'));
            $data['extra'] = $this->encrypt($order['extra']);
            Db::name('orders')->where('trade_no',$params['trade_no'])->update(['remark'=>$zhongzhuan_url.'usdtTrc.php?'. http_build_query($data)]);
            return $zhongzhuan_url.'usdtTrc.php?'. http_build_query($data);
//            return [
//                'request_url' => $zhongzhuan_url.'usdtTrc.php?'. http_build_query($data)
//            ];
        }
    }


    public function notify(){
        //        //跑分平台秘钥
        $data["out_trade_no"] = $_POST['out_trade_no'];
        echo "SUCCESS";
        return $data;
    }

    private function encrypt($data)
    {
        return base64_encode(openssl_encrypt($data,"AES-128-CBC",$this->secret_key,true,$this->iv));

    }



    /**
     * 生成签名
     * @param $args
     * @return string
     */
    protected function getSign($args)
    {
        ksort($args);
        $mab = '';
        foreach ($args as $k => $v) {
            if ($k == 'sign' || $k == 'key' || $v == '') {
                continue;
            }
            $mab .= $k . '=' . $v . '&';
        }
        $mab .= 'key=' . $args['key'];
        return md5($mab);
    }




}