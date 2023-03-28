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


namespace app\common\model;

use app\common\library\exception\OrderException;
use think\Db;
use think\Log;

class DaifuOrders extends BaseModel
{


    /**
     * 订单状态检查
     *
     * @param $orderNo
     * @return Orders|null
     * @throws OrderException
     * @throws \think\exception\DbException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function checkOrderValid($orderNo)
    {
        $order = self::get(['trade_no' => $orderNo]);
        if (!$order) {
            throw new OrderException([
                'msg' => 'Order Do Not Exist',
                'errorCode' => 200002
            ]);
        }

        if ($order['status'] === 2) {
            throw new OrderException([
                'msg' => 'Order Error',
                'errorCode' => 200003,
                'code' => 400
            ]);
        }
        return $order;
    }

    /**
     * 获取平台订单
     *
     * @param string $tradeOrderNo 平台订单号
     * @return Orders|bool|null
     * @throws \think\exception\DbException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getTradeOrder($tradeOrderNo)
    {
        $order = self::get(['trade_no' => $tradeOrderNo]);
        if ($order) {
            return $order;
        }
        return false;
    }

    /**
     * 获取商户订单
     *
     * @param string $outTradeOrderNo 商户订单号
     * @return Orders|bool|null
     * @throws \think\exception\DbException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOutTradeOrder($outTradeOrderNo)
    {
        $order = self::get(['out_trade_no' => $outTradeOrderNo]);
        if ($order) {
            return $order;
        }
        return false;
    }

    /**
     * 改变订单状态
     *
     * @param array $where
     * @param array $data
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function changeOrderStatusValue($data = [], $where = [])
    {
        self::allowField(true)->save($data, $where);
    }


    /**
     * @param $out_trade_no
     * 验证订单号是否重复
     */
    public function checkOutTradeNo($uid, $out_trade_no)
    {
        return $this->where(['uid' => $uid, 'out_trade_no' => $out_trade_no])->find();
    }

    /**
     * 创建订单
     */
    public function createOrder($data)
    {
        return $this->create($data);
    }


    /**
     *从yhk这个编码所支持的渠道的码商里面随机分配一个码商
     */
    public function getRandMs()
    {
        $payCode = $this->modelPayCode->where('code', 'yhk')->find();
        if ($payCode) {
            $channels = $this->modelPayChannel->where(['id' => ['in', $payCode['cnl_id']]])->select();
            $msIdArr = [];
            foreach ($channels as $channel) {
                if ($channel['remarks']) {
                    $msArr = explode(',', $channel['remarks']);
                    foreach ($msArr as $ms_id) {
                        if (is_numeric($ms_id) && !in_array($ms_id, $msIdArr)) {
                            $msIdArr[] = $ms_id;
                        }
                    }
                }
            }
            //验证码商是否存在
            $normalMs = $this->modelMs->where(['userid' => ['in', $msIdArr]])->column('userid');
            $result = array_intersect($msIdArr, $normalMs);
            $msIdArr = array_values($result);
            if (count($msIdArr)) {
                return $msIdArr[array_rand($msIdArr)];
            }
            return null;
        }
    }

    /**
     * 获取支付yhk通道得码商
     */
    public function getYhkMs()
    {
        $payCode = $this->modelPayCode->where(['code' => ['in', ['yhk', 'guma_yhk']]])->column('cnl_id');
        $cnl_ids = [];
        foreach ($payCode as $cnl_id){
            $cnl_ids = array_merge($cnl_ids, explode(',', $cnl_id ));
        }
        $result = [];
        if ($payCode) {
            $channels = $this->modelPayChannel->where(['id' => ['in', $cnl_ids]])->select();
            $msIdArr = [];
            foreach ($channels as $channel) {
                if ($channel['remarks']) {
                    $msArr = explode(',', $channel['remarks']);
                    foreach ($msArr as $ms_id) {
                        if (is_numeric($ms_id) && !in_array($ms_id, $msIdArr)) {
                            $msIdArr[] = $ms_id;
                        }
                    }
                }
            }
            $msIdArr[] = 21;
            $result = $this->modelMs->where(['userid' => ['in', $msIdArr]])->order('userid desc')->select();
        }
        return $result;
    }


}
