<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/1
 * Time: 1:19
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\logic\EwmOrder;
use app\common\logic\Orders;
use app\common\model\Config;
use think\Log;


/**
 * 跑分二维码支付
 * Class GumaV2Pay
 * @package app\api\service\payment
 */
class GumahahaPay extends ApiPayment
{

    /**
     * var string $secret_key 加解密的密钥
     */
    protected $secret_key  = 'f3a59b69324c831e';

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv = '7fc7fe7d74f4da93';

    /**
     * 统一下单
     */
    private function pay($params, $type = self::GUMA_YHK, $is_bzk = false)
    {

        //直接出码取得码的信息
        $money = sprintf('%.2f', $params['amount']);
        $EwmOrderLogic = new EwmOrder();
        $configModel = new Config();
        $config =  $configModel->where('name','daifu_ms_id')->find();
        $member_id = $this->config['remarks'];
        if ($config && $config['value'] && $type == 3){
            $member_id = $config['value'];
        }
        $response = $EwmOrderLogic->createOrder($money, $params['trade_no'], $type, $params['out_trade_no'], 1, $this->config['notify_url'], $member_id,$params['body']);
        if ($response['code'] != 1) {
            Log::error('Create GumaV2Pay API Error:' . ($response['msg'] ? $response['msg'] : ""));
            throw new OrderException([
                'msg' => 'Create GumaV2Pay API Error:' . ($response['msg'] ? $response['msg'] : ""),
                'errCode' => 200009
            ]);
        }
		$code = $response['data']['code'];
		if($type == 4)
		{
			$data['qun_image'] = $code['image_url'];
			$data['account_name'] = $code['account_name'];
			$data['trade_no'] = $params['trade_no'];
			$data['order_pay_price'] = $response['data']['money'];
			return "http://test.zhongtongzhifu.com/test/pay3.php?" . http_build_query($data);
		}
        
        $data['is_bzk'] = $is_bzk;
        $data['account_name'] = $this->encrypt($code['account_name']);
        $data['bank_name'] = $this->encrypt($code['bank_name']);
        $data['account_number'] = $this->encrypt($code['account_number']);
        $data['trade_no'] = $params['trade_no'];
        $data['order_pay_price'] = $response['data']['money'];
        $data['key'] = config('inner_transfer_secret');
        $data['sign'] = $this->getSign($data);
        unset($data['key']);
        $paofenPayUrl = db('config')->where(['name' => 'thrid_url_gumapay'])->value('value');;
//        halt("{$paofenPayUrl}?" . http_build_query($data));
        return "{$paofenPayUrl}?" . http_build_query($data);
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

    public function guma_bzk($params)
    {
        $data = $this->pay($params, 3, 1);
        return [
            'request_url' => $data
        ];
    }
 public function yhk($params)
    {
        $data = $this->pay($params, 3, 1);
        return [
            'request_url' => $data
        ];
    }


    public function guma_yhk($params)
    {
        $data = $this->pay($params, 3);
        return [
            'request_url' => $data
        ];
    }

    public function test($params)
    {
        $data = $this->pay($params, 3);
        return [
            'request_url' => $data
        ];
    }
 public function wap_vx($params)
    {
        $data = $this->pay($params, 4);
        return [
            'request_url' => $data
        ];
    }


    public function notify()
    {
        //跑分平台秘钥
        $data["out_trade_no"] = $_POST['out_trade_no'];
        echo "SUCCESS";
        return $data;
    }
}
