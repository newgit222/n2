<?php
namespace app\common\logic;


use app\admin\model\ShopOrderModel;
use app\agent\model\ShopAccountModel;
use app\common\library\enum\CodeEnum;
use app\common\model\EwmPayCode;
use app\common\model\GemapayOrderModel;
use app\ms\Logic\SecurityLogic;
use think\Db;

/**
 * 二维码订单逻辑处理
 * Class EwmOrder
 * @package app\common\logic
 */
class EwmOrder extends BaseLogic
{
    /**
     * @param $array
     * @param $keys
     * @param int $sort
     * @return mixed
     * 二维数组排序
     */
    public function arraySort($array, $keys, $sort = SORT_DESC)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }


    /**
     * 获取可以使用的二维码
     * @param $money
     * @param $codeType
     * @param $ip
     * @param $admin_id
     * @return array|bool
     */
    public function getGoodCodeV2($money, $codeType, $ip, $member_id)
    {
        $GemapayCode = new EwmPayCode();
        $gemapayOrderLogic = new EwmOrder();
        //获取可以使用二维码
        $codeInfos = $GemapayCode->getAviableCode($money, $codeType, $member_id);
        if (empty($codeInfos)) {
            return false;
        }
        //$isAllowPoint = true;
        //如果匹配不到整数,去匹配小数点
        /* if (empty($codeInfos)) {
             if ($isAllowPoint) {
                 $payPrices = $gemapayOrderLogic->getAvaibleMoneys($money);
                 foreach ($payPrices as $price) {
                     $codeInfos = $GemapayCode->getAviableCode($price, $codeType, $admin_id);
                     if (!empty($codeInfos)) {

                         $reallPayMoney = $price;
                         break;
                     }
                 }
             } else {
                 $reallPayMoney = $money;
             }
             if (empty($codeInfos)) {
                 return false;
             }
         } else {
             $reallPayMoney = $money;
         }*/
        $reallPayMoney = $money;;
        $userIds = [];
        foreach ($codeInfos as $code) {
            $userIds[] = $code['ms_id'];
        }

        $userIds = array_unique($userIds);
        sort($userIds);
//        echo json_encode($userIds);
        $lastUserId = cache("last_userid");
        if (empty($lastUserId)) {
            $lastUserId = $userIds[0];
        } else {
            $flag = false;
            foreach ($userIds as $key => $userId) {
                if ($userId > $lastUserId) {
                    $flag = true;
                    $lastUserId = $userId;
                    break;
                }
            }
            if ($flag == false) {
                $lastUserId = $userIds[0];
            }
        }
        cache('last_userid', $lastUserId);

        //这里按照正序排序
        $codeInfos = $this->arraySort($codeInfos, 'id', SORT_ASC);
        $codeInfo = [];
        //该用户上次使用的codeid
        $lastUserIdCodeId = cache("last_userid_codeid_" . $lastUserId);
        if ($lastUserIdCodeId) {
            foreach ($codeInfos as $code) {
                if ($code['ms_id'] == $lastUserId && $code['id'] > $lastUserIdCodeId) {
                    $codeInfo = $code;
                    break;
                }
            }
            if (!$codeInfo) {
                foreach ($codeInfos as $code) {
                    if ($code['ms_id'] == $lastUserId) {
                        $codeInfo = $code;
                        break;
                    }
                }
            }
        } else {
            foreach ($codeInfos as $code) {
                if ($code['ms_id'] == $lastUserId) {
                    $codeInfo = $code;
                    break;
                }
            }
        }

        cache("last_userid_codeid_" . $lastUserId, $codeInfo['id']);


        return [$reallPayMoney, $codeInfo, null];
    }

    /**
     * @param $money
     * @param $tradeNo
     * @param $codeType
     * @param $merchantOrderNo
     * @param $admin_id
     * @param $notify_url
     * @param int $memeber_id
     * @return array
     */
    public function createOrder($money, $tradeNo, $codeType, $merchantOrderNo, $admin_id, $notify_url, $member_id = 0, $pay_username = '')
    {
        $GemapayCode = new EwmPayCode();
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $msModel = new \app\common\model\Ms();
        $GemapayCode->startTrans();
        //码商余额是否大于押金浮动金额
        $msInfo = $msModel->where(['userid' => $member_id])->find();
        $msMaxOrderMoney = bcsub($msInfo['money'], $msInfo['deposit_floating_money'], 2);
        if (bccomp($money, $msMaxOrderMoney) == 1) {
            $GemapayCode->rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => "码商接单失败：【订单金额过大,最大订单金额{$msMaxOrderMoney}：单号{$tradeNo}】"];
        }
        $insId = $GemapayOrderModel->addGemaPayOrder(0, $money, $tradeNo, 0, $money, "", "", $codeType, $tradeNo, $merchantOrderNo, $admin_id, $notify_url, $member_id, $pay_username);
        if (empty($insId)) {
            $GemapayCode->rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '更新订单数据失败'];
        }
        $where['order_no'] = $tradeNo;
        $order = $GemapayOrderModel->lock(true)->where($where)->find();

        //大于十分钟订单过期不允许访问
        //if (time() - $order['add_time'] > 600) {
        //    return ['code' => CodeEnum::ERROR, 'msg' => '订单已过期'];
        // }

        $ip = request()->ip();
        list($money, $code, $area) = $this->getGoodCodeV2($order['order_price'], $order['code_type'], $ip, $member_id);
        if ($code == false) {
            $GemapayOrderModel->isUpdate(true, ['id' => $order['id']])->save(['note' => '系统没有可用的支付二维码']);
            $GemapayOrderModel->commit();
            return ['code' => CodeEnum::ERROR, 'msg' => '系统没有可用的支付二维码'];
        }
        $data['order_pay_price'] = $money;
        $data['gema_username'] = $code['user_name'];
        $data['gema_userid'] = $code['ms_id'];
        $data['code_id'] = $code['id'];
        $data['visite_area'] = $area;
        $data['visite_ip'] = $ip;
        $data['visite_time'] = time();
        if (false == $GemapayOrderModel->where(['id' => $order['id']])->update($data)) {
            $GemapayOrderModel->rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '更新失败'];

        }

        //扣除用户余额
        $message = "抢单" . $tradeNo . "成功";


        /*        if (false == accountLog($code['ms_id'], MsMoneyType::ORDER_DEPOSIT, MsMoneyType::OP_SUB, $order['order_price'], $message)) {
                    $GemapayCode->where(true, ['id' => $order['id']])->update(['note' => 'error']);
                    $GemapayCode->commit();
                    return ['code' => CodeEnum::ERROR, 'msg' => 'error'];
                }*/

        $GemapayCode->incTodayOrder($code['id']);

        $GemapayCode->commit();
        return ['code' => CodeEnum::SUCCESS, 'data' => [
            'code' => $code,
            'money' => $money
        ]];

    }


    /**
     * 获取可用金额列表
     * @param $money
     * @return array
     */
    public function getAvaibleMoneys($money)
    {
        $data = [];
        $limit = EwmPayCode::MONEY_LIMIT_NUM;
        $moneyStart = $money - $limit * 0.05 / 5;
        for ($i = 0; $i <= $limit; $i++) {
            if ($moneyStart + $i * 0.01 != $money) {
                $data[] = sprintf("%.2f", floatval($moneyStart + $i * 0.01));
            }
        }

        return $data;
    }


    /**
     * 用户完成订单
     * @param $orderId
     * @param $note
     * @param $userid
     */
    public function setOrderSucessByUser($orderId, $userid, $security, $next_user_id = 0, $coerce = false)
    {
        //判断订单状态
        $GemaPayOrder = new \app\common\model\EwmOrder();
        $SecurityLogic = new SecurityLogic();

        //判断交易密码
        $result = $SecurityLogic->checkSecurityByUserId($userid, $security);

        //判断用收款ip是否和最近登录的ip是否一致
        if ($result['code'] == CodeEnum::ERROR) {
            return $result;
        }

        $where['id'] = $orderId;
        $where['status'] = $GemaPayOrder::WAITEPAY;
        //判断是否强制补单
        if (1) {
            unset($where['status']);
        }
        $where['gema_userid'] = $userid;
        if ($next_user_id) {
            //  $where['gema_userid'] = $next_user_id;
        }

        Db::startTrans();
        $orderInfo = $GemaPayOrder->where($where)->lock(true)->find();
        if ($orderInfo['status'] == $GemaPayOrder::PAYED) {
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '订单已完成'];
        }
        Db::commit();


        if (empty($orderInfo)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '订单信息有误'];
        }

        if ($orderInfo['gema_userid'] != $userid) {
            return ['code' => CodeEnum::ERROR, 'msg' => '操作非法'];
        }

        //判断用户余额是否足够

        $UserModel = new \app\common\model\Ms();
        $userMoney = $UserModel->where(['userid' => $userid])->value('money');
        if ($userMoney < $orderInfo['order_price']) {
         //   return ['code' => CodeEnum::ERROR, 'msg' => '用户余额不足'];
        }

        return $this->setOrderSucess($orderInfo, "用户手动调单");
    }

    /**
     * @param $orderInfo
     * @param bool $notSend
     * @return array|bool
     */
    protected function orderProfit($orderInfo, $notSend = false)
    {
        $userModel = new \app\common\model\Ms();
        $user = $userModel->find($orderInfo['gema_userid']);
        $money = sprintf('%.2f', $user['bank_rate'] * $orderInfo['order_pay_price'] / 100);
        if ((bccomp($money, 0.00, 2) == 1)) {
            //$tip_message = "订单【{$orderInfo['order_no']}】中获得佣金{$money}元";
            $tip_message = $orderInfo['order_no'];
            if (!accountLog($orderInfo['gema_userid'], MsMoneyType::USER_BONUS, MsMoneyType::OP_ADD, $money, $tip_message)) {
                return false;
            }
        }

        return $money;
    }


    /**
     * 记录失败次数
     * @param $code_id
     * @param $type
     * @param $admin_id
     */
    public function recordFailedNum($code_id, $type, $admin_id)
    {
        $GemapayCodeModel = new EwmPayCode();
        $code = $GemapayCodeModel->where(['id' => $code_id])->find();
        if ($code) {
            $code->failed_order_num++;
            $code->updated_at = time();
            $code->save();
            $code->commit();
        }
    }


    /**
     * 设置订单为成功状态
     * @param $orderId
     * @param string $note
     * @return array
     */
    public function setOrderSucess($orderInfo, $note)
    {
        $GemapayOrderModel = new \app\common\model\EwmOrder();

        Db::startTrans();
        $orderInfo = $GemapayOrderModel->lock(true)->find($orderInfo['id']);

        //如果订单为关闭状态则手动强制完成需要扣除
        /* if ($orderInfo['status'] == $GemapayOrderModel::CLOSED) {
             $message = "后台强制完成订单" . $orderInfo['out_trade_no'] . ",扣除订单金额";
             $res = accountLog($orderInfo['gema_userid'], MsMoneyType::ORDER_FORCE_FINISH,
                 MsMoneyType::OP_SUB, $orderInfo["order_price"], $message);
             if ($res == false) {
                 Db::rollback();
                 return ['code' => CodeEnum::ERROR, 'msg' => '!更新数据失败'];
             }
         }*/

        $message = "订单" . $orderInfo['out_trade_no'] . "完成";
        //$message = $orderInfo['out_trade_no'];
        if (false == accountLog($orderInfo['gema_userid'], MsMoneyType::ORDER_DEPOSIT, MsMoneyType::OP_SUB, $orderInfo['order_price'], $message)) {
            $GemapayCode->where(true, ['id' => $order['id']])->update(['note' => 'error']);
            $GemapayCode->commit();
            return ['code' => CodeEnum::ERROR, 'msg' => 'error'];
        }
        //给码商返佣金
        $bonus = $this->orderProfit($orderInfo);
        if ($bonus === false) {
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '!发放佣金失败'];
        }

        $res = $GemapayOrderModel->setOrderSucess($orderInfo['id'], $note, $bonus);

        if ($res == false) {
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '!更新数据失败!'];
        }

        $postData['out_trade_no'] = $orderInfo['order_no'];
        $ret = httpRequest($orderInfo['notify_url'], 'post', $postData);
        \think\Log::info("码商确认收款通知支付系统单号：" . $orderInfo['order_no'] . "返回" . $ret);
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $GemapayOrderModel->where(['id' => $orderInfo['id']])->update(['notify_result' => $ret]);
        if ($ret == false) {
//            Db::rollback();
            //          return ['code' => CodeEnum::ERROR, 'msg' => '网络错误,请稍后再试'];
        }

        Db::commit();
        return ['code' => CodeEnum::SUCCESS, 'msg' => '数据更新成功'];
    }


    /**
     * 获取订单总金额
     */
    public function getTotalPrice($where)
    {
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        //总订单金额 总订单数量
        $total_order = $GemapayOrderModel->where($where)
            ->field('cast(sum(order_price) AS decimal(15,2)) as  total_price, count(id) as total_number ')
            ->find()->toArray();
        //成功的订单
        $success_order = $GemapayOrderModel->where($where)->where(['status' => $GemapayOrderModel::PAYED])
            ->field('cast(sum(order_price) AS decimal(15,2)) as  success_price, count(id) as success_number ')
            ->find()->toArray();

        $result = [
            'total_price' => $total_order['total_price'] ? $total_order['total_price'] : 0,
            'total_number' => $total_order['total_number'] ? $total_order['total_number'] : 0,
            'success_price' => $success_order['success_price'] ? $success_order['success_price'] : 0,
            'success_number' => $success_order['success_number'] ? $success_order['success_number'] : 0,
        ];
        return $result;
    }


    /**
     *
     * 获取订单列表
     *
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     * @author 勇敢的小笨羊
     */
    public function getOrderList($where = [], $field = true, $order = 'add_time desc', $paginate = 1)
    {
        $this->modelEwmOrder->alias('a');
        $this->modelEwmOrder->limit = !$paginate;
        $this->modelEwmOrder->append = ['add_time', 'visite_time', 'pay_time'];
        $join = [
            ['ewm_pay_code b', 'a.code_id = b.id', 'left'],
            ['ms c', 'a.gema_userid = c.userid', 'left'],
            ['ewm_block_ip eo', 'eo.block_visite_ip = a.visite_ip', 'left'],
        ];
        $this->modelEwmOrder->join = $join;
        return $this->modelEwmOrder->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取单总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrdersCount($where = [])
    {
        return $this->modelEwmOrder->alias('a')
            ->where($where)
            ->join('ewm_pay_code b', 'a.code_id = b.id', 'left')
            ->join('ms c', 'a.gema_userid = c.userid', 'left')
            ->count();

    }


    /**
     * 管理员完成订单+补单
     * @param $orderId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setOrderSucessByAdmin($orderId, $coerce = 0)
    {
        //判断订单状态
        $GemaPayOrder = new \app\common\model\EwmOrder();

        $where['id'] = $orderId;
        $where['status'] = $GemaPayOrder::WAITEPAY;
        //判断是否强制补单
        if (1) {
            unset($where['status']);
        }

        Db::startTrans();
        $orderInfo = $GemaPayOrder->where($where)->lock(true)->find();
        if ($orderInfo['status'] == $GemaPayOrder::PAYED) {
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '订单已完成'];
        }
        Db::commit();



        if (empty($orderInfo)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '订单信息有误'];
        }

        //判断用户余额是否足够
        return $this->setOrderSucess($orderInfo, "用户手动调单");
    }


    /**
     * 取消订单
     * @param $order
     */
    public function cancleOrder($order)
    {
        Db::startTrans();
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $where["order_no"] = $order;
        $order = $GemapayOrderModel->where($where)->lock(true)->find();

        if (empty($order) || $order['status'] != $GemapayOrderModel::WAITEPAY) {
            Db::rollback();
            return false;
        }
        //取消订单
        $statusRet = $GemapayOrderModel->where(['id' => $order['id']])->setField('status', 2);

        if ($statusRet != false) {
            //如果为二维码订单 记录失败次数
            if (in_array($order['code_type'], ["1", "2", "3","4"])) {
                $this->recordFailedNum($order['code_id'], false, $order['admin_id']);
            }

            //记录日志
            /*   $message = "关闭订单：" . $order['order_no'];
               if ($order['gema_userid']) {
                   //如果没有配置或者配置为1 的话 抢单扣除余额 那关闭订单需要返回余额
                   $ret = accountLog($order['gema_userid'], MsMoneyType::ORDER_DEPOSIT_BACK,
                       MsMoneyType::OP_ADD, $order['order_price'], $message);
                   if ($ret != false) {
                       Db::commit();
                       return true;
                   }
               }*/
        }
        Db::rollback();
        return false;
    }


    /**
     * 10分钟关闭码商二维码订单
     */
    public function closeOrder()
    {

        $indate = 10;
        $where = [];
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $where['code_type'] = 3;
        $where['add_time'] = ['lt', time() - (60 * $indate)];
        $where['status'] = $GemapayOrderModel::WAITEPAY;
        $orderList = $GemapayOrderModel->where($where)->select();
        if ($orderList) {
            foreach ($orderList as $k => $v) {
                $res = $this->cancleOrder($v['order_no']);
            }
        }
echo 3;
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $where['code_type'] = 4;
        $where['add_time'] = ['lt', time() - (60 * 5)];
        $where['status'] = $GemapayOrderModel::WAITEPAY;
        $orderList = $GemapayOrderModel->where($where)->select();
        if ($orderList) {
            foreach ($orderList as $k => $v) {
                $res = $this->cancleOrder($v['order_no']);
            }
        }
    }
}
