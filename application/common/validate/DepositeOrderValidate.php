<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/27
 * Time: 23:42
 */

namespace app\common\validate;


class DepositeOrderValidate extends BaseValidate
{

    // 验证规则
    protected $rule = [
        'uid'      => 'require',
        'card_id'   =>  'require',
//        'bank_id'   => 'require',
//        'bank_account_username'   => 'require',
//        'bank_account_number'   => 'require',
//        'bank_account_address'   => 'require',
        'recharge_account'   => 'require',
        'amount'   => 'require',
    ];

    // 验证提示
    protected $message = [
        'uid.require'      => '非法操作',
        'card_id.require'   =>  '请选择银行账户',
//        'bank_id.require'      => '请选择银行账户',
//        'bank_account_username.require'      => '请选择银行账户',
//        'bank_account_number.require'      => '请选择银行账户',
//        'bank_account_address.require'      => '请选择银行账户',
        'recharge_account.require'   => '请输入充值账号',
        'amount.require'   => '请输入充值金额',
    ];

    protected $scene = [
        'add' => ['uid','card_id','recharge_account','amount'],
        'edit' => ['uid','card_id','recharge_account','amount'],
    ];
}