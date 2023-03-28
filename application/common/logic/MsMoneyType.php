<?php


namespace app\common\logic;


class MsMoneyType extends BaseLogic
{

    //操作方式,添加
    const OP_ADD = 1;

    //操作方式,减少
    const OP_SUB = 0;

    //充值
    const DEPOSIT = 1;

    //提现
    const WITHDRAW = 2;

    //抢单成功,押金
    const ORDER_DEPOSIT = 3;

    //关闭订单,押金返回
    const ORDER_DEPOSIT_BACK = 4;

    //订单完成,用户分润
    const USER_BONUS = 5;

    //后台强制完成订单
    const ORDER_FORCE_FINISH = 6;

    //站内转账
    const TRANSFER = 7;

    //平台手动调整余额
    const ADJUST = 8;

    //订单完成,代理分润
    const AGENT_BONUS = 9;

    //管理员手动分润
    const MANUAL_BONUS = 10;

    //订单完成，扣除余额
    const ORDER_SUCCESS = 11;

    //代付订单完成
    const DAIFU_ORDER_SUCCESS = 12;

    public static function getMoneyOrderTypes()
    {
        return [
            self::DEPOSIT => "充值添加",
            self::WITHDRAW => "提现扣除",
            self::ORDER_DEPOSIT => "订单完成",
            self::ORDER_DEPOSIT_BACK => "冻结返还",
            self::ORDER_FORCE_FINISH => "后台强制",
            self::TRANSFER => "站内转账",
            self::ADJUST => "平台调整",
            self::AGENT_BONUS => '代理分润',
            self::USER_BONUS => '用户分润',
            self::MANUAL_BONUS => '管理员手动分润',
            self::ORDER_SUCCESS => '订单完成',
            self::DAIFU_ORDER_SUCCESS => '代付订单完成'
        ];
    }








}
