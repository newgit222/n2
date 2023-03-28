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


use app\api\service\ApiPayment;
use app\api\service\withdraw\Paofen;
use app\common\library\enum\CodeEnum;
use app\common\library\exception\OrderException;
use app\common\model\PayChannel;
use think\Db;
use think\Exception;
use think\Log;

class BalanceCash extends BaseLogic
{

    /**
     * 获取订单信息
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool|string $field
     *
     * @return mixed
     */
    public function getOrderCashInfo($where = [], $field = 'a.*,u.account,u.account_name,b.name as method'){
        $this->modelBalanceCash->alias('a');

        $join = [
            ['user_account u', 'a.account = u.id','LEFT'],
            ['banker b', 'u.bank_id = b.id','LEFT']
        ];

        $this->modelBalanceCash->join = $join;
        $result = $this->modelBalanceCash->getInfo($where, $field);
        if($result) {
            if (!$result['account']) {
                $result['account_name'] = $result['bank_realname'];
                $result['method'] = $result['bank_name'];
                $result['account'] = $result['bank_number'];
            }
        }
        return $result;
    }
    
   public function getOrderInfo($where = [], $field = '*'){
      //  $this->modelBalanceCash->alias('a');


        return  $this->modelBalanceCash->getInfo($where, $field);
    }




     /**
     * 获取订单信息
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool|string $field
     *
     * @return mixed
     */
    public function getOrderCashInfoV2($where = [], $field = 'a.*,u.account,u.account_name,b.name as method,b.bank_code'){
        $this->modelBalanceCash->alias('a');

        $join = [
            ['user_account u', 'a.account = u.id','LEFT'],
            ['banker b', 'u.bank_id = b.id','LEFT']
        ];

        $this->modelBalanceCash->join = $join;
        $result = $this->modelBalanceCash->getInfo($where, $field);
        return $result;
    }

    public function handle($where,$cnl_id,$audit_remarks)
   {
    $update = ['status'=>3];
   $order = $this->getOrderCashInfoV2($where);
   if ($order->status != 1){
       return ['code'=>'0','msg'=>'订单已被处理'];
   }
   $result = $this->modelBalanceCash->where(['id' => $order['id']])->update($update);
return ['code'=>'1','msg'=>'请求成功'];

}
    public function transpond_channel($where,$cnl_id,$audit_remarks){
        if(!$cnl_id){
            return ['code' =>  CodeEnum::ERROR, 'msg'  => '请选择渠道'];
        }
        //订单
        $order = $this->getOrderCashInfoV2($where);
        if(!$order){
            return ['code' =>  CodeEnum::ERROR, 'msg'  => '订单不存在'];
        }
        //开启事务
        $this->modelBalanceCash->startTrans();
        try{
            //推送渠道状态用3
            $update = ['status'=>3];
            if($audit_remarks){
                $update['audit_remarks'] = $audit_remarks;
            }
            $update['update_time'] = time();
            $result = $this->modelBalanceCash->where(['id' => $order['id']])->update($update);
            if(!$result){
                throw new Exception('保存失败');
            }

            $result = $this->call_channel($order,$cnl_id);
            if($result['code'] != '1' ){
                throw new Exception($result['msg']);
            }
            $this->modelBalanceCash->commit();
            return ['code'=>'1','msg'=>'请求成功'];
        }catch (Exception $e){
            $this->modelBalanceCash->rollback();
            return ['code' =>  CodeEnum::ERROR, 'msg'  => $e->getMessage()];
        }
    }

    /**
     * 调用渠道
     */
    public function call_channel($order,$cnl_id){
        $channel = $this->logicPay->getChannelInfo(['id' =>$cnl_id]);
        //判断渠道是否支持银行
        $bank_code = ApiPayment::$channel['action']()->getBankCode($order['bank_code']);
        if(!$bank_code){
            return ['code'=>'0','msg'=>'渠道不支持银行'];
        }
        return  ApiPayment::$channel['action']()->daifuPay($order);
    }

    /**
     * 订单成功
     */
    public function orderSuccess($data){
        if(!isset($data['out_trade_no'])  ){
            return ['code'=>'0','msg'=>'参数不完整'];
        }

        $BalanceCash = new \app\common\model\BalanceCash();

        $order = $BalanceCash->where(['cash_no'=>$data['out_trade_no'],'status'=>'3'])->find();
        if(!$order){
            return ['code'=>'0','msg'=>'订单不存在'];
        }
        $order->save([
            'status'  =>  '2',
            'update_time'   =>time()
        ]);
//        $order->commit();

        return ['code'=>'1','msg'=>'处理成功'];
    }




