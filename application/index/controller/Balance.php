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

namespace app\index\controller;

use app\common\library\enum\CodeEnum;
use think\captcha\Captcha;
use think\Request;

class Balance extends Base
{
    /**
     * 验证码
     *
     * @return \think\Response
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function vercode()
    {
        $config = [
            // 验证码字体大小
            'fontSize' => 14,
            // 验证码位数
            'length' => 4,
            // 关闭验证码杂点
            'useNoise' => false,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    /**
     * 资金详情与变动记录
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function index()
    {
        $where = ['uid' => is_login()];

        //详情
        $this->common($where);

        //变动记录
        $this->assign('list', $this->logicBalanceChange->getBalanceChangeList($where, true, 'id desc', 10));

        return $this->fetch();
    }

    /**
     * 收款账户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function account()
    {
        $where = ['a.uid' => is_login()];
        //详情
        $this->common();

        //组合搜索
        !empty($this->request->get('banker')) && $where['bank_id']
            = ['eq', $this->request->get('banker')];

        //状态
        if ($this->request->get('status') != "") {
//            echo 123; die();
            $where['a.status'] = ['eq', $this->request->get('status', '')];
        } else {
            $where['a.status'] = ['neq', -1];
        }

        //收款账户
        $this->assign('list', $this->logicUserAccount->getAccountList($where, 'a.*,b.id as b_id,b.name as banker', 'create_time desc'));

        return $this->fetch();
    }

    /**
     * 新增
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function addAccount()
    {
        if ($this->request->isPost()) {
            if ($this->request->post('b/a')['uid'] == is_login()) {
                $this->result($this->logicUserAccount->saveUserAccount($this->request->post('b/a')));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //银行
        $this->assign('banker', $this->logicBanker->getBankerList());

        return $this->fetch();
    }

    /**
     * 新增
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function editAccount()
    {

        if ($this->request->isPost()) {
            if ($this->request->post('b/a')['uid'] == is_login()) {
                $this->result($this->logicUserAccount->saveUserAccount($this->request->post('b/a')));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //银行
        $this->assign('banker', $this->logicBanker->getBankerList());

        //详情信息
        $this->assign('info', $this->logicUserAccount->getAccountInfo(['uid' => is_login(), 'id' => $this->request->param('id')]));

        return $this->fetch();
    }

    /*
     *商户删除自己绑定的的银行卡
     *
     */
    public function delAccount()
    {
        $where['id'] = $this->request->param('id');
        $accountInfo = $this->logicUserAccount->getAccountInfo($where);
        if ($accountInfo['uid'] != is_login()) {
            $this->error('参数错误');
        }
        $res = $this->logicUserAccount->delAccount($where);
        if ($res['code'] == CodeEnum::SUCCESS) {
            $this->success($res['msg']);
        }
        $this->error($res['msg']);
    }


