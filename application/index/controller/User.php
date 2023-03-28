<?php

/**
 *  +----------------------------------------------------------------------
 *  | 狂神系统系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\index\controller;


use app\common\library\enum\CodeEnum;
use app\common\logic\GoogleAuthLogic;
use think\helper\Time;

class User extends Base
{

    /**
     * 商户首页
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function index()
    {
        $where = ['uid' => is_login()];
        list($start, $end) = Time::today();
        $where['create_time'] = ['between time', [$start, $end]];
        //是否开启代付
	$whether_open_daifu = $this->logicConfig->getConfigInfo(['name' => 'whether_open_daifu']);
	$whether_open_daifu =0 ;
        $this->assign('whether_open_daifu', $whether_open_daifu);
        //资金 资产信息
        $this->assign('wallet', $this->logicBalance->getBalanceInfo(['uid'=>is_login()]));
  //      if (1||$whether_open_daifu) {''
            //代付相关数据
            $today = $this->logicOrders->getDaifuStat($where);

            //今日
            list($start_1, $end_1) = Time::yesterday();
            $map = $where;
            $map['create_time'] = ['between time', [$start_1, $end_1]];
            $yesterday = $this->logicOrders->getDaifuStat($map);
           // dd($today);
            $this->assign('stat', [
                'today' => $today,
                'yesterday' => $yesterday
            ]);

//        } else {
            $this->assign('stat', $this->logicOrders->getWelcomeStat($where));
        //当月时间
        //当月数据统计
        $this->assign('month', $this->logicOrders->getWelcomeStat($where));
        //最新订单  当月时间
        $this->assign('list', $this->logicOrders->getOrderList($where, true, 'create_time desc', '5'));

//        $Api = new Api();

        $this->apiCommon();

        return $this->fetch();
    }


    /**
     * API公共
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function apiCommon()
    {
//        if ($this->request->isPost()) {
//
//            //暂时去掉商户设置ip白名单
//            if (isset($this->request->post('u/a')['auth_ips'])) {
//                $this->result(0, '非法操作，请重试！');
//            }
//
//            if ($this->request->post('u/a')['uid'] == is_login()) {
//                $this->result($this->logicApi->editApi($this->request->post('u/a')));
//            } else {
//                $this->result(0, '非法操作，请重试！');
//            }
//        }
        $this->assign('api', $this->logicApi->getApiInfo(['uid' => is_login()]));

        $this->assign('rsa', $this->logicConfig->getConfigInfo(['name' => 'rsa_public_key'], 'value'));
        $this->assign('notify_ip', $this->logicConfig->getConfigInfo(['name' => 'notify_ip'], 'value'));
    }

    /**
     * 通知公告
     *
     * @param $id
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function notice($id)
    {

        $this->assign('notice', $this->logicArticle->getNoticeInfo(['id' => $id]));
        $this->assign('list', $this->logicArticle->getNoticeList([], true, 'create_time desc', 10));
        return $this->fetch();
    }

    /**
     * 订单月统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrderStat()
    {

        $res = $this->logicOrders->getOrdersMonthStat();

        $data = [
            'orders' => get_order_month_stat($res, 'total_orders'),
            'fees' => get_order_month_stat($res, 'total_amount'),
        ];
        $this->result(CodeEnum::SUCCESS, '', $data);
    }


    /*
     *生成用户google验证信息
     */
    public function makeGoogle()
    {
        $data['secret'] = $this->logicGoogleAuth->createSecretkey();
        $data['google_qr'] = $this->logicGoogleAuth->getQRCodeGoogleUrl($data['secret']);
        return $data;
    }

    /*
     *商户绑定登录IP白名单
     */
//    public function blndIp()
//    {
//        $userInfo = $this->logicUser->getUserInfo(['uid' => is_login()]);
//        $this->assign('ips', explode(',', $userInfo['auth_login_ips']));
//        return $this->fetch('blind_ip');
//    }


    /*
     *商户绑定GOOGLE验证码
     *
     */
    public function blndGoogle()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('i/a');
            if (empty($data['google_secret_key'])) {
                $this->result(0, '参数错误');
            }
            if (empty($data['google_code'])) {
                $this->result(0, '请输入GOOGLE验证码');
            }
            $ret = $this->logicGoogleAuth->checkGoogleCode($data['google_secret_key'], $data['google_code']);
            if ($ret == false) {
                $this->result(0, '绑定GOOGLE失败,请扫码重试');
            }
            unset($data['google_code']);
            $data['is_need_google_verify'] = 1;
            $ret = $this->modelUser->where(['uid' => is_login()])->update($data);
            if ($ret !== false) {
                $this->result(1, '绑定成功');
            }
            $this->result(0, '绑定失败');
        }
        //获取商户详细信息
        $userInfo = $this->logicUser->getUserInfo(['uid' => is_login()]);
        $this->assign('user', $userInfo);
        if ($userInfo['is_need_google_verify'] == 0) {
            $google['google_secret'] = $this->logicGoogleAuth->createSecretkey();
            $google['google_qr'] = $this->logicGoogleAuth->getQRCodeGoogleUrl($google['google_secret']);
            $this->assign('google', $google);
        }
        return $this->fetch('blind_google');
    }


    /**
     * 商户信息
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function info()
    {
        $this->common();
        return $this->fetch();
    }

//     * 密码管理
//     *
//     * @return mixed
//     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
//     *
//     */
    public function password()
    {
        if ($this->request->isPost()) {
            if ($this->request->post('p/a')['uid'] == is_login()) {
                $this->result($this->logicUser->changePwd($this->request->post('p/a')));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //获取商户详细信息
        $this->assign('user', $this->logicUser->getUserInfo(['uid' => is_login()]));

        return $this->fetch();
    }

    /**
     * 操作日志
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function log()
    {

        $where = ['uid' => is_login()];
        //组合搜索

        $where['module'] = ['like', 'index'];

        //时间搜索  时间戳搜素
        $where['create_time'] = $this->parseRequestDate();

        $this->assign('list', $this->logicLog->getLogList($where, true, 'create_time desc', 10));

        return $this->fetch();
    }

    /**
     * Common
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function common()
    {
        $this->assign('user', $this->logicUser->getUserInfo(['uid' => is_login()]));

    }
    /**
     * 常见问题
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     */
    public function faq()
    {
        return $this->fetch();
    }
}
