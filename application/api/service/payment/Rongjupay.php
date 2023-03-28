<?php
/**
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\api\service\payment;

use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use app\common\library\exception\SignatureException;
use think\Log;

class Rongjupay extends ApiPayment
{
    /**
     * 支付宝扫码支付
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $order
     *
     * @return array
     * @throws OrderException
     * @throws SignatureException
     */
    protected  $merchantCode = 'M880129';

    protected $private = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAM11od3w/sk7NccOToBcDt5zNMw3AAIQDjTbfXdSURi1HmWFeEb7aGGnVWk461hdls9T5zFouOAs7iEM5ZE6YkPb3WgWTuJ5KOZ/A9p0qcdbNi2bg2wq8gjeJ4juowUIfOZI1pjpzif6HK/Ff6LxQocpDKTmP1cHvSXSNx+vNfcvAgMBAAECgYEAwaJcdDQ9m99etoRoPZcFOGcxWkl3i3ogOXamz37YCUYhKKLakWM9o9M1rt5DB1vk1u8HU8yTeEs//gwOi4mPsx2sHwmOuSvKpWdwimiucIpGesF/zsKjAJrd0XAR5b9KbND2roSh70t0K7oZTP7+tenpmpXYWKZazW8CZDIRYiECQQDx2efEm8tDMUUHnYuGc3POAxjQvG90/hd1AhBVnhYVPRi5iR5mdP9cfuH4ZsbnmGQJgiyz8S3+zAwF0LvyHAhfAkEA2Xq0ikJ8yKvUtA7kZsRNuJlbU9EUGhH0Pwvlkl6lqG6I1H4vgi6FoWC/ORWH6SNL81KYqRCc60SJCjpRVYjDMQJAepLEyzlmcByFbtGjCDZ1R8D8D7ahCntI6i1sl+fyKzt2z3m2JZlAXuGTpzB3uKLceJE4FM60UAGcSko7Jv6cIwJBAMe6pWwEu80f4eyrcJ/g0PAkB3fmoGuA6autJV1Thxg0y307li4cw6T90SB/4Z3/SJVi2ckK9NIs0x6NiHzhaUECQQDHyDCDKF8dKbFEF7BJyB5r7PHx0kOgViiuV2ltqmTXKT7FnEjyLGmi/5EhYFITRQ5sNEeod1AbPg7r+Vh7JhIN
-----END PRIVATE KEY-----';
    protected $bankCode = 'ALIPAY'; // ==AA== 需要换成自己的
    protected $notifyUrl = 'http://a.com/notifyUrl.php';
    protected $returnUrl = 'http://a.com/returnUrl.php';
    protected $postUrl = 'http://api.rjpay.net/gateway';
    protected $charset = "UTF-8";
    protected $orderNo = "";
    protected $amount = 0;

    public function yinlian($order){
       // $this->orderNo = $order['trade_no'];
        $this->orderNo = $order['trade_no'];
        $this->amount = $order['amount'] = 100;
        $remark = "";
        $signData = "charset={$this->charset}&merchantCode={$this->merchantCode}&orderNo={$this->orderNo}"
            ."&amount={$this->amount}&channel=BANK&bankCode={$this->bankCode}&remark={$remark}"
            ."&notifyUrl={$this->notifyUrl}&returnUrl={$this->returnUrl}&extraReturnParam={$this->orderNo}";

        $pi_key = openssl_pkey_get_private($this->private);
        openssl_sign($signData, $sign, $pi_key, OPENSSL_ALGO_SHA1);
        openssl_sign($signData, $sign, $pi_key, OPENSSL_ALGO_SHA1);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);

        $sign = str_replace( '%2F', '/', $sign );
        $sign = str_replace( '%3D', '=', $sign );
        $sign = str_replace( '%2B', '+', $sign );

        $data=array(
            'charset' => $this->charset,
            'merchantCode' => $this->merchantCode,
            'orderNo' => $this->orderNo,
            'amount' => $this->amount,
            'channel' => 'BANK',
            'bankCode' => $this->bankCode,
            'remark' => $remark,
            'notifyUrl' => $this->notifyUrl,
            'returnUrl' => $this->returnUrl,
            'extraReturnParam' => $this->orderNo,
            'signType' => 'RSA',
            'sign' => $sign,
        );

        $response = $this->curlPost($this->postUrl,$data);

        return [
            'order_qr' => $result['qr_code']
        ];
    }

    public function notify()
    {
        $data['order_id'] = "aaa";
        $data['name'] = "bbb";
        return $data;
    }

    public function  daifu($orderId, $amount, $bankId, $accountNumber, $accountName)
    {

    }

    public function  daifuQuery($order)
    {

    }

    public function getThridOrder($orderId)
    {
        return 'AAA' . str_pad($orderId, 20, '0', STR_PAD_LEFT);
    }


    /**
     * 平台和三方银行对应字典
     * @return array
     */
    private function getBankDict()
    {
        return [
            1  => ['code' => 'CMB', 'name' => '招商银行'],
            2  => ['code' => 'CMBC', 'name' => '中国民生银行'],
            3  => ['code' => 'CCB', 'name' => '中国建设银行'],
            4  => ['code' => 'ABC', 'name' => '中国农业银行'],
            5  => ['code' => 'ICBC', 'name' => '中国工商银行'],
            6  => ['code' => 'COMM', 'name' => '交通银行'],
            8  => ['code' => 'SPDB', 'name' => '浦发银行'],
            // 9  => ['code' => 'GDB', 'name' => '广发银行'],
            11 => ['code' => 'SPABANK', 'name' => '平安银行'],
            13 => ['code' => 'CIB', 'name' => '兴业银行'],
            14 => ['code' => 'CITIC', 'name' => '中信银行'],
            15 => ['code' => 'HXB', 'name' => '华夏银行'],
            16 => ['code' => 'CEB', 'name' => '中国光大银行'],
            // 17 => ['code' => 'PSBC', 'name' => '中国邮政储蓄银行'],
            20 => ['code' => 'BOC', 'name' => '中国银行'],
        ];
    }
}