    /**
     * 打款记录
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function paid()
    {
        $where = ['a.uid' => is_login()];

        $date = $this->request->param('d/a');


        $start = empty($date['start']) ? strtotime(date("Y-m-d")) - 30 * 24 * 60 * 60 : strtotime($date['start']);
        $end = empty($date['end']) ? strtotime(date("Y-m-d 23:59:59")) : strtotime($date['end']);

        $where['a.create_time'] = ['between', [$start, $end]];

        if (isset($_GET['status']) && $_GET['status'] !== "") {
            $where['a.status'] = $_GET['status'];
        }
        //列表
        $this->assign('list', $this->logicBalanceCash->getOrderCashList($where, 'a.*,u.account,u.account_name,b.name as method'));

        return $this->fetch();
    }

    /**
     * 自主提现申请
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function apply()
    {
        //用户信息
        $userInfo = $this->logicUser->getUserInfo(['uid' => session('user_info.uid')]);
        //google验证其二维码
        require_once EXTEND_PATH . 'PHPGangsta/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $where = ['uid' => is_login()];
        if ($this->request->isPost()) {
            //todo  google身份验证暂时关闭
            if($userInfo['is_need_google_verify']){
                //google身份验证
                $code = input('b.google_code');
//                $secret = session('google_secret');
                $userinfo = $this->logicUser->getUserInfo($where);
                $secret = $userinfo->google_secret_key ?? '';
                $checkResult = $ga->verifyCode($secret, $code, 1);
                if($checkResult==false){
                    $this->result(0,'google身份验证失败 ！！！');
                }
            }
            //校验令牌


            $token = input('__token__');
            if (session('__token__') != $token) {
                $this->result(0, '请不要重复发起提现,请刷新页面重试 ！！！');
            }
            session('__token__', null);

            if ($this->request->post('b/a')['uid'] == is_login()) {
                $this->result($this->logicBalanceCash->saveUserCashApply($this->request->post('b/a')));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //详情
        $this->common($where);
        //收款账户
        $withdraw_fee = $this->logicConfig->getConfigInfo(['name' => 'withdraw_fee'], 'value');
        $this->assign('fee', $withdraw_fee['value']);

        //usdt的手续费默认是百分比
        $usdt_rate = $this->logicConfig->getConfigInfo(['name' => 'withdraw_usdt_rate'], 'value');
        $this->assign('usdt_rate', $usdt_rate['value']);


        $this->assign('list', $this->logicUserAccount->getAccountList(['a.uid' => is_login(), 'a.status' => 1], 'a.*,b.id as b_id,b.name as banker', 'a.create_time desc'));
//        $secret = $ga->createSecret();
//        session('google_secret', $secret);
        $this->assign('user', $userInfo);
        $balance_cash_type = $this->logicConfig->getConfigInfo(['name' => 'balance_cash_type'], 'value');
        $this->assign('balance_cash_type', $balance_cash_type['value']);
     //   $this->assign('google_qr', $ga->getQRCodeGoogleUrl($userInfo['account'], $secret));
        $this->assign('google_qr', '');
        return $this->fetch();
    }


    /**
     * @return mixed
     * @throws \Exception
     * 充值申请
     */
    public function recharge()
    {
        //用户信息
        $userInfo = $this->logicUser->getUserInfo(['uid' => session('user_info.uid')]);

        $where = ['uid' => is_login()];
        if ($this->request->isPost()) {
            //todo  google身份验证暂时关闭

            //校验令牌
//            $token= input('__token__');
//            if(session('__token__')!= $token){
//                $this->result(0,'请不要重复发起提现,请刷新页面重试 ！！！');
//            }
//            session('__token__',null);

            if ($this->request->post('b/a')['uid'] == is_login()) {
                $this->result($this->logicDepositeOrder->addApply($this->request->post('b/a')));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //详情
        $this->common($where);
        //充值银行卡
        $this->assign('list', $this->logicDepositeCard->getCardList(['a.status' => 1, 'uid' => is_login()], 'a.*,b.name as banker', 'a.create_time desc', false));
        $this->assign('user', $userInfo);
        return $this->fetch();
    }

    /**
     * 充值记录
     */
    public function rechargeList(Request $request)
    {
        $where = ['a.uid' => is_login()];
        $startTime = $request->param('start');
        $endTime = $request->param('end');
        if ($startTime && empty($endTime)) {
            $where['a.create_time'] = ['egt', strtotime($startTime)];
        }
        if (empty($startTime) && $endTime) {
            $where['a.create_time'] = ['elt', strtotime($endTime)];
        }
        if ($startTime && $endTime) {
            $where['a.create_time'] = ['between', [strtotime($startTime), strtotime($endTime)]];
        }

        //指定时间段的统计start
        $successCount = $this->modelDepositeOrders->alias('a')->where($where)->where(['status' => 1])->count();
        $successMoney = $this->modelDepositeOrders->alias('a')->where($where)->where(['status' => 1])->sum('amount');
        $this->assign('successCount', $successCount);
        $this->assign('successMoney', $successMoney);
        //指定时间段的统计end

        //列表
        $this->assign('list', $this->logicDepositeOrder->getOrderList($where, 'a.*,b.name'));
        $this->assign('request', $request);
        return $this->fetch();
    }


    /**
     * Common
     *
     * @param array $where
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function common($where = [])
    {
        //资产信息
        $this->assign('info', $this->logicBalance->getBalanceInfo($where));
        //银行
        $this->assign('banker', $this->logicBanker->getBankerList());

    }


    /*
     *导出提现记录
     *
     */
    public function exportBalanceCash()
    {
        $where = ['a.uid' => is_login()];


        $start = empty($date['start']) ? strtotime(date("Y-m-d")) - 30 * 24 * 60 * 60 : strtotime($date['start']);
        $end = empty($date['end']) ? strtotime(date("Y-m-d 23:59:59")) : strtotime($date['end']);

        $where['a.create_time'] = ['between', [$start, $end]];

        if (isset($_GET['status']) && $_GET['status'] !== "") {
            $where['a.status'] = $_GET['status'];
        }
        //导出数据
        $data = $this->logicBalanceCash->getOrderCashList($where, 'a.*,u.account,b.name as method', 'a.create_time desc', false);
        //组装header 响应html为execl 感觉比PHPExcel类更快
        $orderStatus = ['驳回', '等待', '已打款', '等待', '待确认'];
        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">提现金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收款方式</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收款账户</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">更新时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
        $strTable .= '</tr>';

        if (is_array($data)) {
            foreach ($data as $k => $val) {
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['cash_no'] . ' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['amount'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['method'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['account'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['remarks'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['update_time'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $orderStatus[$val['status']] . '</td>';
                $strTable .= '</tr>';
                unset($data[$k]);
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, 'balance_cash');


    }


    /**
     * 导出资金账变
     *
     */
    public function exportBalanceChange()
    {


        $where = ['uid' => is_login()];

        //详情
        $this->common($where);
        $data = $this->logicBalanceChange->getBalanceChangeList($where, true, 'id desc', false);


        $moneyType = ['enable' => '可用余额', 'disable' => '冻结金额'];

        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">账变ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">类型</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">前额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">增加</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">扣减</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">后额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">时间</td>';
        $strTable .= '</tr>';

        if (is_array($data)) {
            foreach ($data as $k => $val) {
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['id'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $moneyType[$val['type']] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['preinc'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['increase'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['reduce'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['suffixred'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['remarks'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['create_time'] . '</td>';
                $strTable .= '</tr>';
                unset($data[$k]);
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, 'balance_change');
    }


}