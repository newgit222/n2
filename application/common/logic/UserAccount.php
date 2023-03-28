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


use app\common\library\enum\CodeEnum;
use think\Db;
use think\Log;

class UserAccount extends BaseLogic
{
    /**
     * 获取商户账号列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param string|bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getAccountList($where = [], $field = true, $order = '', $paginate = 20)
    {
        $this->modelUserAccount->limit = !$paginate;

        $this->modelUserAccount->alias('a');

        $join = [
            ['banker b', 'a.bank_id = b.id'],
        ];
        $where['a.status']=['neq',-1];

        $this->modelUserAccount->join = $join;
        return $this->modelUserAccount->getList($where, $field, $order, $paginate);
    }

    /**
     * 商户账号总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return mixed
     */
    public function getAccountCount($where = []){
        return $this->modelUserAccount->getCount($where);
    }

    /**
     * 获取商户结算账户
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getAccountInfo($where = [], $field = true){

        return $this->modelUserAccount->getInfo($where, $field);
    }

    /**
     * 编辑商户账号
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return array
     */
    public function saveUserAccount($data){

        //TODO  验证数据
        $validate = $this->validateAccountValidate->scene($data['scene'])->check($data);
        if (!$validate) {

            return [ 'code' => CodeEnum::ERROR, 'msg' => $this->validateAccountValidate->getError()];
        }
        //TODO 修改数据
        Db::startTrans();
        try{


            $data['status'] = 2;
            //读取商户配置
            $is_verify_bankaccount = \app\common\model\User::where(['uid'=>$data['uid']])->value('is_verify_bankaccount');
            if(!$is_verify_bankaccount){
                $data['status'] = 1;
            }
            $this->modelUserAccount->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, $action . '收款账号。uid:'. $data['uid']);

            Db::commit();

            return [ 'code' => CodeEnum::SUCCESS, 'msg' => $action . '账号成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR, 'msg' => config('app_debug')?$ex->getMessage():'未知错误'];
        }
    }

    /**
     * 删除账户
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     *
     * @return array
     */
    public function delAccount($where = []){
        Db::startTrans();
        try{
            $this->modelUserAccount->deleteInfo($where);
            action_log('删除', '删除账户，ID：'. $where['id']);
            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => '删除账户成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }
}