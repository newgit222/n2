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

namespace app\api\logic;
use app\api\service\ApiPayment;
use app\api\service\payment\Qpay;
use app\api\service\payment\Rongjupay;
use app\common\library\exception\OrderException;
use app\common\logic\Config;
use think\Exception;
use think\Log;

/**
 * 支付处理类  （优化方案：提出单个支付类  抽象类对象处理方法 便于管理）
 *
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
class DoPay extends BaseApi
{
    /**
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $orderNo
     *
     * @return mixed
     * @throws \Exception
     */
    public function pay($orderNo)
    {

        //todo
        //检查支付状态
        $order = $this->modelOrders->checkOrderValid($orderNo);
        //创建支付预订单
        return $this->prePayOrder($order);
    }

    /**
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $orderNo
     *
     * @return mixed
     * @throws \Exception
     */
    public function dafiupay($orderNo)
    {
        //检查支付状态
        $order = $this->modelDaifuOrders->checkOrderValid($orderNo);

        //创建支付预订单
        return $this->preDaifuPayOrder($order);
    }

    /**
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $order
     *
     * @return mixed
     * @throws OrderException
     */
    private function preDaifuPayOrder($order){
        //渠道和参数获取
        $appChannel = $this->logicPay->getDaifuAllowedAccount($order);
        if (isset($appChannel['errorCode'])){
            Log::error($appChannel['msg']);
            throw new OrderException($appChannel);
        }

        //取出数据
        list($payment,$action,$config) = array_values($appChannel);


        //带付分发
        $result = ApiPayment::$payment($config)->$action($order->id, $order->amount, 1, $order->bank_number, $order->bank_owner);
        return $result;
    }

    /**
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $order
     *
     * @return mixed
     * @throws OrderException
     */
    private function prePayOrder($order){
        //渠道和参数获取
        $appChannel = $this->logicPay->getAllowedAccount($order);
        if (isset($appChannel['errorCode'])){
            Log::error($appChannel['msg']);
            throw new OrderException($appChannel);
        }
       // dd($appChannel);
        //取出数据
        list($payment,$action,$config) = array_values($appChannel);

        //获取当前毫秒时间
        $start_time = msectime();

        //支付分发
        $result = ApiPayment::$payment($config)->$action($order);

        $elapsed_time = msectime() - $start_time;

        $request_elapsed_time = $elapsed_time/1000;

        if($result && $request_elapsed_time == '0' ){
            $request_elapsed_time = '0.001';
        }

        $this->modelOrders->setInfo(['id'=>$order['id'],'request_elapsed_time'=>$request_elapsed_time]);


        return $result;
    }
}