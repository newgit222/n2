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
    'app_debug'              => false,
    // 应用Trace
    'app_trace'              => false,

    // 默认控制器名
    'default_controller'     => 'Pay',
    // 默认操作名
    'default_action'         => 'unifiedorder',

    // 默认输出类型
    'default_return_type'    => 'json',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '\\app\\common\\library\\exception\\ExceptionHandler',
    //真实服务器地址
    'real_host' =>'http://193.168.153.1/',
    //源host
    'orginal_host' =>'http://www.azhidsdsdsaaffu.com/inde.php',
    'proxy_debug' =>0,

];