    /**
     * 获取打款列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getOrderCashList($where = [], $field = 'a.*,u.account,u.account_name,b.name as method', $order = 'a.create_time desc', $paginate = 15)
    {
        $this->modelBalanceCash->alias('a');

        $join = [
            ['user uu', 'a.uid = uu.uid','LEFT'],
            ['user_account u', 'a.account = u.id','LEFT'],
            ['banker b', 'u.bank_id = b.id','LEFT'],
            ['pay_channel pc', 'a.channel_id = pc.id','LEFT']
        ];

        $this->modelBalanceCash->join = $join;

        $list = $this->modelBalanceCash->getList($where, $field, $order, $paginate);
        foreach ($list as $k => $v){
            if(!$v['account']){
                $list[$k]['account_name'] = $v['bank_realname'];
                $list[$k]['method'] = $v['bank_name'];
                $list[$k]['account'] = $v['bank_number'];
            }
        }

        return $list;
    }

    /**
     * 获取打款列表总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @return mixed
     */
    public function getOrderCashCount($where = []){
        return $this->modelBalanceCash->getCount($where);
    }

    /**
     * 新增提现申请记录（本次新增usdt选项）
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     *
     * @return array
     */
    public function saveUserCashApplyV1($data){
        //TODO 数据验证
        $validate = $this->validateBalance->check($data);

        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateBalance->getError()];
        }
        //校检
        $balance_cash_type  =  $this->logicConfig->getConfigInfo(['name'=>'balance_cash_type'],'value')['value'];
        if($balance_cash_type == 1 ){
            if(!$data['account']){
                return ['code' => CodeEnum::ERROR, 'msg' => '请选择收款账号'];
            }
        }else{
            if(!$data['bank_name'] || !$data['bank_number'] || !$data['bank_realname'] ){
                return ['code' => CodeEnum::ERROR, 'msg' => '请填写收款账号'];
            }
        }
        //读取配置
        $max_withdraw_limit  =  $this->logicConfig->getConfigInfo(['name'=>'max_withdraw_limit'],'value');
        $min_withdraw_limit  =  $this->logicConfig->getConfigInfo(['name'=>'min_withdraw_limit'],'value');
        $min_withdraw_limit = $min_withdraw_limit['value'];
        $max_withdraw_limit = $max_withdraw_limit['value'];
        if($data['amount'] < $min_withdraw_limit ){
            return ['code' => CodeEnum::ERROR, 'msg' => '最小提现金额为'.$min_withdraw_limit.'你的体现金额为:'.$data['amount']];
        }
        if($data['amount'] > $max_withdraw_limit ){
            return ['code' => CodeEnum::ERROR, 'msg' =>  '最大提现金额为:'.$max_withdraw_limit];
        }

        //提现手续费不通的方式不通
//        $commission =config('custom.fee');
        $withdraw_fee  =  $this->logicConfig->getConfigInfo(['name'=>'withdraw_fee'],'value');
        $commission =  $withdraw_fee['value'];

        //提现超额判断
        $balance = $this->modelBalance->getColumn(['uid'=>$data['uid']],'enable')[0];
        if($commission+$data['amount']>$balance){
            return ['code' => CodeEnum::ERROR, 'msg' => '提现超额,系统手续费抵扣不足'];
        }

