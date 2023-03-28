<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/7
 * Time: 21:54
 */

namespace app\common\logic;


use app\common\library\enum\CodeEnum;
use think\Db;
use think\Exception;
use think\Log;

class DepositeOrder extends BaseLogic
{


    public function __construct($data = [])
    {
        parent::__construct($data);
    }


    public function acceptApply($id)
    {
        //1.设置订单状态为成功状态

        //2.商户资金添加余额和账变信息
    }

//    public function rejectApply($id)
//    {
//        Db::startTrans();
//        try{
//            $this->modelArticle->deleteInfo($where);
//
//            Db::commit();
//
//            action_log('驳回', '驳回充值。ID：'. $where['id']);
//
//            return ['code' => CodeEnum::SUCCESS, 'msg' =>'文章删除成功'];
//        }catch (\Exception $ex){
//            Db::rollback();
//            Log::error($ex->getMessage());
//            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
//        }
//    }

    /**
     * 申请充值
     */
    public function addApply($data)
    {
        //数据验证
        $validate = $this->validateDepositeOrderValidate->scene('add')->check($data);
        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateDepositeOrderValidate->getError()];
        }


        $card = $this->modelDepositeCard->getInfo(['id' => $data['card_id']]);
        if (!$card) {
            return ['code' => '0', 'msg' => '收款账户不存在'];
        }
        $data['bank_id'] = $card['bank_id'];
        $data['bank_account_username'] = $card['bank_account_username'];
        $data['bank_account_number'] = $card['bank_account_number'];
        $data['bank_account_address'] = $card['bank_account_address'];
        $data['status'] = '0';
        $data['create_time'] = time();
        $data['trade_no'] = create_order_no();
        $result = $this->modelDepositeOrders->setInfo($data);
        return $result ? ['code' => CodeEnum::SUCCESS, 'msg' => '申请成功'] : ['code' => CodeEnum::ERROR, 'msg' => $this->modelDepositeOrders->getError()];
    }


    /**
     *
     *  获取列表
     */
    public function getOrderList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelDepositeOrders->alias('a');
        $join = [
            ['banker b', 'b.id = a.bank_id'],
            ['user c', 'a.uid = c.uid'],
        ];
        $this->modelDepositeOrders->join = $join;

        $this->modelDepositeOrders->limit = !$paginate;
        return $this->modelDepositeOrders->getList($where, $field, $order, $paginate);
    }


    /**
     * @param array $where
     * @return mixed
     * 获取订单总数
     */
    public function getOrderCount($where = [])
    {
        return $this->modelDepositeOrders->getCount($where);
    }


    /**
     * 审核
     */
    public function successOrder($id)
    {
        return $this->saveOrder($id, true);
    }


    /**
     * 驳回
     */
    public function errorOrder($id)
    {
        return $this->saveOrder($id, false);
    }


    /**
     * 订单状态修改
     */
    public function saveOrder($id, $status)
    {
        if (!$id) {
            return ['code' => '0', 'msg' => '非法操作'];
        }
        $order = $this->modelDepositeOrders->where(['id' => $id, 'status' => '0'])->find();
        if (!$order) {
            return ['code' => '0', 'msg' => '订单不存在'];
        }
        $this->modelDepositeOrders->startTrans();
        try {
            $order->save([
                'status' => $status ? '1' : '2',
                'update_time' => time(),
            ]);
            if ($status) {
                //充值成功 商户余额增加
                $result = $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'], '商户充值成功，余额增加');
                if (!$result) {
                    throw new Exception('商户余额变动失败');
                }
            }
            $order->commit();
        } catch (Exception $e) {
            $this->modelDepositeOrders->rollback();
            return ['code' => '0', 'msg' => $e->getMessage()];
        }


        return ['code' => '1', 'msg' => '审核成功'];
    }


    /**
     * 统计信息
     * @param $where
     */
    public function calute($where)
    {
        //总充值金额
        $amount = $this->modelDepositeOrders->stat($where, 'sum', 'amount');
        return [
            'amount' => $amount
        ];
    }


}