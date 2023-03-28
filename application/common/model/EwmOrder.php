<?php


namespace app\common\model;


/**
 * 二维码订单
 * Class EwmOrder
 * @package app\common\model
 */
class EwmOrder extends BaseModel
{

    const WAITEPAY = 0;
    const PAYED = 1;
    const CLOSED = 2;
    // 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
    protected $autoWriteTimestamp = false;
    // 创建时间字段
    protected $createTime = 'create_time';
    // 更新时间字段
    protected $updateTime = 'update_time';




    const EXPREIDTIME = 30 * 60;

    /**
     * 增加订单
     * @param int $userid
     * @param float $money
     * @param string $orderNum
     * @param int $codeId
     * @param float $payMoney
     * @return int|string
     */
    function addGemaPayOrder($userid, $money, $orderNum, $codeId, $payMoney, $qrCode, $gemaUsername, $type, $tradeNo, $merchantOrderNo, $admin_id, $notify_url, $member_id,$pay_username='')
    {
        $data = [
            'add_time' => time(),
            'status' => self::WAITEPAY,
            'gema_userid' => $userid,
            'order_price' => $money,
            'order_no' => $orderNum,
            'code_id' => $codeId,
            'order_pay_price' => $payMoney,
            'qr_image' => $qrCode,
            'gema_username' => $gemaUsername,
            'code_type' => $type,
            'out_trade_no' => $tradeNo,
            'merchant_order_no' => $merchantOrderNo,
            'admin_id' => $admin_id,
            'notify_url' => $notify_url,
            'member_id' => $member_id,
            'pay_user_name'=>$pay_username
        ];

        return $this->insert($data, false, true);
    }

    /**
     * 设置订单成功
     * @param $orderId
     * @param string $note
     * @param float $fee
     * @return GemapayOrderModel
     */
    public function setOrderSucess($orderId, $note = "", $fee = 0.00)
    {
        $where['id'] = $orderId;
        $data['bonus_fee'] = $fee;
        $data['status'] = self::PAYED;
        $data['pay_time'] = time();
        $data['note'] = $note;
        $data['sure_ip'] = clientIp();
        $data['sure_order_role'] = strtolower(request()->module()) == 'admin'?2:1;
        return $this->where($where)->update($data);
    }


//    public function getAddTimeAttr($value)
//    {
//        return $value ? date('Y-m-d H:i:s', $value) : '---';
//    }
//    public function getPayTimeAttr($value)
//    {
//        return $value ? date('Y-m-d H:i:s', $value) : '未支付';
//    }
//
//    public function getVisiteTimeAttr($value)
//    {
//        return $value ? date('Y-m-d H:i:s', $value) : '未访问';
//    }






}