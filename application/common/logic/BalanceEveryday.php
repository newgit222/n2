<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 21:40
 */

namespace app\common\logic;


class BalanceEveryday extends BaseLogic
{
    public function getEverydayList($where = [], $field = '', $order = 'a.createtime desc', $paginate = 15)
    {
        $this->modelBalanceEveryday->alias('a');

        $join = [
            ['user u', 'a.uid = u.uid','LEFT'],
        ];

        $this->modelBalanceEveryday->join = $join;


        $list = $this->modelBalanceEveryday->getList($where, $field, $order, $paginate);
        return $list;
    }


    public function getEverydayCount($where = []){
        return $this->modelBalanceEveryday->getCount($where);
    }
}