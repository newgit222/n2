<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/14
 * Time: 20:15
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/**花花富支付
 * Class HhfPay
 * @package app\api\service\payment
 */
class HhfPay extends ApiPayment
{
    protected $publicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl5cM19IYCSbrGegYhzZpmlrOQu/hRLpwfIc085CeiwIVDbxzqC/MbUd0rDEaUGPm/ey8G1LvCB4gadNqfyYyoQR87z/X7BXez3Ivj2X2aLcFMalBwD5NkM1ylyJ4qToC7cu4voir+K1p3xkEdYlq+jNwCMoKamtyBnZ0me6WfO0D+j347FiRGuLOAPW1abCWCC7Ux9fjNGsGrut26KCBJZl7zgvx+vrkZj605pm5jsc70yNZaFnJezZKFV0/u0HuOmGeCHds25n7u6UAS3la29pqx5aaImXqwjS/FIw+B5EbJ0ahRfudQzm0H0kqQy0HrHkstUd6zTJjSkii7lM7ewIDAQAB';
//商户私钥
    protected $privateKey = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCV2X6oyReaoEDJt1PVQ6tMz9Q+s2ZybDIwx6XUfRwXbaxcNCphs1tqYAp5AMcPRnv4X7PpzyczFV2vQZoF/F8BrqxaG3R3HtTRbC/05B52IAZ/OyUcUH8twDMUUGzHEW030NGdZ7+SD+uD8dBQZ0iVAiz3VStDkkiNkmicyOQ+yfv0yDGnggn6QGvhskgXlCB33LXCjX1ht7FvmBFWbESpLg4o+GovA9OJG3zA5Rr1vlEvEli1GQRfLtWzzCMAxvamQt87uEOzviMDziGu5YwTzBN6I7qF4jotm7YmYxUb0g0VLzRJMMTN7uog42zcN+E9BX/6DNG2V1N/edOQp0UHAgMBAAECggEAPHpFJjXSsvNOcprs2Luw3RVb4dph1HhaYVmSUgoUVlhLIgNjv361vF76mw93R3D5A6sMX6bdeT58Swk88oGCjplCsjM2dAUbe1IgoMOYj7ApLxxdw+mxCnPxqZcz77vWypoan16J8JdAREZJ/slQf+Ma5s+W4HSfl5OpaO2jQLuABFRVhEJnctoCbgxY8Jz4zxa9Z8CMKP5T60KJF4CiOeClReYE9quRf6AWYpexvu4C1OzF0fkVCYc8Kati5COOHOfXm3Lq55ur6+b45NvyMBQcyqrghgmJ8qrGY34McXac3Cp7lCUn9wnN/uBFWS24J6TGv4GMU9KEhRq2s2hRMQKBgQDnlzeUwBrwE9b+a9ZoVM6StF4Y/aIcUQm887kQj5JCF0coNFpwCp9dzyJAWBKosk0NRHjojOSNl2828WBxv3xsyMBXJZ62nOB9/HI5vQdpUKYeTHxG98XD2vb0KBs9FHtX5Ng6WkFOAyKu/t4zkRcIaWYE7Wy3+WSMbeWsrkCaQwKBgQClpLz7NPLiBdV8osyIlfDrbtSI2IdhXCYYyGLSm/pW3a1gbHv1Qeg5JV8r2jdaQR82TJb495IaztSlKHAZtiWQTomXj0VzNBvr61IuVFlzbICPi4pEuJSX9rptfwWLuhy/uyNGhiW4rrZsuDr1GmVM/u9/9p0BRs19/6RWImLn7QKBgECWDn0geiK8FbhMkLX1+Mo3HQrxKRWmkAWvHsx8jbh6z3Bp8VLP05QQ4Sd6qHF9kDmEAAgyysamFfEHrfLrCSM5dVKYfkPFSmI4Vg6+JeP8BufqQrpz/SZa2YZL7RTCsodXnengI9hCzZhqIcPV0cuZwUbcg/ZmyeDUATqAOioLAoGAT6sxXd10qHNH4B9pIpSDgKkYvgzu6Evq4uaWaVeuC1OpjQQAhtDBQaMQ6EWXc+h4e6RQxMRGWHB8ZEdTy3oJDSRJF0VIkQVSHKFdMH1PXK2mE+R4h18D0SdROVLrtu234BvA9q2UvQQDJ71gVfzxFhxT0wcpMrD8Kwrm3qrBSW0CgYBXZcBiBYpiDRWx3d0lbaAfoZF8Z+wOWyKT2+oL84rxfupy7bfOwoNcSEbI7HEYfc1eAXibGHJ97NXNzVt2CALAa64bUGplkq6Lo6jR7sW+VYXcXi8a61WnMGfqblqy/T+R8lcTyEw1XDxb5nXGwE1RTzaTJ9Xggt6VYbtPXONTqw==';


