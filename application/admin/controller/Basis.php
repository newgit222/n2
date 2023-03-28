<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/6/25
 * Time: 17:16
 */

namespace app\admin\controller;


use app\common\controller\Common;
use think\Request;

class Basis extends Common
{
    public  function __construct(Request $request)
    {


        //读取配置 前台域名白名单
//        $admin_domain_white = '';
$admin_domain_white_list = '';
        //读取配置 前台域名白名单
//        $admin_domain_white_list = \app\common\model\Config::where(['name'=>'admin_domain_white_list'])->find()->toArray();
        if($admin_domain_white_list){
            if($admin_domain_white_list['value']){
//                var_dump($_SERVER['HTTP_HOST']);die();  
                if($admin_domain_white_list['value'] != $_SERVER['HTTP_HOST'] ){
                    header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
                    die();
                }
            }
        }


        //读取配置
//        $admin_view_path = \app\common\model\Config::where(['name'=>'admin_view_path'])->find()->toArray();
  //      $admin_view_path = $admin_view_path?$admin_view_path['value']:'view';
$admin_view_path ='view';
        config('template.view_path',APP_PATH.$request->module().DS.$admin_view_path.DS);
        parent::__construct($request);
    }
}
