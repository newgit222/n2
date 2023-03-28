<?php


namespace app\ms\controller;


use app\common\logic\MsMoneyType;
use think\Db;
use think\Request;

/**
 * 码商资金日志
 * Class Bills
 * @package app\ms\controller
 */
class Bills  extends Base
{

    /*
      *资金流水
      * @param Request $request
      * @return mixed
      * @throws \think\exception\DbException
      */
    public function lists(Request $request)
    {

        $someBill = Db::name('ms_somebill');
        $map = array();
        $map['a.uid'] = session('agent_id');
        //增加的查询条件
        //时间
        $startTime = $request->param('start_time', strtotime(date("Y-m-d", time())), 'strtotime');
        $endTime = $request->param('end_time', strtotime(date("Y-m-d", strtotime("+1 day"))), 'strtotime');
        if ($startTime && empty($endTime)) {
            $map['addtime'] = ['egt', $startTime];
        }
        if (empty($startTime) && $endTime) {
            $map['addtime'] = ['elt', $endTime];
        }
        if ($startTime && $endTime) {
            $map['addtime'] = ['between', [$startTime, $endTime]];
        }


        $billType = $request->param('bill_type', 0, 'intval');
        $billType && $map['jl_class'] = $billType;
   //     $username = $request->param('username', '', 'trim');
     //   $username && $map ['b.username'] = $username;
        $info = $request->param('info', '', 'trim');
        $info && $map ['a.info'] = ['like', '%' . $info . '%'];

        $listData = $someBill->alias('a')
            ->join("ms b", "a.uid=b.userid", "left")
            ->where($map)
            ->order('id desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $list = $listData->items();
        $count = $listData->count();
        $page = $listData->render();

        $bill_types = MsMoneyType::getMoneyOrderTypes();

        $this->assign('billTypes', $bill_types);
        $this->assign('count', $count);
        $this->assign('list', $list); // 賦值數據集
        $this->assign('count', $count);
        $this->assign('page', $page); // 賦值分頁輸出

        return $this->fetch();
    }



}
