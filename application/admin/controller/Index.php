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

namespace app\admin\controller;

use app\common\library\enum\CodeEnum;
use think\helper\Time;

class Index extends BaseAdmin
{

//    public function test(){
//        $admin_domain_white_list = \app\common\model\Config::where(['name'=>'admin_domain_white_list'])->find();
//        if($admin_domain_white_list){
//            if($admin_domain_white_list['value']){
////                var_dump($_SERVER['HTTP_HOST']);die();
//                if($admin_domain_white_list['value'] != $_SERVER['HTTP_HOST'] ){
//                    header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
//                    die();
//                }else{
//                    echo "请求域名:".$_SERVER['HTTP_HOST']."与配置域名:".$admin_domain_white_list['value']."校检成功";
//                }
//            }else{
//                echo "admin_domain_white_list值为空则不校检白名单";
//            }
//        }else{
//            echo "config表admin_domain_white_list配置不存在";
//        }
//    }

    /**
     * 访问首页  -  加载框架
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function index()
    {
        //读取配置 判断是否开启渠道统计悬浮窗
        $is_channel_statistics = 0;
        $config = \app\common\model\Config::where(['name'=>'is_channel_statistics'])->find()->toArray();
        if($config){
            if($config['value'] == '1'){
                $is_channel_statistics = '1';
            }
        }
        $this->assign('is_channel_statistics',$is_channel_statistics);
        return $this->fetch();
    }

    /**
     * 欢迎主页  -  展示数据
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function welcome()
    {
        return $this->fetch('',$this->logicOrders->getWelcomeStat());
    }

    /**
     * 订单月统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrderStat(){

        $res = $this->logicOrders->getOrdersMonthStat();

        $data = [
            'orders' => get_order_month_stat($res,'total_orders'),
            'fees' => get_order_month_stat($res,'total_amount'),
        ];
        $this->result(CodeEnum::SUCCESS,'',$data);
    }

    /**
     * 本月最近订单
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrdersList(){
        $where = [];
        //当月时间
        list($start, $end) = Time::month();

        $where['create_time'] = ['between time', [$start,$end]];

        $data = $this->logicOrders->getOrderList($where,true, 'create_time desc',false);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'data'=>$data
            ]
        );
    }
}
