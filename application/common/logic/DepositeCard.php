<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/27
 * Time: 17:47
 */

namespace app\common\logic;


use app\common\library\enum\CodeEnum;
use think\Exception;
use think\Log;

class DepositeCard extends BaseLogic
{

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    /**
     *
     *  获取列表
     */
    public function getCardList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelDepositeCard->alias('a');
        $join = [
            ['banker b', 'b.id = a.bank_id']
        ];
        $this->modelDepositeCard->join = $join;
        $this->modelDepositeCard->limit = !$paginate;
        return $this->modelDepositeCard->getList($where, $field, $order, $paginate);
    }

    public function getCardListV2($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelDepositeCard->alias('a');
        $join = [
            ['banker b', 'b.id = a.bank_id'],
            ['user c', 'a.uid = c.uid']
        ];
        $this->modelDepositeCard->join = $join;
        $this->modelDepositeCard->limit = !$paginate;
        return $this->modelDepositeCard->getList($where, $field, $order, $paginate);
    }

    /**
     * @param array $where
     * @return mixed
     * 获取订单总数
     */
    public function getCardCount($where = [])
    {
        return $this->modelDepositeCard->getCount($where);
    }

    /**
     * 修改
     */
    public function saveCard($data, $type)
    {
        $validate = $this->validateDepositeCardValidate->scene($type)->check($data);
        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateDepositeCardValidate->getError()];
        }
        $data['update_time'] = time();
        if ($type == 'add') {
            $data['create_time'] = time();
        }
        $result = $this->modelDepositeCard->setInfo($data);
        return $result ? ['code' => CodeEnum::SUCCESS, 'msg' => '保存成功'] : ['code' => CodeEnum::ERROR, 'msg' => $this->modelDepositeCard->getError()];
    }

    /**
     * 获取单条充值银行卡数据
     */
    public function getCard($id)
    {
        return $this->modelDepositeCard->getInfo(['id' => $id]);
    }

    /**
     * @param array $where
     * @return array
     * 删除
     */
    public function delCard($where = [])
    {
        $result = $this->modelDepositeCard->deleteInfo($where, true);
        return $result ? ['code' => CodeEnum::SUCCESS, 'msg' => '删除成功'] : ['code' => CodeEnum::ERROR, 'msg' => $this->modelDepositeCard->getError()];
    }


    /**
     * @param $bank_id  银行卡ID
     * @param int $chang_type 1：充值 2：代付扣减
     * @param $type '1：增加 2：减少'
     * @param $remarks  描述
     */
    public function addLogs($bank_id, $amount, $chang_type = 1, $type = 1, $remarks)
    {
        $bank = $this->modelDepositeCard->where(['id' => $bank_id])->find();
        if ($bank && $bank['status'] == 1) {
            $data['bank_id'] = $bank_id;
            $data['chang_type'] = $chang_type;
            $data['type'] = $type;
            $data['amount'] = $amount;
            $data['preinc'] = $bank['balance'];
            $data['suffixred'] = $type == 2 ? bcsub($bank['balance'], $amount, 3)
                : bcadd($data['preinc'], $amount, 3);
            $data['remarks'] = $remarks; //改变备注
            //添加日志记录
            $this->modelDepositeCardLog->setInfo($data);
            //增减金额
            $this->modelDepositeCard->setIncOrDec(['id' => $bank_id], $type == 2 ? 'setDec' : 'setInc', 'balance', $amount);
        } else {
            throw new Exception('当前银行卡状态异常');
        }
    }


}