    protected $md5Key = 'QhbeHLdUkuiSAtozpqRNJOEnvIXwsBca';

    /**
     * 生成随机字符串
     * @param string $type
     * @param int $len
     * @return false|string
     */
    public static function buildNonceStr($type = 'alnum', $len = 8)
    {
        switch ($type) {
            case 'alpha':
            case 'alnum':
            case 'numeric':
            case 'nozero':
                switch ($type) {
                    case 'alpha':
                        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'alnum':
                        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'numeric':
                        $pool = '0123456789';
                        break;
                    case 'nozero':
                        $pool = '123456789';
                        break;
                }
                return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
            case 'unique':
            case 'md5':
                return md5(uniqid(mt_rand()));
            case 'encrypt':
            case 'sha1':
                return sha1(uniqid(mt_rand(), true));
        }
    }


    /**
     * 生成签名
     * @param $data
     * @param $md5Key
     * @param $privateKey
     * @return mixed
     */
    public function getSign($data, $md5Key, $privateKey)
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
        $sig_data = strtoupper(md5($arg));
        //私钥签名
        return $this->sign($sig_data);
    }


    /**
     * 设置私钥
     * @return bool
     */
    private function setupPrivKey()
    {
        if (is_resource($this->privateKey)) {
            return true;
        }
        $pem = chunk_split($this->privateKey, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $this->privateKey = openssl_pkey_get_private($pem);
        return true;
    }


    /**
     * 构造签名
     * @param string $dataString 被签名数据
     * @return string
     */
    public function sign($dataString)
    {
        $this->setupPrivKey();
        $signature = false;
        openssl_sign($dataString, $signature, $this->privateKey,OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }





    /**
     * 统一下单
     */
    private function pay($order, $type = 'Wechat')
    {
        //请求格式
        $merId = '20210133';
        $data  = [
            'merId'     => $merId,               //商户号
            'orderId'   => $order['trade_no'],            //订单号，值允许英文数字
            'orderAmt'  => sprintf("%.2f", $order["amount"]),              //订单金额,单位元保留两位小数
            'channel'   => $type,            //支付通道编码
            'desc'      => 'goods',           //简单描述，只允许英文数字 最大64
            'attch'     => 'goods',             //附加信息,原样返回
            'smstyle'   => '1',               //用于扫码模式（sm），仅带sm接口可用，默认0返回扫码图片，为1则返回扫码跳转地址。
            'userId'    => '',                 //用于识别用户绑卡信息，仅快捷接口可用。
            'ip'        => request()->ip(),          //用户的ip地址必传，风控需要
            'notifyUrl' => $this->config['notify_url'],   //异步返回地址
            'returnUrl' => $this->config['return_url'],     //同步返回地址
            'nonceStr'  => $this->buildNonceStr()   //随机字符串不超过32位
        ];
        //私钥签名
        $data['sign'] = $this->getSign($data, $this->md5Key, $this->privateKey);
        $url          = 'https://api.casystar.cn/pay';
        $result       = json_decode(self::curlPost($url, $data), true);
        if ($result['code'] != '1') {
            Log::error('Create HhfPay API Error:' . $result['msg']);
            throw new OrderException([
                'msg'     => 'Create HhfPay API Error:' . $result['msg'],
                'errCode' => 200009
            ]);
        }
        return $result['data']['payurl'];
    }


    /**
     * 微信扫码支付
     * @param $params
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, 'Wechat');
        return [
            'request_url' => $url,
        ];
    }




    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'Wechat');
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
        Log::notice("HhfPay notify data" . $input);
        $notifyData = $_POST;
        if ($notifyData['status'] ==1) {
            echo "success";
            $data['out_trade_no'] = $notifyData['orderId'];
            return $data;
        }
        echo "error";
        Log::error('HhfPay API Error:' . $input);
    }
}

