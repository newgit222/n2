<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/7
 * Time: 21:27
 */

namespace app\ms\controller;


use app\common\library\enum\CodeEnum;
use app\common\logic\MsMoneyType;
use think\Db;
use think\Exception;
use think\Request;

class DaifuOrders extends Base
{

    /**
     * @return mixed
     * 代付订单列表
     */
    public function index(Request $request)
    {
        $where = [];
        //当前时间段统计

        $startTime = $request->param('start_time');
        //dd($startTime);
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


        //指定时间段的统计start
        $successWhere['status'] = 2;
        $successWhere['ms_id'] = $this->agent_id;
        $successWhere['create_time'] = $this->parseRequestDate4();
        $successCount = $this->modelDaifuOrders->where($successWhere)->count();
        $successMoney = $this->modelDaifuOrders->where($successWhere)->sum('amount');
        $this->assign('successCount', $successCount);
        $this->assign('successMoney', $successMoney);
        //指定时间段的统计end


        $status = $this->request->param('status', -1);
        $status != -1 && $where['a.status'] = ['eq', $status];
        $where['a.ms_id'] = $this->agent_id;
        !empty($this->request->param('trade_no')) && $where['trade_no']
            = ['eq', $this->request->param('trade_no')];
        !empty($this->request->param('out_trade_no')) && $where['out_trade_no']
            = ['eq', $this->request->param('out_trade_no')];
        //组合搜索
        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];


        $fields = ['a.*', 'b.pao_ms_ids', 'c.username', 'bank_account_username', 'bank_account_number', 'e.enable'];
        $query = $this->modelDaifuOrders->alias('a')
            ->join('user b', 'a.uid=b.uid', 'left')
            ->join('ms c', 'a.ms_id=c.userid', 'left')
            ->join('deposite_card d', 'a.df_bank_id=d.id', 'left')
            ->join('cm_balance e', 'a.uid=e.uid', 'left')
            ->field($fields)
            ->order('id desc')
            ->where($where);

        if ($status == '-1' or $status == '1'){
            $query->whereOr('a.status', 1);
        }
//            ->where(function ($query) {
//                $query->whereOr("IF (a.ms_id!=0,a.ms_id = {$this->agent->userid},(find_in_set( {$this->agent->userid}, pao_ms_ids )  or pao_ms_ids=''))");
//            });