//        dd($balance);
        //TODO 添加数据
        Db::startTrans();
        try{
            $data['cash_no'] = create_order_no();
            $data['commission'] = $commission;
            //提现
            $this->modelBalanceCash->setInfo($data);
            //资金变动 - 资金记录
           $this->logicBalanceChange->creatBalanceChange($data['uid'],$data['amount'],'提现扣减可用金额', 'enable', true);
           //手续费账变
            if(bccomp($commission,0.00,2)==1){
                $this->logicBalanceChange->creatBalanceChange($data['uid'],$commission,'提现手续费扣减可用金额', 'enable', true);
            }

            Db::commit();

            action_log('新增', '个人提交提现申请'. $data['remarks']);

            return ['code' => CodeEnum::SUCCESS, 'msg' => '新增提现申请成功'];
        }catch (\Exception $ex){
            Log::error("新增提现申请出现错误 : " . $ex->getMessage());
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage()
                : '新增提现申请出现错误' ];
        }
    }

    public function saveUserCashApply($data)
    {
        //TODO 数据验证
        $validate = $this->validateBalance->check($data);

        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateBalance->getError()];
        }

        $type = $data['type'];
        if ($type == 1 && empty($data['withdraw_usdt_address'])) {
            return ['code' => CodeEnum::ERROR, 'msg' => 'USDT提款请输入提款的地址'];
        }

        if ($type == 0) {
            //校检
            $balance_cash_type = $this->logicConfig->getConfigInfo(['name' => 'balance_cash_type'], 'value')['value'];
            if ($balance_cash_type == 1) {
                if (!$data['account']) {
                    return ['code' => CodeEnum::ERROR, 'msg' => '请选择收款账号'];
                }
            } else {
                if (!$data['bank_name'] || !$data['bank_number'] || !$data['bank_realname']) {
                    return ['code' => CodeEnum::ERROR, 'msg' => '请填写收款账号'];
                }
            }
        }

        //读取配置
        $max_withdraw_limit = $this->logicConfig->getConfigInfo(['name' => 'max_withdraw_limit'], 'value');
        $min_withdraw_limit = $this->logicConfig->getConfigInfo(['name' => 'min_withdraw_limit'], 'value');
        $min_withdraw_limit = $min_withdraw_limit['value'];
        $max_withdraw_limit = $max_withdraw_limit['value'];
        if ($data['amount'] < $min_withdraw_limit) {
            return ['code' => CodeEnum::ERROR, 'msg' => '最小提现金额为' . $min_withdraw_limit . '你的体现金额为:' . $data['amount']];
        }
        if ($data['amount'] > $max_withdraw_limit) {
            return ['code' => CodeEnum::ERROR, 'msg' => '最大提现金额为:' . $max_withdraw_limit];
        }

        //提现手续费
//        $commission =config('custom.fee');
        if ($data['type'] == 0)
        {
            $withdraw_fee  =  $this->logicConfig->getConfigInfo(['name'=>'withdraw_fee'],'value');
            $commission =  $withdraw_fee['value'];
        }else
        {
            $withdraw_fee  =  $this->logicConfig->getConfigInfo(['name'=>'withdraw_usdt_rate'],'value');

            $commission =bcdiv(bcmul($withdraw_fee['value'],$data['amount']),100,2);
        }

        //提现超额判断
        $balance = $this->modelBalance->getColumn(['uid'=>$data['uid']],'enable')[0];
        if($commission+$data['amount']>$balance){
            return ['code' => CodeEnum::ERROR, 'msg' => '提现超额,系统手续费抵扣不足'];
        }

