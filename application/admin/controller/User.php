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
use app\common\library\enum\UserStatusEnum;
use app\common\logic\TgLogic;
use app\common\model\UserPayCode;
use app\common\model\UserPayCodeAppoint;
use app\index\controller\Tg;
use think\Cache;
use think\helper\Str;

class User extends BaseAdmin
{

    /*
     *系统统计
     *
     */
    public function cal()
    {
        return $this->fetch();
    }
public function tongbu()
{
//$data =  db('ewm_order')->where(['status' =>1])->find();
$data = db()->query('select o.trade_no,e.notify_url from cm_ewm_order as e left join cm_orders as o on o.trade_no = e.order_no  where e.status=1 and o.status=1 order by o.create_time desc limit 100');
var_dump($data);
$postData['out_trade_no'] = $data[0]['trade_no'];
$orderInfo['notify_url'] =  $data[0]['notify_url'];
//var_dump($postData);die();
        $ret = httpRequest($orderInfo['notify_url'], 'post', $postData);
//var_dump($data);die();
//echo 3;die();

}

    /**
     *
     */
    public function calList()
    {

        $time = strtotime($this->request->param('start', date('Y-m-d')));
        $where['create_time'] = ['between', [$time, $time + 86400]];
        $data = [];
        //订单总金额+完成金额
        $orderInfo = $this->logicOrders->getOrderInfo($where, 'COALESCE(sum(`amount`),0) as amount,COALESCE(sum(if(status=2,amount,0)),0) as paid');
        $data['order_money'] = $orderInfo['amount'];
        $data['order_paid_money'] = $orderInfo['paid'];
        //手续费+提现成功金额

        $cashInfo = $this->modelBalanceCash->where(array_merge($where, ['status' => 2]))->field('COALESCE(sum(`amount`),0) as amount,COALESCE(sum(`commission`),0) as commission')->find();
        $data['cash_amount'] = $cashInfo['amount'];
        $data['cash_fee'] = $cashInfo['commission'];

        //手动增加和减少余额
        $balanceChangeInfo = $this->logicBalanceChange->getBalanceChangeInfo(array_merge($where, ['is_flat_op' => 1]), 'COALESCE(sum(`increase`),0) as increase,COALESCE(sum(`reduce`),0) as reduce');
        $data['increase'] = $balanceChangeInfo['increase'];
        $data['reduce'] = $balanceChangeInfo['reduce'];
        $arr[0] = $data;
        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => 1,
                'data' => $arr
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => 0,
                'data' => $data
            ]
        );
    }


    /**
     * 商户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function index()
    {

        return $this->fetch();
    }

    /**
     * 商户列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getList()
    {
        $where = [];

        //组合搜索
        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];

        !empty($this->request->param('username')) && $where['username']
            = ['like', '%' . $this->request->param('username') . '%'];

        !empty($this->request->param('email')) && $where['account']
            = ['like', '%' . $this->request->param('email') . '%'];

        if ($this->request->get('status') !== '') {
            $where['status'] = ['eq', $this->request->get('status', UserStatusEnum::ENABLE)];
        }

        //时间搜索  时间戳搜素
        // $where['create_time'] = $this->parseRequestDate();
        //dd($where);

        $data = $this->logicUser->getUserList($where, true, 'create_time desc', false);

        $is_open_daifu = 0;
        //获取配置 判断代付是否开启
        //判断代付是否开启
        $whether_open_daifu = \app\common\model\Config::where(['name' => 'whether_open_daifu'])->find()->toArray();
        if ($whether_open_daifu) {
            if ($whether_open_daifu['value'] == '1') {
                $is_open_daifu = 1;
            }
        }
        foreach ($data as $k => $v) {
            $data[$k]['whether_open_daifu'] = $is_open_daifu;
        }
        $count = $this->logicUser->getUserCount($where);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => $count,
                'data' => $data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => $count,
                'data' => $data
            ]
        );
    }


    /**
     * 添加商户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function add()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUser->addUser($this->request->post()));


        //所有代理商
        $where['is_agent'] = $where['status'] = 1;
        $agents = $this->logicUser->getUserList($where, 'uid,puid,username', 'create_time desc', false);
        $this->assign('agents', $agents);
        return $this->fetch();
    }

    /**
     * 编辑商户基本信息
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function edit()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUser->editUser($this->request->post()));
        //获取商户详细信息
        $this->assign('user', $this->logicUser->getUserInfo(['uid' => $this->request->param('id')]));
        //所有代理商
        $where['is_agent'] = $where['status'] = 1;
        $agents = $this->logicUser->getUserList($where, 'uid,puid,username', 'create_time desc', false);
        $this->assign('agents', $agents);
        return $this->fetch();
    }

    /**
     * 修改商户状态
     *
     * @param int $id
     * @param bool $status
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function changeStatus($id = 0, $status = false)
    {

        $this->result($this->logicUser->setUserStatus(['uid' => $id], $status == 1 ? '0' : '1'));
    }

    /**
     * 删除商户
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function del()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result(
            $this->logicUser->delUser(
                [
                    'uid' => $this->request->param('uid')
                ])
        );
        // get 直接报错
        $this->error([CodeEnum::ERROR, '未知错误']);
    }

    /**
     * 认证信息
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function auth()
    {
        return $this->fetch();
    }

    /**
     * 商户认证信息列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAuthList()
    {
        $where = [];

        //组合搜索
        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];

        $where['status'] = ['eq', $this->request->get('status', '1')];

        //时间搜索  时间戳搜素
        $where['create_time'] = $this->parseRequestDate();

        $data = $this->logicUser->getUserAuthList($where, true, 'create_time desc', false);

        $count = $this->logicUser->getUserAuthCount($where);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => $count,
                'data' => $data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => $count,
                'data' => $data
            ]
        );
    }

    /**
     * 认证详细信息
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function userAuthInfo()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUser->saveUserAuth($this->request->post()));
        //获取认证详细信息
        $auth = $this->logicUser->getUserAuthInfo(['uid' => $this->request->param('id')]);
        $auth['card'] = json_decode($auth['card'], true);

        $this->assign('auth', $auth);

        return $this->fetch();
    }

    /**
     * 分润设置
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     */
    public function profit()
    {
        // post 是提交数据
        if ($this->request->isPost()) {
            $data = $this->request->post('r/a');
            foreach ($data as $key => $item) {
                //查
                $profit = $this->logicUser->getUserProfitInfo(['uid' => $item['uid'], 'cnl_id' => $item['cnl_id']]);
                if ($profit) {
                    $data_update[] = [
                        'id' => $profit['id'],
                        'uid' => $item['uid'],
                        'cnl_id' => $item['cnl_id'],
                        'urate' => $item['urate'],
                        'grate' => 1,
                        'single_handling_charge' => isset($item['single_handling_charge']) ? $item['single_handling_charge'] : 0,
                    ];
                } else {
                    $data_update[] = [
                        'uid' => $item['uid'],
                        'cnl_id' => $item['cnl_id'],
                        'urate' => $item['urate'],
                        'grate' => 1,
                        'single_handling_charge' => isset($item['single_handling_charge']) ? $item['single_handling_charge'] : 0,
                    ];
                }

            }
            $this->result($this->logicUser->saveUserProfit($data_update));
        };

        //所有渠道列表
        $list = $this->logicPay->getChannelList(['status' => '1'], true, 'create_time desc', false);
        $channel_array = [];
        foreach ($list as $k => $v) {
            $channel_array[] = $v['id'];
        }
        //所有渠道列表
        $channel = $this->logicPay->getAccountList(['cnl_id' => ['in', $channel_array]], true, 'create_time desc', false);
//        dd(collection($channel)->toArray());

        //获取商户分润详细信息
        $userProfit = $this->logicUser->getUserProfitList(['uid' => $this->request->param('id')]);

        if ($userProfit) {
            foreach ($userProfit as $item) {
                $_tmpData[$item['cnl_id']] = $item;
            }
        }

        //重组渠道列表
        if ($channel) {
            foreach ($channel as $key => $item) {
                //dump($item);
                $channel[$key]['urate'] = isset($_tmpData[$item['id']]['urate']) ? $_tmpData[$item['id']]['urate'] : $item['urate'];
                $channel[$key]['grate'] = isset($_tmpData[$item['id']]['grate']) ? $_tmpData[$item['id']]['grate'] : $item['grate'];
                $channel[$key]['single_handling_charge'] = isset($_tmpData[$item['id']]['single_handling_charge']) ? $_tmpData[$item['id']]['single_handling_charge'] : 0;
            }
        }
        // dd(collection($channel)->toArray());
        //dd($channel);

        $this->assign('list', $channel);;

        //读取配置 是否开启单笔手续费
        $is_open = 0;
        $is_single_handling_charge = \app\common\model\Config::where(['name' => 'is_single_handling_charge'])->find()->toArray();
        if ($is_single_handling_charge) {
            if ($is_single_handling_charge['value'] == 1) {
                $is_open = 1;
            }
        }
        $this->assign('is_open', $is_open);

        return $this->fetch();
    }


    /**
     * 代付费率设置
     *
     */
    public function daifuProfit()
    {

        $uid = $this->request->param('uid');
        if (!$uid) {
            $this->result('0', '非法操作', []);
        }
        // post 是提交数据
        if ($this->request->isPost()) {
            $data = $this->request->post('r/a');

            $this->result($this->logicUser->saveUserDaifuProfit($data));
        };

        $this->assign('info', $this->logicUser->getUserDaifuProfitInfo(['uid' => $uid]));;

        return $this->fetch();
    }


    /*
    *用户支付渠道pay_code
    *
    */
    public function codes()
    {
        $uid = $this->request->param('id');
        if ($this->request->isPost()) {
            $data = $this->request->post('r/a');
            $this->result($this->logicUser->doUserPayCodes($uid, $data));
        }
        $data = $this->logicPay->getCodeList([], true, 'create_time desc', false);
        foreach ($data as $k => $v) {
            $userCode = $this->logicUser->userPayCode(['uid' => $uid, 'co_id' => $v['id']]);
            $data[$k]['status'] = $userCode ? $userCode['status'] : -1;
        }
        $this->assign('list', $data);;
        return $this->fetch();
    }


    /**
     * 清除绑定Google
     */
    public function clearGoogleAuth()
    {
        $userId = $this->request->param('uid');
        if (!$userId) {
            $this->error('非法操作');
        }

        $result = \app\common\model\User::where(['uid' => $userId])->update(['is_need_google_verify' => 0, 'google_secret_key' => '']);
        if ($result !== false) {
            $this->success('操作成功');
        }
        $this->error('错误请重试');

    }


    /**
     * 商户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function appoint_ndex()
    {

        $this->assign('uid', $this->request->param('uid'));

        return $this->fetch();
    }

    /**
     * 商户指定渠道列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function appoint_get_list()
    {
        $where = [];
        //组合搜索
        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];
        $model = new UserPayCodeAppoint();
        $data = $model->order('a.createtime desc')
            ->field('a.*,u.username,pc.name,pc.code,ch.name as ch_name,ch.action as ch_action')
            ->alias('a')
            ->join('cm_user u', 'u.uid = a.uid')
            ->join('cm_pay_code pc', 'pc.id = a.pay_code_id')
            ->join('cm_pay_channel ch', 'ch.id = a.cnl_id')
            ->where($where)
            ->select();
        $count = $model
            ->field('a.*,u.username,pc.name,pc.code,ch.name as ch_name,ch.action as ch_action')
            ->alias('a')
            ->join('cm_user u', 'u.uid = a.uid')
            ->join('cm_pay_code pc', 'pc.id = a.pay_code_id')
            ->join('cm_pay_channel ch', 'ch.id = a.cnl_id')
            ->where($where)
            ->count();
        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => $count,
                'data' => $data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => $count,
                'data' => $data
            ]
        );
    }


    /**
     * 添加商户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function appoint_add()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUser->addAppoint($this->request->post()));


        //所有商户
        $users = $this->logicUser->getUserList([], 'uid,puid,username', 'create_time desc', false);
        $this->assign('users', $users);

        //所有支付产品
        $pay_code = $this->logicPay->getCodeList([], 'id,name,code', 'create_time desc', false);
        $this->assign('pay_code', $pay_code);

        return $this->fetch();
    }

    /**
     * 获取渠道列表
     */
    public function get_channel_list()
    {

        $id = $this->request->param('id');

        $codeInfo = $this->logicPay->getCodeInfo(['id' => $id]);
        $ids = [];
        if ($codeInfo) {
            $ids = $codeInfo['cnl_id'];
        }

        $list = $this->logicPay->getChannelList(['id' => ['in', $ids]], 'id,name,action', 'create_time desc', false);
        $this->result([
            'code' => CodeEnum::SUCCESS,
            'msg' => '',
            'count' => count($list),
            'data' => $list
        ]);
    }

    /**
     * 编辑商户基本信息
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function appoint_edit()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUser->editAppoint($this->request->post()));


        $appoint = $this->modelUserPayCodeAppoint->where(['appoint_id' => $this->request->param('appoint_id')])->find();

        $this->assign('appoint', $appoint);
        //所有商户
        $users = $this->logicUser->getUserList([], 'uid,puid,username', 'create_time desc', false);
        $this->assign('users', $users);

        //所有支付产品
        $pay_code = $this->logicPay->getCodeList([], 'id,name,code', 'create_time desc', false);
        $this->assign('pay_code', $pay_code);

        return $this->fetch();
    }


    /**
     * 删除
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function appoint_del()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result(
            $this->logicUser->delAppoint(
                [
                    'appoint_id' => $this->request->param('appoint_id')
                ])
        );
        // get 直接报错
        $this->error([CodeEnum::ERROR, '未知错误']);
    }

    /**
     *解绑此商户的TG群
     */
    public function unblindTgGroup()
    {
        $userId = $this->request->param('uid');
        if (!$userId) {
            $this->error('非法操作');
        }
        $result = \app\common\model\User::where(['uid' => $userId])->update(['tg_group_id' => '']);
        if ($result !== false) {
            $this->success('操作成功');
        }
        $this->error('错误请重试');

    }


    /**
     * 给商户群发消息
     */
    public function sendTgMessage()
    {
        set_time_limit(0);
        $content = $this->request->param('content');
        $img = $this->request->param('img');
        $uids = $this->request->param('uids');
        $uidsArr = explode(',', $uids);
        if (empty($img) && empty($content)) {
            $this->error('图片和文本必传其一');
        }
        $map = [];
        $uids && $map['uid'] = ['in', $uidsArr];
        $users = $this->logicUser->getUserList($map, 'tg_group_id', '', false);
        $tgGroupIds = array_filter(array_column(collection($users)->toArray(), 'tg_group_id'));
        //群发消息
        $tgLogic = new TgLogic();
        if ($tgGroupIds) {
            $send_message = Cache::get('send_message');
            if (!$send_message or $send_message === 0){
                Cache::set('send_message', 1);
                Cache::set('send_message_content', json_encode(compact('img', 'content')));
            }else{
                $this->error('上一次群发消息未完成，请稍后在试！');
            };
            (new Tg())->groupSendMessage();
          /*foreach ($tgGroupIds as $tgGroupId) {
                $serverImg = request()->domain() . $img;
                // $serverImg = "https://www.layui.com/layuiadmin/pro/dist/style/res/template/portrait.png";
                $option = $content ? ['caption' => $content] : [];
                if ($img) {
                    $tgLogic->sendPhoto($tgGroupId, $serverImg, $option);
                } else {
                    $tgLogic->sendMessageTogroup($content, $tgGroupId);
                }

            }*/
        }
        $this->success('推送成功');
    }


    /**
     * 给商户推送的页面
     */
    public function addPushs()
    {
        return $this->fetch();
    }


    /**
     * （标记or取消标记）商户
     */
    public function mark_abnormal()
    {
        $val = $this->request->param('val', 0);
        $uid =  $this->request->param('uid');
        $row = $this->modelUser->where(['uid' => $uid, 'mark_abnormal' => $val ? 0 : 1])->find();
        if (!$row){
            $this->error('商户错误');
        }
        $row->mark_abnormal = $row->mark_abnormal ? 0 : 1;
        $row->save();
        $this->success('操作成功');
    }
}
