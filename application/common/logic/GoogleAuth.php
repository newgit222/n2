<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2019/12/21
 * Time: 14:56
 */

namespace app\common\logic;



class GoogleAuth extends BaseLogic
{

    protected $googleObj = null;

    public function __construct()
    {
        if(is_null($this->googleObj))
        {
            vendor('PHPGangsta/GoogleAuthenticator');
            $this->googleObj = new \PHPGangsta_GoogleAuthenticator();;
        }
    }

    /*
     *
     * 生成密钥
     */
    public function createSecretkey(){

        return  $this->googleObj->createSecret();
    }

    /*
     *
     * 获取二维码
     */
    public function getQRCodeGoogleUrl($secret){
        return  $this->googleObj->getQRCodeGoogleUrl('pay', $secret);
    }


    /*
     *校验google验证码
     *
     */
    public function  checkGoogleCode($google_secretkey,$code)
    {
        return $this->googleObj->verifyCode($google_secretkey, $code, 2);
    }







}