//        dd($balance);
        //TODO 添加数据
        Db::startTrans();
        try{
            $data['cash_no'] = create_order_no();
            $data['commission'] = $commission;
            unset($data['withdraw_usdt_rate']);

            //提现
            $this->modelBalanceCash->setInfo($data);
            //资金变动 - 资金记录
            $this->logicBalanceChange->creatBalanceChange($data['uid'],$data['amount'],'提现扣减可用金额', 'enable', true);
            //手续费账变
            if(bccomp($commission,0.00,2)==1){
                $this->logicBalanceChange->creatBalanceChange($data['uid'],$commission,'提现手续费扣减可用金额', 'enable', true);
            }

            Db::commit();

            action_log('新增', '个人提交提现申请');

            return ['code' => CodeEnum::SUCCESS, 'msg' => '新增提现申请成功'];
        }catch (\Exception $ex){
            Log::error("新增提现申请出现错误 : " . $ex->getMessage());
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage()
                : '新增提现申请出现错误' ];
        }
    }


    /**
     * 推送打款队列
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     *
     * @return array
     */
    public function pushBalanceCash($where = [],$audit_remarks = '',$channel_id=0){
        //订单
        $order = $this->getOrderCashInfo($where);

        //加入队列
       // $result = $this->logicQueue->pushJobDataToQueue('AutoBalanceCash' , $order , 'AutoBalanceCash');
      // / if ($result){
       //     $returnmsg =
      //  }else{
       //     $returnmsg = [ 'code' =>  CodeEnum::ERROR, 'msg'  => '推送打款队列失败'];
       // }

        $update = ['status'=>2];
        if($audit_remarks){
            $update['audit_remarks'] = $audit_remarks;
        }

//        $is_open_channel_fund = \app\common\model\Config::where(['name'=>'is_open_channel_fund'])->find()->toArray();
//        if($is_open_channel_fund){
//            if($is_open_channel_fund['value'] == '1' ){
//
//                //渠道id 扣除对应的渠道余额
//                if(!$channel_id){
//                    return [ 'code' =>  CodeEnum::ERROR, 'msg'  => '请输入渠道id'];
//                }
//                //判断余额
//                $PayChannel = new PayChannel();
//                $pay_channel = $PayChannel->where(['id'=>$channel_id])->find();
//                if(!$pay_channel){
//                    return [ 'code' =>  CodeEnum::ERROR, 'msg'  => '渠道不存在'];
//                }
//
//                $amount = $order['amount']+$pay_channel['wirhdraw_charge'];
//
//                if($pay_channel['channel_fund'] < $amount){
//                    return [ 'code' =>  CodeEnum::ERROR, 'msg'  => '该渠道余额不足'];
//                }
//                $ret = $this->logicPayChannelChange->creatPayChannelChange($channel_id,$amount,'提现审核成功',true,1);
//                if(!$ret){
//                    return [ 'code' =>  CodeEnum::ERROR, 'msg'  => '渠道余额变动失败'];
//                }
//                $update['channel_id'] = $channel_id;
//            }
//        }

        $is_paid_select_channel = \app\common\model\Config::where(['name'=>'is_paid_select_channel'])->find()->toArray();
        if($is_paid_select_channel){
            if($is_paid_select_channel['value'] == '1' && $channel_id  ){
                $PayChannel = new PayChannel();
                $pay_channel = $PayChannel->where(['id'=>$channel_id])->find();
                if(!$pay_channel){
                    return [ 'code' =>  CodeEnum::ERROR, 'msg'  => '渠道不存在'];
                }
                $update['channel_id'] = $channel_id;
            }
        }



        $this->modelBalanceCash->updateInfo(['id' => $order['id']], $update );
        action_log('推送','推送提现订单打款，单号：' . $order['cash_no']);
        return [ 'code' =>  CodeEnum::SUCCESS, 'msg'  => '推送打款队列成功'];
    }

    /**
     * 确认订单 推送跑分平台
     */
    public function successTranspondBalanceCash($where = [],$audit_remarks){
        //订单
        $order = $this->getOrderCashInfo($where);
        if(!$order){
            return ['code'=>'0','msg'=>'订单不存在'];
        }
        //开启事务
        $this->modelBalanceCash->startTrans();
        try{
            //订单成功
            $update = ['status'=>2];
            if($audit_remarks){
                $update['audit_remarks'] = $audit_remarks;
            }
            $result = $this->modelBalanceCash->where(['id' => $order['id']])->update($update);
            if(!$result){
                throw new Exception('保存失败');
            }
            $Paofen = new Paofen();
            $result = $Paofen->successOrder($order);
            if($result['code'] != '1' ){
                throw new Exception($result['msg']);
            }
            $this->modelBalanceCash->commit();
            return ['code'=>'1','msg'=>'请求成功'];
        }catch (Exception $e){
            $this->modelBalanceCash->rollback();
            return ['code' =>  CodeEnum::ERROR, 'msg'  => $e->getMessage()];
        }
    }

    /**
     * 获取跑分平台管理员列表
     */
    public function getPaofenAdminList(){
        $Paofen = new Paofen();
        return $Paofen->getList();
    }

    /**
     * @param $where
     * @return array
     * 撤回
     */
    public function revocationBalanceCash($where){
        $where['a.status'] = '3';
        $order = $this->getOrderCashInfo($where);
        if(!$order){
            return ['code'=>'0','msg'=>'订单不存在'];
        }

        //发起请求
        //开启事务
        $this->modelBalanceCash->startTrans();
        try{
            //撤回之后可重新转发
            $update = ['status'=>1];
            $result = $this->modelBalanceCash->where(['id' => $order['id']])->update($update);
            if(!$result){
                throw new Exception('保存失败');
            }
            $Paofen = new Paofen();
            $result = $Paofen->revocation($order);
            if($result['code'] != '1' ){
                throw new Exception($result['msg']);
            }
            $this->modelBalanceCash->commit();
            return ['code'=>'1','msg'=>'请求成功'];
        }catch (Exception $e){
            $this->modelBalanceCash->rollback();
            return ['code' =>  CodeEnum::ERROR, 'msg'  => $e->getMessage()];
        }

    }



    /**
     * 推送跑分平台
     */
    public function transpondBalanceCash($where,$admin_id,$audit_remarks = ''){
        //订单
        $order = $this->getOrderCashInfo($where);

        if(!$order){
            return ['code' =>  CodeEnum::ERROR, 'msg'  => '订单不存在'];
        }
        //开启事务
        $this->modelBalanceCash->startTrans();
        try{
            //推送跑分状态用3
            $update = ['status'=>3];
            if($audit_remarks){
                $update['audit_remarks'] = $audit_remarks;
            }
            $result = $this->modelBalanceCash->where(['id' => $order['id']])->update($update);
            if(!$result){
                throw new Exception('保存失败');
            }
            $Paofen = new Paofen();
            $result = $Paofen->orders($order,$admin_id);
            if($result['code'] != '1' ){
                throw new Exception($result['msg']);
            }
            $this->modelBalanceCash->commit();
            return ['code'=>'1','msg'=>'请求成功'];
        }catch (Exception $e){
            $this->modelBalanceCash->rollback();
            return ['code' =>  CodeEnum::ERROR, 'msg'  => $e->getMessage()];
        }

    }

    /**
     * 订单设置为待确认 4
     */
    public function setConfirmed($data){
        if(!isset($data['cash_no']) || !isset($data['voucher']) || !isset($data['voucher_time'])  ){
            return ['code'=>'0','msg'=>'参数不完整'];
        }

        $BalanceCash = new \app\common\model\BalanceCash();

        $order = $BalanceCash->where(['cash_no'=>$data['cash_no'],'status'=>'3'])->find();
        if(!$order){
            return ['code'=>'0','msg'=>'订单不存在'];
        }

        $order->save([
            'status'  =>  '4',
            'voucher'  =>  $data['voucher'],
            'voucher_time'  =>  $data['voucher_time'],
        ]);
        $order->commit();

        return ['code'=>'1','msg'=>'处理成功'];

    }



    /**
     * @return array
     */
    public function getAuditSwitch(){
        return ['code'=>CodeEnum::SUCCESS,'msg'=>'获取成功','data'=>$this->modelConfig->getValue(['name'=>'is_cash_audit_remark'],'value')];
    }

    /**
     * 驳回提现
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     *
     * @return array
     */
    public function rebutBalanceCash($where = [],$audit_remarks = ''){
        //订单
        $order = $this->getOrderCashInfo($where);
        if ($order['status'] == 0) return ['code' =>  CodeEnum::SUCCESS, 'msg'  => '已经操作过了'];
        Db::startTrans();
        try{

            $update = ['status'=>0];
            if($audit_remarks){
                $update['audit_remarks'] = $audit_remarks;
            }

            $this->modelBalanceCash->updateInfo(['id' => $order['id']], $update , CodeEnum::ERROR);
//            $this->modelBalanceCash->setFieldValue(['id' => $order['id']], 'status', CodeEnum::ERROR);

            //资金变动 - 资金记录
            $this->logicBalanceChange->creatBalanceChange($order['uid'],$order['amount'],"提现驳回可用金额", 'enable', false);
            $this->logicBalanceChange->creatBalanceChange($order['uid'],$order['commission'],"提现驳回手续费可用金额", 'enable', false);

            Db::commit();

            action_log('驳回', '个人提交提现申请，单号：' . $order['cash_no']);

            return  ['code' =>  CodeEnum::SUCCESS, 'msg'  => '已经驳回'];
        }catch (\Exception $ex){
            Log::error("新增提现申请出现错误 : " . $ex->getMessage());
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage()
                : '驳回异常' ];
        }
    }

    /**
     * 设置某个字段参数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param string $field
     * @param string $value
     *
     */
    public function setCashValue($where = [], $field = 'cash_no', $value = ''){
        $this->modelBalanceCash->setFieldValue($where, $field, $value);
    }


    /*
     *检测跑分平台管理员提交的处理提现数据
     * @param $data
     */
    public function notifyPadminBalanceCash($data)
    {
        if(empty($data['cash_id']))
        {
           return  ['code' =>  CodeEnum::ERROR, 'msg'  => '参数错误'];
        }
        if(empty($data['cash_file']))
        {
            return  ['code' =>  CodeEnum::ERROR, 'msg'  => '请提交转款凭证'];
        }
        $cash  =$this->modelBalanceCash->where(['id'=>$data['cash_id']])->find();
        if(empty($cash))
        {
            return  ['code' =>  CodeEnum::ERROR, 'msg'  => '当前提现申请不存在'];
        }
        if($cash['status'] != 1)
        {
            return  ['code' =>  CodeEnum::ERROR, 'msg'  => '当前提现申请状态异常'];
        }
        if($data['padmin_id'] != config('paofen_super_admin_id'))
        {

           $blind = $this->modelUserPadmin->where(['uid'=>$cash['uid'],'p_admin_id'=>$data['padmin_id']])->find();
           if(empty($blind))
           {
               return  ['code' =>  CodeEnum::ERROR, 'msg'  => '跑分授权管理员没有与当前商户绑定'];
           }

        }
        return [ 'code' =>  CodeEnum::SUCCESS, 'msg'  => 'success'];
    }
}
