<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/26
 * Time: 23:46
 */

namespace app\api\service\withdraw;


use app\api\service\ApiPayment;
use app\api\service\ApiWithdraw;
use app\common\model\Config;
use think\Log;
use think\Request;

class Paofen extends ApiWithdraw
{

    private $key;
    private $url;
    private $successUrl;
    private $adminlistUrl;


    public function __construct()
    {
        $this->initConfig();
    }


    /**
     * 初始化配置
     */
    private function initConfig(){
        $Config = new Config();
        $this->key = $Config->where(['name'=>'balance_cash_key'])->value('value');
        $this->url = $Config->where(['name'=>'balance_cash_url'])->value('value');
        $this->successUrl = $Config->where(['name'=>'balance_cash_successurl'])->value('value');
        $this->adminlistUrl = $Config->where(['name'=>'balance_cash_adminlist'])->value('value');
        $this->revocationUrl = $Config->where(['name'=>'balance_cash_revocation'])->value('value');
    }

    /**
     * 下单
     */
    public function orders($order,$admin_id){

        $request = Request::instance();
        $data = [
            'cash_no'   =>  $order['cash_no'],
            'price'   =>  sprintf("%.2f", $order['amount']),
            'notify_url'    =>  $request->domain().'/api/notify/notifywithdraw/channel/Paofen',
            'account'   =>  $order['account'],
            'account_name'   => $order['account_name'],
            'method'   =>  $order['method'],
            'admin_id'  =>  $admin_id
        ];

        $data['sign'] = $this->getSign($data);
        Log::notice(json_encode($data));
        $result = json_decode($this->curlPost($this->url,$data),true);
        if( !$result || $result['code'] != '1' ){
            Log::notice(json_encode($result));
            return ['code'=>'0','msg'=>isset($result['msg'])?$result['msg']:'跑分平台下单地址错误'];
        }
        return ['code'=>'1','msg'=>'请求成功'];
    }


    /**
     * 撤回
     */
    public function revocation($order){

        $data = [
            'cash_no'   =>  $order['cash_no'],
        ];
        $data['sign'] = $this->getSign($data);
        Log::notice(json_encode($data));
        $result = json_decode($this->curlPost($this->revocationUrl,$data),true);
        if( !$result || $result['code'] != '1' ){
            Log::notice(json_encode($result));
            return ['code'=>'0','msg'=>isset($result['msg'])?$result['msg']:'跑分平台撤回地址错误'];
        }
        return ['code'=>'1','msg'=>'请求成功'];
    }


    /**
     * 回调
     */
    public function notify(){
        $input = file_get_contents("php://input");
        Log::notice("Paofen notifywithdraw data".$input);
        $notigyData = json_decode($input,true);

        $result = $this->checkSign($notigyData);
        if(!$result){
            echo "ERROR";
            Log::error(" sign验证错误 Paofen notifywithdraw data".$input);
        }else {
            //返回
            $data = [
                'cash_no' => $notigyData['cash_no'],
                'voucher' => $notigyData['withdraw_voucher'],
                'voucher_time'  =>  $notigyData['voucher_time']
            ];

            echo "SUCCESS";
            return $data;
        }
    }

    /**
     * 确认订单 推送跑分
     */
    public function successOrder($order){
        $data = [
            'cash_no'   =>  $order['cash_no'],
        ];

        $data['sign'] = $this->getSign($data);
        Log::notice(json_encode($data));
        $result = json_decode($this->curlPost($this->successUrl,$data),true);
        if( !$result || $result['code'] != '1' ){
            Log::notice(json_encode($result));
            return ['code'=>'0','msg'=>isset($result['msg'])?$result['msg']:'跑分平台确认地址错误'];
        }
        return ['code'=>'1','msg'=>$result['msg']];
    }

    /**
     * 获取跑分平台商户列表
     */
    public function getList(){

        $data = [
            'time'  =>time(),
        ];
        $data['sign'] = $this->getSign($data);
        Log::notice(json_encode($data));
        $result =  json_decode($this->curlPost($this->adminlistUrl,$data),true);
        Log::notice(json_encode($result));
        return $result;
    }




    /**
     * 获取sign
     */
    public function getSign($data){
        if(isset($data['sign'])){
            unset($data['sign']);
        }
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }


    /**
     * 验证sign
     */
    public function checkSign($data){
        if(!isset($data['sign']) || !$data['sign'] ){
            return false;
        }
        $sign = $this->getSign($data);
        if($sign != $data['sign']){
            return false;
        }
        return true;
    }

}