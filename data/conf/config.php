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


return [

    // 应用调试模式
    'app_debug' => false,
    // 应用Trace
    'app_trace' => false,
    // 域名部署
    'url_domain_deploy' => true,

    // 视图输出字符串内容替嘻嘻嘻换
    'view_replace_str' => [
        '__STATIC__' => '/public/static',
        '__COMMON__' => '/public/static/common',
        '__ADMIN__' => '/public/static/admin',
        '__CLOUD__' => '/public/static/cloudui',
        '__INDEX__' => '/public/static/index',
        '__LAYUI__' => '/public/static/layui',
        '__MS__' => '/public/static/ms'
    ],

    // 自定义跳转页面对应的模板文件
    'dispatch_success_tmpl' => APP_PATH . 'common/view/tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl' => APP_PATH . 'common/view/tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl' => APP_PATH . 'common/view/tpl' . DS . 'exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message' => 'Bug! 这好像是个Bug!',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH,
        // 分部日志
        'apart_level' => ['notice', 'error'],
        // 日志记录级别
        'level' => ['notice', 'error'],
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache' => [
        // 使用复合缓存类型
        'type' => 'complex',
        // 默认使用的缓存
        'default' => [
            // 驱动方式
            'type' => 'redis',
            // 服务器地址
            'host' => '127.0.0.1',
        ],
        // 文件缓存
        'file' => [
            // 驱动方式
            'type' => 'file',
            // 设置不同的缓存保存目录
            'path' => CACHE_PATH,
        ],
        // redis缓存
        'redis' => [
            // 驱动方式
            'type' => 'redis',
            // 服务器地址
            'host' => '127.0.0.1',
        ]

    ],

    // +----------------------------------------------------------------------
    // | 分页配置
    // +----------------------------------------------------------------------
    'paginate' => [
        'type' => 'bootstrap',//bootstrap  //Page\Pagination
        'var_page' => 'page',
        'list_rows' => 15,
    ],
    'paofen_url' => 'http://192.168.153.1/',
    'paofen_pay_url' => 'http://www.bank.com',
    'paofen_super_admin_id' => 1,
    'inner_transfer_secret' => 'g8CZvkqwwFRmKyloOAc2hLAZgZg8Ahcz',//内部应用通讯密钥
    'default_filter' => 'htmlspecialchars,addslashes,strip_tags',
    //新版匹配码商银行卡
    'new_version_forcode'=>true,
    'bank_pay_url'=>'http://www.bank.com/v2/index.php'

];
