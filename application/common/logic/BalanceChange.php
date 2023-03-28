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

namespace app\common\logic;

use think\Db;
use think\Log;

class BalanceChange extends BaseLogic
{

    public function getBalanceChangeInfo($where = [], $field = 'COALESCE(sum(`increase`),0) as total_increase,COALESCE(sum(`reduce`),0) as total_reduce')
    {
        return $this->modelBalanceChange->getInfo($where, $field);
    }


    /**
     * 资金变动记录
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @module Admin
     *
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getBalanceChangeList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelBalanceChange->limit = !$paginate;
        return $this->modelBalanceChange->getList($where, $field, $order, $paginate);
    }

    /**
     * 资金变动记录总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return mixed
     */
    public function getBalanceChangeCount($where = []){
        return $this->modelBalanceChange->getCount($where);
    }

    /**
     * 变动记录
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $uid
     * @param $amount
     * @param string $remarks
     * @param string $field
     * @param bool $setDec
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creatBalanceChange($uid,$amount,$remarks = '未知变动记录',$field = 'enable',$setDec = false,$is_plat_op=0){
        //事务
        $user = (new \app\common\model\Balance())->getUserBalance(['uid' => $uid]);
        if( $field== 'enable' && $setDec && bccomp($amount,$user['enable']) == 1)
        {
            //减少用户资金
            return false;
        }

        if(!is_null($user)){

            $data['uid'] = $uid; //商户UID
            $data['type'] =  $field; //余额类型
            $data['preinc'] =  $user[$data['type']]; //改变前金额
            $data['increase'] = $setDec ?'0.000': $amount; //改变增加金额
            $data['reduce'] = $setDec ? $amount : '0.000'; //改变减少金额

            $data['suffixred'] = $setDec ? bcsub($data['preinc'], $amount, 3)
                : bcadd($data['preinc'] , $amount,3); //改变后金额     这里用PHP  bc高精度计算

            $data['remarks'] = $remarks; //改变备注
            $data['is_flat_op'] = $is_plat_op;
            //数据提交
            try{
                //资金记录
                (new \app\common\model\BalanceChange())->setInfo($data);
                //资金增减
                (new \app\common\model\Balance())->setIncOrDec(['uid'=>$uid],$setDec ? 'setDec' :'setInc', $field, $amount);
                return true;

            }catch (\Exception $e) {
                //记录日志
                Log::error("Creat Balance Change Error:[{$e->getMessage()}]");
                return false;
            }
        }
        return false;
    }
}