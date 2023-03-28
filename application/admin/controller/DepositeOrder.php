<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/3/28
 * Time: 1:04
 */

namespace app\admin\controller;


use app\common\library\enum\CodeEnum;

class DepositeOrder extends BaseAdmin
{
    /**
     * @return mixed
     * 充值订单列表
     */
    public function index()
    {

        //代付订单统计
        $where['status'] = 1;
        $depositeOrderCal = $this->logicDepositeOrder->calute($where);
        $this->assign('calute', $depositeOrderCal);
        return $this->fetch();
    }

    /**
     * 获取订单列表
     */
    public function getOrderList()
    {
        $where = [];
        if ($this->request->param('status') != "") {
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


        //时间搜索  时间戳搜素
        $parseTime = $this->parseRequestDate3();
        $where['a.create_time'] = $parseTime;

        $fields = 'a.*,b.name,c.username';
        $data = $this->logicDepositeOrder->getOrderList($where, $fields, 'create_time desc', false);

        $count = $this->modelDepositeOrders->alias('a')->where($where)->count();

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
            ]);
    }


    /**
     * 审核成功
     */
    public function auditSuccess()
    {
        $this->result($this->logicDepositeOrder->successOrder($this->request->post('id')));
    }

    /**
     * 驳回
     */
    public function auditError()
    {
        $this->result($this->logicDepositeOrder->errorOrder($this->request->post('id'), 0));
    }


    /***
     * 充值统计
     */
    public function searchOrderMoney()
    {

        $where['status'] = 1;
        !empty($this->request->param('orderNum')) && $where['a.trade_no']
            = ['eq', $this->request->param('orderNum')];
        !empty($this->request->param('trade_no')) && $where['a.out_trade_no']
            = ['eq', $this->request->param('trade_no')];
        //组合搜索

        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];
        //时间搜索  时间戳搜素
        $parseTime = $this->parseRequestDate3();
        $where['create_time'] = $parseTime;
        $depositeOrderCal = $this->logicDepositeOrder->calute($where);
        exit(json_encode($depositeOrderCal));
    }

}