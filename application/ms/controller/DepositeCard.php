<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/1/23
 * Time: 16:36
 */

namespace app\ms\controller;


use app\admin\logic\AdminLogic;
use app\admin\logic\TransferLogic;
use app\admin\Model\ConfigModel;
use app\admin\model\TransferBankModel;
use app\admin\model\TransferModel;
use app\common\library\enum\CodeEnum;
use app\index\model\BankcardModel;
use app\ms\controller\Base;
use think\Collection;
use think\Db;
use think\Exception;
use think\Request;


/**
 * 代付银行卡管理
 * Class DepositeCard
 * @package app\ms\controller
 */
class DepositeCard extends Base
{


    /**
     * 获取指定码商的代付银行卡
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepositeCardByUid()
    {
        if ($this->request->param('uid') != "") {
            $where['uid'] = ['eq', $this->request->param('uid')];
        }
        $listData = $this->modelDepositeCard->where($where)->order(['create_time' => 'desc'])->select();
        $this->assign('list', $listData);
        return $this->fetch();

    }

    /**
     * 获取充值银行卡列表
     */
    public function getDepositeCardList()
    {
        $where = [];

        $where['ms_id'] = $this->agent_id;

        if ($this->request->param('uid') != "") {
            $where['a.uid'] = ['eq', $this->request->param('uid')];
        }

        if ($this->request->param('status') != "") {
            $where['status'] = ['eq', $this->request->param('status')];
        }
        !empty($this->request->param('bank_account_username')) && $where['bank_account_username']
            = ['like', '%' . $this->request->param('bank_account_username') . '%'];

        !empty($this->request->param('bank_account_number')) && $where['bank_account_number']
            = ['like', '%' . $this->request->param('bank_account_number') . '%'];
        $fields = 'a.*,b.name,c.username';
        $listData = $this->logicDepositeCard->getCardListV2($where, $fields, 'create_time desc', 15);

        $list = $listData->items();
        $count = $listData->count();
        $page = $listData->render();
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);
        return $this->fetch();

    }


    //添加充值银行卡
    public function addDepositeCard()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['ms_id'] = $this->agent_id;
            $res = $this->logicDepositeCard->saveCard($data, 'add');
            if ($res['code'] == 1) {
                $this->success($res['msg'], 'getDepositeCardList');
            }
            $this->error($res['msg']);
        }
        $this->assign('bank', $this->logicBanker->getBankerList());
        $this->assign('users', $this->logicUser->getUsersByMsId($this->agent_id));
        return $this->fetch();
    }


    //编辑充值银行卡
    public function editDepositeCard()
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['ms_id'] = $this->agent_id;
            $res = $this->logicDepositeCard->saveCard($data, 'edit');
            if ($res['code'] == 1) {
                $this->success($res['msg'], 'getDepositeCardList');
            }
            $this->error($res['msg']);
        }
        $this->assign('bank', $this->logicBanker->getBankerList());
        $this->assign('info', $this->logicDepositeCard->getCard($this->request->param('id')));
        $this->assign('users', $this->logicUser->getUsersByMsId($this->agent_id));
        return $this->fetch('add_deposite_card');
    }

    //删除充值银行卡
    public function delDepositeCard()
    {
        $this->result($this->logicDepositeCard->delCard(['id' => $this->request->param('id')]));
    }

    /**
     * 列表
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {

        $where['ms_id'] = $this->agent_id;

        if ($this->request->param('status') != "") {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }

        if ($this->request->param('uid') != "") {
            $where['a.uid'] = ['eq', $this->request->param('uid')];
        }

        !empty($this->request->param('bank_account_username')) && $where['bank_account_username']
            = ['like', '%' . $this->request->param('bank_account_username') . '%'];
        !empty($this->request->param('trade_no')) && $where['trade_no']
            = ['like', '%' . $this->request->param('trade_no') . '%'];

        !empty($this->request->param('bank_account_number')) && $where['bank_account_number']
            = ['like', '%' . $this->request->param('bank_account_number') . '%'];

        //时间搜索  时间戳搜素
        $parseTime = $this->parseRequestDate3();
        $where['a.create_time'] = $parseTime;
        $fields = 'a.*,b.name,c.username';
        $data = $this->logicDepositeOrder->getOrderList($where, $fields, 'create_time desc', false);

        $count = $this->modelDepositeOrders->alias('a')->where($where)->count();


        //读取上级管理员下的转账
        $map = array();
        $map['ms_id'] = $this->agent->userid;
        $username = $request->param('username');
        if ($username) {
            $map['username'] = array('like', "%{$username}%");
        }
        $mobile = $request->param('mobile/s');
        if ($mobile) {
            $map['mobile'] = array('like', "%{$mobile}%");
        }
        $uid = $request->param('uid');
        if ($uid) {
            $map['uid'] = array('like', "%{$username}%");
        }


        $TransferBankModel = $this->modelMsBank;
        $listData = $TransferBankModel->order("transfer_bank_id desc")->where($map)->paginate(15);
        $list = $listData->items();
        $count = $listData->count();
        $page = $listData->render();
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);
        return $this->fetch();
    }


    /**
     * 验证
     */
    public function check($param)
    {
        if (!isset($param['mobile']) || !$param['mobile']) {
            return '请输入手机号';
        }
        if (!isset($param['realname']) || !$param['realname']) {
            return '请输入真实姓名';
        }
        if (!isset($param['password']) || !$param['password']) {
            return '请输入网银登录密码';
        }
        if (!isset($param['pay_pwd']) || !$param['pay_pwd']) {
            return '请输入支付密码';
        }
        if (!isset($param['account_number']) || !$param['account_number']) {
            return '请输入银行卡号';
        }
//        if(!isset($param['username']) || !$param['username'] ){
//            return '请输入设备用户名';
//        }
//        if(!isset($param['user_password']) || !$param['user_password'] ){
//            return '请输入设备密码';
//        }
        if (!isset($param['status'])) {
            return '请选择状态';
        }
        if (!isset($param['bank_type']) || !$param['bank_type']) {
            return '请选择银行类型';
        }
        $TransferBankModel = $this->modelMsBank;
        $account_number = $TransferBankModel->where(['account_number' => $param['account_number']])->find();
        if ($account_number) {
            return '银行卡已存在';
        }
        return true;
    }


    /**
     * 新增
     *
     */
    public function bank_add(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();

            unset($data['id']);
            //重复验证
            $result = $this->check($data);
            if ($result !== true) {
                $this->error($result);
            }
            $data['createtime'] = time();
            $data['updatetime'] = time();

            $data['ms_id'] = $this->agent->userid;
//            $data['admin_id'] = $this->agent->add_admin_id;
            if ($data) {
                $id = Db::name('ms_bank')->insert($data);
                if ($id) {
                    $this->success('新增成功', url('index'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error('新增失败了!');
            }
        } else {
            return $this->fetch('bank_edit');
        }
    }


    /**
     * 编辑
     *
     */
    public function bank_edit($id, Request $request)
    {

        $map = [
            'transfer_bank_id' => $id,
            'ms_id' => $this->agent->userid,
//            'admin_id' => $this->agent->add_admin_id,
        ];
        $info = Db::name('ms_bank')->where($map)->find();
        if (!$info) {
            $this->error('非法操作');
        }
        if ($request->isPost()) {
            $data = $request->post();
            $data['updatetime'] = time();
            if ($data) {
                $i_admin_bank_id = $data['id'];
                unset($data['id']);

                $result = Db::name('ms_bank')->where('transfer_bank_id', $i_admin_bank_id)->update($data);
                if ($result) {
                    $this->success('更新成功', url('index'));
                } else {
                    $this->error('更新失败,或数据没有更新');
                }
            } else {
                $this->error('更新失败!');
            }
        } else {

            $this->assign('info', $info);

            return $this->fetch('bank_edit');
        }
    }


    /**
     * 删除
     */
    public function bank_del()
    {

        $id = input('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
//        $adminLogic = new AdminLogic();
//        $admin_id = $adminLogic->is_login();
//        if ($admin_id != 1) {
//            $info = Db::name('transfer_bank')->find($id);
//            if (!$info || $info['admin_id'] != $admin_id) {
//                $this->error('这不是您的银行账号，不能删除');
//            }
//        }
        $re = Db::name('ms_bank')->where(array('transfer_bank_id' => $id))->delete();
        if ($re) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


    /**
     * 删除流水日志
     */
    public function DelChangeLog()
    {
        $id = input('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $re = $this->modelDepositeCardLog->where(array('log_id' => $id))->delete();
        if ($re) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


    /**
     *代付银行卡变动日志
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function changeLog(Request $request)
    {
        if ($this->request->param('id') != "") {
            $where['bank_id'] = ['eq', $this->request->param('id')];
        }
        $fields = '*';
        $listData = $this->modelDepositeCardLog->where($where)->field($fields)->order(['create_time' => 'desc'])->paginate(15);
        $page = $listData->render();
        $this->assign('list', $listData);
        $this->assign('page', $page);
        return $this->fetch();
    }


    /**
     * 代付银行卡充值订单
     * @throws Exception
     */
    public function orders(Request $request)
    {
        $where = [];
        $status = $this->request->param('status', -1);
        if ($status != -1) {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }

        !empty($this->request->param('bank_account_username')) && $where['bank_account_username']
            = ['like', '%' . $this->request->param('bank_account_username') . '%'];
        !empty($this->request->param('trade_no')) && $where['trade_no']
            = ['like', '%' . $this->request->param('trade_no') . '%'];

        !empty($this->request->param('bank_account_number')) && $where['bank_account_number']
            = ['like', '%' . $this->request->param('bank_account_number') . '%'];
        !empty($this->request->param('uid')) && $where['a.uid']
            = $this->request->param('uid');
//        //时间搜索  时间戳搜素
//        $parseTime = $this->parseRequestDate3();
//        $where['a.create_time'] = $parseTime;

        $startTime = $request->param('start_time');
        $endTime = $request->param('end_time');
        if ($startTime && empty($endTime)) {
            $where['a.create_time'] = ['egt', strtotime($startTime)];
        }
        if (empty($startTime) && $endTime) {
            $where['a.create_time'] = ['elt', strtotime($endTime)];
        }
        if ($startTime && $endTime) {
            $where['a.create_time'] = ['between', [strtotime($startTime), strtotime($endTime)]];
        }

        //当前码商所管的商户
        $users = $this->logicUser->getUsersByMsId($this->agent_id);
        $users = collection($users)->toArray();
        $where['a.uid'] = ['in', array_column($users, 'uid')];

        $fields = 'a.*,b.name,c.username';
        $listData = $this->logicDepositeOrder->getOrderList($where, $fields, 'create_time desc', 15);
        $page = $listData->render();
        $this->assign('list', $listData);
        $this->assign('status', $status);
        $this->assign('page', $page);
        return $this->fetch();
    }


    /**
     * 审核成功
     */
    public function auditSuccess()
    {
        $this->result($this->logicDepositeOrder->successOrder($this->request->param('id')));
    }

    /**
     * 驳回
     */
    public function auditError()
    {
        $this->result($this->logicDepositeOrder->errorOrder($this->request->param('id'), 0));
    }


    /**
     * 银行卡代付统计
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\exception\DbException
     */

    public function logs(Request $request)
    {
        $where = [];
        $where['a.create_time'] = $this->parseRequestDate3();
        !empty($this->request->param('bank_account_number')) && $where['bank_account_number']
            = ['like', '%' . $this->request->param('bank_account_number') . '%'];

        $where['ms_id'] = $this->agent_id;
        $where['chang_type'] = 2;
        $where['type'] = 2;
        $fields = ['bank_account_username', 'bank_account_number', "sum(amount) total_amount","count(log_id) as total_count"];
        $cals = $this->modelDepositeCardLog->alias('a')
            ->join('deposite_card b', 'a.bank_id=b.id', 'left')
            ->where($where)
            ->group('a.bank_id')
            ->field($fields)
            ->select();
        $this->assign('list', $cals);
        return $this->fetch();
    }


}
