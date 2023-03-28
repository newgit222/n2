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

class Banker extends BaseLogic
{

    /**
     * 获取所有支持银行
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getBankerList($where = [], $field = true, $order = 'create_time desc',$paginate = 100){
        return $this->modelBanker->getList($where,$field, $order, $paginate);
    }
    /**
     * 所有支持银行总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return mixed
     */
    public function getBankerCount($where = []){
        return $this->modelBanker->getCount($where);
    }



    /**
     * 获取所有支持银行
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getBankerInfo($where = [], $field = true){
        return $this->modelBanker->getInfo($where,$field);
    }

    /**
     * 修改添加银行
     */
    public function saveBankerInfo($data){
         $result =  $this->modelBanker->setInfo($data);
         if(!$result){
             return [  'code' => CodeEnum::ERROR,  'msg' => '保存失败'];
         }
        return [  'code' => CodeEnum::SUCCESS,  'msg' => '保存成功'];
    }

    /**
     * 删除银行卡
     */
    public function delBankerInfo($where){
        $result = $this->modelBanker->deleteInfo($where,true);
        if(!$result){
            return [  'code' => CodeEnum::ERROR,  'msg' => '保存失败'];
        }
        return [  'code' => CodeEnum::SUCCESS,  'msg' => '保存成功'];
    }

}