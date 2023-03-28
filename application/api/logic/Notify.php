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

use app\common\library\enum\CodeEnum;
use app\common\logic\Config;
use app\common\model\PayChannel;
use think\Db;
use think\Exception;
use think\Log;
use app\common\library\enum\OrderStatusEnum;

class Notify extends BaseApi
{
    /**
     * 支付回调助手
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return bool
     *
     */
    public function handle($data){
        Db::startTrans();
        try{
            //获取支付订单号
            $trade_no = $data['out_trade_no'];

            $where['trade_no'] = $trade_no;

            //查找订单
            $order = $this->modelOrders->where($where)->lock(true)->find();
            //第四方回调通知时间
            $fourNotifyMaxTime = $this->logicConfig->getConfigInfo(['name'=>'four_noticy_time'],'value');
            $fourNotifyMaxTime =(empty($fourNotifyMaxTime) && empty($fourNotifyMaxTime['value']))?20:$fourNotifyMaxTime['value'];

            ///超时判断 （超时10分钟当作失败订单）  判断状态
            if (($order->status == 1 || $order->status == 0) && bcsub(time(), strtotime($order->create_time)) <= $fourNotifyMaxTime*60) {

                Log::notice('更新订单状态:'.$order->out_trade_no);
                //更新订单状态
                $res = $this->updateOrderInfo($order, true);
                if(empty($res))
                {
                    Db::rollback();
                    return false;
                }
                Log::notice('异步消息商户:'.$order->out_trade_no);
                //异步消息商户
                $this->logicOrdersNotify->saveOrderNotify($order);
                Log::notice('提交队列'.$order->out_trade_no);
                $this->logicQueue->pushJobDataToQueue('AutoOrderNotify' , $order , 'AutoOrderNotify');
                //提交更改
                Db::commit();
                return true;
            }
            //订单超时写入到补单备注
            $res = $this->modelOrders->changeOrderStatusValue([
                'bd_remarks'    => '单号' . $trade_no . '超时处理',
            ], [
                'id'=>$order->id
            ]);
            if(empty($res))
            {
                Log::error("update Order error".'单号'. $order->out_trade_no);
                return false;
            }
            Db::commit();
//            Db::rollback();
            Log::error('单号' . $trade_no . '超时处理');
            return true;
        } catch (Exception $ex) {
            Db::rollback();
            Log::error('错误'.$ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 更新支付订单数据
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $order
     * @param $success
     */
    public function updateOrderInfo($order, $success)
    {
        /*************订单操作************/
        //1.查找用户对应渠道费率

        $profit = $this->logicUser->getUserProfitInfo(['uid' => $order->uid, 'cnl_id' => $order->cnl_id]);

        $account = $this->logicPay->getAccountInfo(['id' => $order->cnl_id]);

        if(empty($profit)) $profit = $account;

        //2.数据计算
        //实付金额 - 扣除渠道费率后

//        $income =  bcsub($order->amount , bcmul($order->amount,$account['rate'],3),  3);
//
//        $agent_in = "0.000";
//        //商户收入
//        $user_in =bcmul($income, $profit['urate'], 3);

        /***  zhangxiaohei 3-31 update 这里读取配置  单笔手续费  直接计算分成 不扣除通道费率  **/

//        $income =  bcsub($order->amount , bcmul($order->amount,$account['rate'],3),  3);

        $income = $order->amount;
        $agent_in = "0.000";
        //商户收入
        $user_in =bcmul($income, $profit['urate'], 3);

        //读取配置  扣除单笔手续费
     //   $is_single_handling_charge = \app\common\model\Config::where(['name'=>'is_single_handling_charge'])->find()->toArray();
       // if($is_single_handling_charge){
         //   if($is_single_handling_charge['value'] == 1){
           //     $user_in = bcsub($user_in,$profit['single_handling_charge'],3);
          //  }
      //  }

        /** end **/

        //是否有代理
        if ($order->puid != 0){
            //1.获取代理的费率
            $agent_profit = $this->logicUser->getUserProfitInfo(['uid' => $order->puid, 'cnl_id' => $order->cnl_id]);

            //2.代理收入
            $agent_in = bcmul($income, bcsub($agent_profit['urate'], $profit['urate'],3), 3);
            $agent_in = $agent_in>0 ? $agent_in : 0;

            if($agent_in>0)
            {
                $this->logicBalanceChange->creatBalanceChange($order->puid, $agent_in,'商户单号'. $order->out_trade_no . '支付成功，代理分润金额转入','enable',false);
            }
            /**************写入商户代理资金结束*****************/
        }

        /*************写入商户资金******************/
        //支付成功  扣除待支付金额 (这个操作就只有两个地方   自动关闭订单和这里)
        // $this->logicBalanceChange->creatBalanceChange($order->uid,$order->amount,'单号'. $order->out_trade_no . '支付成功，收入至待结算金额','disable',true);
        //支付成功  写入结算金额
        $res =  $this->logicBalanceChange->creatBalanceChange($order->uid,$user_in,'单号'. $order->out_trade_no . '支付成功，金额转入','enable',false);
        if(empty($res))
        {
            Log::error("Change Balance error".'单号'. $order->out_trade_no);
            return false;
        }

        /**************写入商户资金结束*****************/

        /**************写入渠道资金**********************/
        //读取配置 判断渠道资金是否开启

        $is_open_channel_fund = \app\common\model\Config::where(['name'=>'is_open_channel_fund'])->find()->toArray();
        if($is_open_channel_fund){
            if($is_open_channel_fund['value'] == 1){
                //计算渠道金额
                $channel_amount =bcmul($order->amount, $account['rate'], 3);
                if($channel_amount >0 ) {
                    $res = $this->logicPayChannelChange->creatPayChannelChange($account->cnl_id, $channel_amount, '单号' . $order->out_trade_no . '支付成功，金额转入', false);
                    if (!$res) {
                        Log::error("Change PayChannelChange error" . '单号' . $order->out_trade_no);
                        return false;
                    }
                }
            }
        }


        /**************写入渠道资金结束****************/


        //平台收入
        $platform_in = bcsub($income, bcadd($user_in,$agent_in,3),3);

        //3.数据存储
        $res = $this->modelOrders->changeOrderStatusValue([
            'income'    => $income,
            'user_in'    => $user_in,
            'agent_in'    => $agent_in,
            'platform_in'    => $platform_in,
            'status'  => $success ? OrderStatusEnum::PAID : OrderStatusEnum::UNPAID
        ], [
            'id'=>$order->id
        ]);
        if(empty($res))
        {
            Log::error("update Order error".'单号'. $order->out_trade_no);
            return false;
        }
        return true;
    }




    /*
     *回调第三方通知ip白名单校验
     * @param $channel
     */
    public function  checkNotifyIp($channel)
    {
       $channelInfo   = $this->logicpay->getChannelInfo(['action'=>$channel]);
       $notifyIps  = explode(',',trim($channelInfo['notify_ips']));
       if($notifyIps && !in_array($_SERVER['REMOTE_ADDR'],$notifyIps))
       {
           Log::notice("通知结果:第三方{$channel}通知IP不在渠道白名单内");
           return ['code'=>CodeEnum::ERROR,'msg' =>'验证签名失败了'];
       }

       //渠道是否允许回调
       if($channelInfo['ia_allow_notify']==0)
       {
           Log::notice("通知结果:第三方{$channel}通知,平台拒绝回调通知");
           return ['code'=>CodeEnum::ERROR,'msg' =>'平台拒绝回调通知'];
       }
       return ['code'=>CodeEnum::SUCCESS,'msg'=>'success'];
    }
}

