<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/27
 * Time: 22:47
 */

namespace app\common\validate;


class DepositeCardValidate extends BaseValidate
{
// 验证规则
    protected $rule = [
        'bank_id'      => 'require',
        'status'   => 'require',
        'bank_account_username'   => 'require',
        'bank_account_number'   => 'require',
        'bank_account_address'   => 'require',
    ];

    // 验证提示
    protected $message = [
        'bank_id.require'      => '银行不能为空',
        'status.require'   => '请选择状态',
        'bank_account_username.require'   => '银行账户不能为空',
        'bank_account_number.require'   => '银行卡号不能为空',
        'bank_account_address.require'   => '支行地址不能为空',
    ];

    protected $scene = [
        'edit' => ['bank_id','status','bank_account_username','bank_account_number','bank_account_address'],
        'add' => ['status','bank_account_username','bank_account_number','bank_account_address'],
    ];
}