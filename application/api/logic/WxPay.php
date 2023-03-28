<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 9/1/19
 * Time: 3:31 PM
 */

namespace app\api\logic;


class WxPay
{
    public function getWxNativeRequestlink($code,$tradeNo)
    {
        $WxPay =new \app\ownpay\controller\Index();
        $token = $WxPay->makeVxQr($code);
        return config('custom.wx_pay_link')."?no=".$tradeNo."&token=".$token;
    }

    public function getWxH5Requestlink($url,$amount)
    {
        return config('custom.wx_h5_pay_link')."?amount=".$amount."&redict_url=".urlencode($url);
    }
}