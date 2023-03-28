<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/18
 * Time: 17:53
 */

namespace app\common\logic;


use app\common\model\PayChannel;
use think\Exception;

class PayChannelChange extends BaseLogic
{


    public function getPayChannelChangeInfo($where = [], $field = 'COALESCE(sum(`increase`),0) as total_increase,COALESCE(sum(`reduce`),0) as total_reduce')
    {
        return $this->modelPayChannelChange->getInfo($where, $field);
    }



    public function getPayChannelChangeList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelPayChannelChange->limit = !$paginate;
        return $this->modelPayChannelChange->getList($where, $field, $order, $paginate);
    }


    public function getPayChanneleChangeCount($where = []){
        return $this->modelPayChannelChange->getCount($where);
    }


    public function creatPayChannelChange($channel_id,$amount,$remarks = '未知变动记录',$setDec = false,$is_plat_op=0){


        $PayChannel = new PayChannel();
        $pay_channel = $PayChannel->where(['id'=>$channel_id])->find();

        //如果是减少余额 判断一下
        if($setDec) {
            //判断余额
            if ($amount > $pay_channel['channel_fund']) {
                return false;
            }
        }

        if(!is_null($pay_channel)){
            $data['channel_id'] = $channel_id; //商户UID
            $data['preinc'] =  $pay_channel['channel_fund']; //改变前金额
            $data['increase'] = $setDec ?'0.000': $amount; //改变增加金额
            $data['reduce'] = $setDec ? $amount : '0.000'; //改变减少金额
            $data['suffixred'] = $setDec ? bcsub($data['preinc'], $amount, 3)
                : bcadd($data['preinc'] , $amount,3); //改变后金额     这里用PHP  bc高精度计算
            $data['remarks'] = $remarks; //改变备注
            $data['is_flat_op'] = $is_plat_op;
            //数据提交
            $PayChannel->startTrans();
            try{
                $result = $PayChannel->setIncOrDec(['id'=>$channel_id],$setDec ? 'setDec' :'setInc', 'channel_fund', $amount);
                if(!$result){
                    throw new Exception('余额变动失败');
                }
                //资金记录
                $result = (new \app\common\model\PayChannelChange())->setInfo($data);
                if(!$result){
                    throw new Exception('资金记录失败');
                }
                //资金增减
                $PayChannel->commit();

                return true;

            }catch (\Exception $e) {
                //记录日志
                Log::error("Creat PayChannel Change Error:[{$e->getMessage()}]");
                return false;
            }
        }
        return false;
    }
}