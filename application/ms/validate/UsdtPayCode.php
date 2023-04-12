<?php


namespace app\ms\validate;


use think\Validate;


/**
 * 二维码验证规则
 * Class EwmPayCode
 * @package app\ms\validate
 */
class UsdtPayCode extends Validate
{

    protected $rule = [
        'account_name' => 'require|chsAlphaNum',
        'account_number' => 'require|alphaNum|max:40',
        'security' => 'require|max:25',
    ];

    protected $message = [
        'account_name.require' => '钱包名称必填',
        'account_name.chsAlphaNum' => '钱包名称只能是汉字、字母和数字',
        'account_number.require' => '钱包地址必填',
        'account_number.number' => '钱包地址必须是数字和字母',
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
//        'kzk_add' => ['bank_name', 'account_name','account_number','security'],
//        'usdt_add' => ['bank_name', 'account_name','account_number','security'],
////        'edit' => ['email'],
//    ];
}


