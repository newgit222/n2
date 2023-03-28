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


namespace app\common\validate;

class AccountValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'bank_id'      => 'require',
        'account'   => 'require',
        'address'   => 'require',
    ];

    // 验证提示
    protected $message = [
        'bank_id.require'      => '银行不能为空',
        'account.require'   => '账号不能为空',
        'address.require'   => '地址不能为空',
    ];

    protected $scene = [
       'edit' => ['banker','account','address']
    ];
}