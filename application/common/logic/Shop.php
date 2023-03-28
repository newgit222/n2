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

class Shop extends BaseLogic
{
    //详细列表
    public function getShopList($where = [], $field = true, $order = '', $paginate = 20)
    {
        $this->modelShop->limit = !$paginate;

        return $this->modelUserShop->getList($where, $field, $order, $paginate);
    }
	//统计条数
    public function getShopCount($where = []){
        return $this->modelUserShop->getCount($where);
    }

    //获取单条信息
    public function getShopInfo($where = [], $field = true){

        return $this->modelUserShop->getInfo($where, $field);
    }

    /**
     * 编辑商户账号
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return array
     */
    public function saveUserShop($data){

        //TODO  验证数据
       // $validate = $this->validateAccountValidate->scene($data['scene'])->check($data);

       // if (!$validate) {

        //    return [ 'code' => CodeEnum::ERROR, 'msg' => $this->validateAccountValidate->getError()];
       // }
        //TODO 修改数据
        Db::startTrans();
        try{

            $this->modelUserShop->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, $action . '淘宝店铺。uid:'. $data['uid']);

            Db::commit();

            return [ 'code' => CodeEnum::SUCCESS, 'msg' => $action . '店铺成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR, 'msg' => config('app_debug')?$ex->getMessage():'未知错误'];
        }
    }

    /**
     * 删除店铺
     */
    public function delShop($where = []){
        Db::startTrans();
        try{

            $this->modelUserShop->deleteInfo($where);

            action_log('删除', '删除淘宝店铺，ID：'. $where['id']);

            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => '删除店铺成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    public function updateShopLastOnlineTime($shopId)
    {
        $Shop = new \app\common\model\Shop();
        if(empty($shopId))
        {
            return false;
        }
        $where['id'] = $shopId;
        $data["onlinedate"] = request()->time();
        return $Shop->updateInfo($where, $data);
    }

    public function getShoInfo($shopId)
    {
        $Shop = new \app\common\model\Shop();
        $where['id'] = $shopId;
        return $Shop->where($where)->find();
    }

    public function getShoInfoByToken($token)
    {
        $Shop = new \app\common\model\Shop();
        $where['token'] = $token;
        return $Shop->where($where)->find();
    }
}