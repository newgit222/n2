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


use app\common\library\enum\CodeEnum;

class Padmin extends BaseModel
{
     const STATUS_SUCCESS= 1;
     const STATUS_ERROR= 0;



     public function checkPadmin($padmin_id)
     {
           $padmin  = $this->where(['padmin_id'=>$padmin_id])->find();
           if(empty($padmin) || $padmin['status'] ==self:: STATUS_ERROR)
           {
               return ['code'=>CodeEnum::ERROR,'msg'=>'访问受限'];
           }
           return ['code'=>CodeEnum::SUCCESS,'msg'=>'success','data'=>$padmin];
     }



}

