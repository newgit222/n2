<?php


namespace app\ms\validate;


use think\Validate;


/**
 * 二维码验证规则
 * Class EwmPayCode
 * @package app\ms\validate
 */
class EwmPayCode extends Validate
{

    protected $rule = [
        'bank_name' => 'require|max:25',
        'account_name' => 'require|max:5|vlidateError|checkIsChinese',
        'account_number' => 'require|number|max:30',
        'security' => 'require|max:25',
    ];

    protected $message = [
        'account_name.require' => '开户姓名必填',
        'account_name.max' => '开户姓名最大五位',
        'account_number.require' => '银行卡必填',
        'account_number.number' => '银行卡卡号必须是整数',
        'bank_name.require' => '开户行必填',
        'security.require' => '安全码必填',
    ];

    protected function checkIsChinese($value)
    {
        return checkIsChinese($value) ? true : '开户姓名必须为中文';
    }


    protected function vlidateError($value)
    {

        return vlidateError($value) ? true : '开户姓名必须为中文';
    }



//    protected $scene = [
//        'add' => ['name', 'email'],
//        'edit' => ['email'],
//    ];
}


