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


namespace app\common\model;

use app\common\logic\MsMoneyType;

class MsSomeBill extends BaseModel
{

    protected $table = 'cm_ms_somebill';
    protected $name = 'ms_somebill';


    public function getJlClassTextAttr($value,$data)
    {
        $types = MsMoneyType::getMoneyOrderTypes();
        return isset($types[$data['jl_class']])?$types[$data['jl_class']]:'未知';
    }


}