        $listData = $query->paginate(15, false, ['query' => request()->param()]);
        $list = $listData->items();
        $count = $listData->count();
        $page = $listData->render();
        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('request', $request);
        //所有的支付渠道
       // $device = isMobile() ? 'index_mobile' : 'index';
        $device = 'index';
        return $this->fetch($device);
    }


    /**
     * 匹配订单
     */
    public function matching()
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('非法操作');
        }
        try {
            $transfer = $this->modelDaifuOrders->lock(true)->where(['id' => $id, 'status'=>1])->find();
            if (!$transfer) {
                throw new Exception('订单不存在或者订单已处理，请刷新！');
            }
            $transfer->status = 3;
            $transfer->ms_id = $this->agent_id;
            $transfer->save();
            $transfer->commit();
            $this->success('请求成功');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        /*$id = $this->request->param('id');
        if (!$id) {
            $this->error('非法操作');
        }
        try {
            $transfer = $this->modelDaifuOrders->lock(true)->where(['id' => $id])->find();
            if (!$transfer) {
                throw new \Exception('订单不存在');
            }
            if ($transfer->ms_id > 0 || $transfer->status != '1') {
                throw new Exception('订单已匹配');
            }
            $transfer->ms_id = $this->agent->userid;
            $transfer->matching_time = time();
            $transfer->save();
            $transfer->commit();
            $this->success('请求成功');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }*/
    }

    /**
     * 码商修改代付结果
     * @return mixed
     */
    public function sendDfResult()
    {
        $id = $this->request->param('id');

        //代付银行卡
        $df_bank_id = $this->request->param('df_bank_id');
        $status = $this->request->param('status');
        $error_reason = $this->request->param('error_reason', '');
        if (!$id || !in_array($status, [0, 2])) {
            $this->error('非法操作');
        }
        Db::startTrans();
        $DaifuOrdersLogic = new \app\common\logic\DaifuOrders();
        try {
            $orders = $this->modelDaifuOrders->lock(true)->where(['id' => $id])->find();
            if (!$orders) {
                throw new Exception('订单不存在');
            }
         /*   $bank = $this->modelDepositeCard->where(['id' => $df_bank_id, 'status' => 1])->find();
            if ($status == 2) {
                if (empty($bank)) {
                    throw new Exception('当前不存在或卡号被卡被禁用');
                }
                if ($orders['uid'] != $bank['uid']) {
                    throw new Exception('当前代付银行卡只能被商户ID:' . $bank['uid'] . '充值或代付');
                }
                if ($bank['ms_id'] != $this->agent_id) {
                    throw new Exception('当前代付卡是由码商ID:' . $bank['ms_id'] . '添加您的操作非法');
                }
            }*/

            if ($status == 2) {
                $result = $DaifuOrdersLogic->successOrder($orders['id']);
            } else {
                $result = $DaifuOrdersLogic->errorOrder($orders['id']);
            }
            //更新订单的代付银行卡
            $up['finish_time'] = time();
//            $up['df_bank_id'] = $df_bank_id;
            $up['error_reason'] = $error_reason;
            $res = $this->modelDaifuOrders->where(['id' => $id])->update($up);
            if ($res === false) {
                throw new Exception('代付失败');
            }


            //添加银行卡流水日志
            /*if ($status == 2) {
                $remark = "码商【ID:{$this->agent_id}】为代付订单【ID:{$id}】代付";
                $this->logicDepositeCard->addLogs($df_bank_id, $orders['amount'], 2, 2, $remark);
            }*/

            Db::commit();

            if ($result['code'] == 1) {
                $this->success('操作成功', url('index'));
            }
            $this->error('操作成功', url('index'));
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }


    /**
     * 获取当前码商对应的商户的最新订单
     */
    public function lastOrder(\app\common\logic\User $user)
    {
        $uids = $user->getUsersByMsId($this->agent_id);
        $uids = collection($uids)->column('uid');
        $lastOrderId = \app\common\model\DaifuOrders::where(['uid' => ['in', $uids]])->order(['id' => 'desc'])->value('id');
        echo $lastOrderId;
    }


    /**
     * 导出代付订单
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function exportDfOrder(Request $request)
    {
        $where = [];
        //当前时间段统计

        $startTime = $request->param('start_time');
        //dd($startTime);
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

        $status = $this->request->param('status', 1);
        $status != -1 && $where['a.status'] = ['eq', $status];

        !empty($this->request->param('trade_no')) && $where['trade_no']
            = ['eq', $this->request->param('trade_no')];
        !empty($this->request->param('out_trade_no')) && $where['out_trade_no']
            = ['eq', $this->request->param('out_trade_no')];
        //组合搜索
        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];


        $fields = ['a.*', 'b.pao_ms_ids', 'c.username', 'bank_account_username', 'bank_account_number', 'e.enable'];
        $query = $this->modelDaifuOrders->alias('a')
            ->join('user b', 'a.uid=b.uid', 'left')
            ->join('ms c', 'a.ms_id=c.userid', 'left')
            ->join('deposite_card d', 'a.df_bank_id=d.id', 'left')
            ->join('cm_balance e', 'a.uid=e.uid', 'left')
            ->field($fields)
            ->order('id desc')
            ->where($where)
            ->where(function ($query) {
                $query->whereOr("IF (a.ms_id!=0,a.ms_id = {$this->agent->userid},(find_in_set( {$this->agent->userid}, pao_ms_ids )  or pao_ms_ids=''))");
            });

        $listData = $query->select();

        //组装header 响应html为execl 感觉比PHPExcel类更快
        $orderStatus = ['处理失败', '待处理', '已完成'];


        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">ID标识</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">订单编号(商户)</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商户UID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商户余额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收款信息</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">付款信息</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">失败原因</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">创建时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">完成时间</td>';
        $strTable .= '</tr>';

        if ($listData) {
            foreach ($listData as $k => $val) {
                $skAccountInfo = '姓名:' . $val['bank_owner'] . ' 银行:' . $val['bank_name'] . ' 卡号:' . $val['bank_number'];
                $payAccountInfo = '---';
                if ($val['bank_id']) {
                    $payAccountInfo = '转账银行卡ID:' . $val['bank_id'] . ' 姓名:' . $val['bank_account_username'] . ' 卡号:' . $val['bank_account_number'];
                }
                $val['finish_time'] = $val['finish_time']?date("Y-m-d H:i:s",$val['finish_time']):'---';
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;' . $val['id'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['out_trade_no'] . ' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['uid'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['enable'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['amount'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $skAccountInfo . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $payAccountInfo . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $orderStatus[$val['status']] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['error_reason'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['create_time'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['finish_time'] . '</td>';
                $strTable .= '</tr>';
                unset($listData[$k]);
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, 'daifu_orders_execl');
    }


}
