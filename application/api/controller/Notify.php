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

namespace app\api\controller;;

use app\api\service\ApiPayment;
use app\api\service\ApiWithdraw;

use app\common\controller\BaseApi;
use app\common\library\enum\CodeEnum;
use app\common\library\exception\ForbiddenException;
use app\common\library\exception\OrderException;
use app\common\logic\BalanceCash;
use app\common\model\Orders;
use GuzzleHttp\Client;
use think\Exception;
use think\Log;

class Notify extends BaseApi
{

    /**
     * 个人收款配置 【等待开发】
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function person($channel = 'wxpay'){

        $apiurl = $this->request->request("apiurl");
        $sign = $this->request->request("sign");

        //验证签名
        if ($sign != md5(md5($apiurl))) {
            $this->result("签名密钥不正确");
        }
        $this->result("配置成功");
        echo $channel;
    }

    /**
     * 同步回调 【不做数据处理 获取商户回调地址返回就行了】
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param string $channel
     *
     */
    public function callback($channel = 'wxpay'){
        //默认跳转
        $result['return_url'] = "https://www.yeeshopping.com";
        //支付分发

        $result = ApiPayment::$channel()->callback();

        $this->redirect($result['return_url']);
    }


    public function  test()
    {
        $logicNotify = new \app\api\logic\Notify();
        $order = $this->modelOrders->where(['id'=>1])->lock(true)->find();


        $logicNotify->updateOrderInfo($order,1);

    }

    /**
     * 统一异步通知
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param string $channel
     *
     */
    public function notify($channel = 'wxpay'){

        //如果ｐｏｓｔ里面有ｃｈａｎｅｌ的话,以get为准
        if(!empty($_GET['channel']) && $channel!=$_GET['channel'])
        {
            $channel = $_GET['channel'];
        }
        $channel = explode("?",$channel)[0];
        $logicNotify = new \app\api\logic\Notify();
        $checkret  =  $logicNotify->checkNotifyIp($channel);
        if($checkret['code'] == CodeEnum::ERROR)
        {
//            echo 'success.';die();
            throw new OrderException([
                'msg'   =>$checkret['msg'],
                'errCode'   => 200011
            ]);
        }

        $result = ApiPayment::$channel()->notify();

        Log::notice('处理结果:'.json_encode($result));

        $timeStart = time();
        $res = $logicNotify->handle($result);
        $timeFinish = time();

        Log::notice($result['out_trade_no']." finish order take:".($timeFinish-$timeStart)." s");

       // return $res;

    }

    public function notifyWithdraw($channel="Paofen"){
        if(!empty($_GET['channel']) && $channel!=$_GET['channel'])
        {
            $channel = $_GET['channel'];
        }
        try{
            $channel = explode("?",$channel)[0];
            $modelName = '\app\api\service\withdraw\\'.$channel;
            $model = new $modelName();
            $result = $model->notify();
            Log::notice('处理结果:'.json_encode($result));
        }catch (Exception $e){
            Log::error($e);
        }
        //将单号设置为待确认

        $BalanceCash = new BalanceCash();
        $res = $BalanceCash->setConfirmed($result);
        Log::notice('提现处理'.json_encode($res));
    }


    /**
     * 代付回调
     */
    public function daifuNotify(){
        if(!isset($_GET['channel']) && empty($_GET['channel'])  )
        {
            exit("error1");
        }
        $channel = $_GET['channel'];
        try{
            $channel = explode("?",$channel)[0];
            $logicNotify = new \app\api\logic\Notify();
            $checkret  =  $logicNotify->checkNotifyIp($channel);
            if($checkret['code'] == CodeEnum::ERROR)
            {
                throw new Exception($checkret['msg']);
            }
            $result = ApiPayment::$channel()->daifuNotify();
            Log::notice('处理结果:'.json_encode($result));
            //提现订单处理成功
            $BalanceCash = new BalanceCash();
            $res = $BalanceCash->orderSuccess($result);
            Log::notice('提现处理'.json_encode($res));
        }catch (Exception $e){
            echo "error";
            Log::error($e);
        }

    }








}
