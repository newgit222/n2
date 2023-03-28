<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/7
 * Time: 21:27
 */

namespace app\admin\controller;


use app\common\library\enum\CodeEnum;
use app\common\logic\TgLogic;

class DaifuOrders extends BaseAdmin
{

    /**
     * @return mixed
     * 代付订单列表
     */
    public function index(){
        $where['create_time'] = $this->parseRequestDate3();
        $orderCal  = $this->logicDaifuOrders->calOrdersData($where);
        $this->assign('fees',$orderCal);
        //所有的支付渠道
        return $this->fetch();
    }


    /**
     * 代付参数设置
     */
    public function setting(){
        $this->common();
        $this->assign('list', $this->logicConfig->getConfigList(['group'=> '3'],true,'sort ace'));
        return $this->fetch();
    }

    /**
     * Common
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    private function common(){
        $this->request->isPost() && $this->result(
            $this->logicConfig->settingSave(
                $this->request->post()
            )
        );
    }
    /**
     * 代付订单列表
     */
    public function getorderslist(){

        $where = [];
        //code
        //状态
        if ($this->request->param('status') != "") {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }
        !empty($this->request->param('orderNum')) && $where['a.trade_no']
            = ['eq', $this->request->param('orderNum')];

        !empty($this->request->param('username')) && $where['m.username']
            = ['eq', $this->request->param('username')];

        !empty($this->request->param('trade_no')) && $where['a.out_trade_no']
            = ['eq', $this->request->param('trade_no')];
        //组合搜索
        // !empty($this->request->param('trade_no')) && $where['trade_no']
        //   = ['eq', $this->request->param('trade_no')];

        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];



        //时间搜索  时间戳搜素
        $where['create_time'] = $this->parseRequestDate3();

    //    $data = $this->logicDaifuOrders->getOrderList($where, 'a.*, m.username', 'create_time desc',false);
//
//        $count = $this->logicDaifuOrders->getOrderCount($where);


       $data =  $this->modelDaifuOrders
           ->alias('a')
            ->where($where)
            ->join('cm_ms m', 'a.ms_id = m.userid', 'left')
            ->field('a.*, m.username')
            ->order('create_time desc')
            ->paginate($this->request->param('limit', 15));


        $this->result($data->items() ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>$data->total(),
                'data'=>$data->items()
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>$data->total(),
                'data'=>$data->items()
            ]);
    }


    /**
     * 审核成功
     */
    public function auditSuccess(){
        $this->result($this->logicDaifuOrders->successOrder($this->request->post('id')));
    }

    /**
     * 驳回
     */
    public function auditError(){
        $this->result($this->logicDaifuOrders->errorOrder($this->request->post('id'),0));
    }

    /**
     * 驳回
     */
    public function add_notify(){
        $this->result($this->logicDaifuOrders->retryNotify($this->request->post('id')));
    }



    /**
     * @return mixed
     * 订单详情
     */
    public function details()
    {
        $where['id'] = $this->request->param('id', '0');

        //订单
        $order = $this->logicDaifuOrders->getOrderInfo($where);

        $notify = [];
        //当支付成功的时候才会看有没有回调成功
//        if ($order['status'] == '2') {
//            //回调
//            $notify = $this->logicDaifuOrders->getOrderNotify(['order_id' => $where['id']]);
//        }

        $this->assign('order', $order);
//        $this->assign('notify', $notify);

        return $this->fetch();
    }


    /**
     * 查询订单金额
     * 按照简单的来
     */
    public function  searchOrderMoney(){

        //状态
        if ($this->request->param('status') != "") {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }

        !empty($this->request->param('orderNum')) && $where['a.trade_no']
            = ['eq', $this->request->param('orderNum')];
        !empty($this->request->param('trade_no')) && $where['a.out_trade_no']
            = ['eq', $this->request->param('trade_no')];
        //组合搜索
        // !empty($this->request->param('trade_no')) && $where['trade_no']
        //   = ['eq', $this->request->param('trade_no')];

        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];



        //时间搜索  时间戳搜素
        $where['a.create_time'] = $this->parseRequestDate3();

        $orderCal  = $this->logicDaifuOrders->getOrdersAllStat($where)['fees'];
//        echo json_encode($where);

        $orderCal['percent'] =  $orderCal['paid_count']==0?0: sprintf("%.2f",$orderCal['paid_count']/$orderCal['total_count'])*100;

        exit(json_encode($orderCal));
        // echo  sprintf('%.2f',$searchTotalOrderAmount['searchTotalOrderAmount']);
    }


    /**
     * 导出订单
     */
    public function  exportOrder(){
        //组合搜索
        $where = [];
        //code
        //状态
        if ($this->request->param('status') != "") {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }
        !empty($this->request->param('orderNum')) && $where['a.trade_no']
            = ['eq', $this->request->param('orderNum')];

        !empty($this->request->param('trade_no')) && $where['a.out_trade_no']
            = ['eq', $this->request->param('trade_no')];
        //组合搜索
        // !empty($this->request->param('trade_no')) && $where['trade_no']
        //   = ['eq', $this->request->param('trade_no')];

        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];



        //时间搜索  时间戳搜素
        $where['a.create_time'] = $this->parseRequestDate3();
        //导出默认为选择项所有
        //$orderList = $this->logicDaifuOrders->getOrderList($where,true, 'create_time desc', false);
        $orderList = $this->modelDaifuOrders->alias('a')->where($where)->field('a.*,m.username')->join('ms m', 'a.ms_id = m.userid', 'left')->select();

        //组装header 响应html为execl 感觉比PHPExcel类更快
        $orderStatus =['订单关闭','等待支付','支付完成','处理中','异常订单'];
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">ID标识</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易商户</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">打款单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易手续费</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易方式</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收款姓名</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收款账号</td>';

        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">创建时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">更新时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">卡商名称</td>';

//        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">码商号</td>';
//        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付渠道</td>';


        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['uid'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['out_trade_no'].' </td>';
//                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['amount'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'. bcadd($val['service_charge'],$val['single_service_charge'], 2).'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">代付</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'. $val['bank_owner'] .'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'. $val['bank_number'] .'</td>';

                $strTable .= '<td style="text-align:left;font-size:12px;">'.$orderStatus[$val['status']].'</td>';

//                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['channel'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['update_time'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['username'].'</td>';
                $strTable .= '</tr>';
                unset($orderList[$k]);
            }
        }
        $strTable .='</table>';
        downloadExcel($strTable,'daifu_orders');
    }

    //充值申请列表
    public function applyList()
    {
        $where = [];
        //code
        //状态
        if ($this->request->param('status') != "") {
            $where['status'] = ['eq', $this->request->param('status')];
        }
        !empty($this->request->param('orderNum')) && $where['trade_no']
            = ['eq', $this->request->param('orderNum')];

        //按照商户号搜索
        if ($this->request->param('uid') != "") {
            $where['uid'] = ['eq', $this->request->param('uid')];
        }

        //时间搜索  时间戳搜素
        $where['create_time'] = $this->parseRequestDate3();

        $data = $this->logicDepositOrders->getOrderList($where, true, 'create_time desc',false);

        $count = $this->logicDepositOrders->getOrderCount($where);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>$count,
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>$count,
                'data'=>$data
            ]);
    }

    //驳回申请
    public function rejectApply($id)
    {
        $this->result($this->logicDepositeOrder->delDepositeCard(['id' => $id]));
        //1.设置申请充值订单状态为失败状态
    }

    //完成申请
    public function acceptApply($id)
    {
        $this->result($this->logicDepositeOrder->acceptApply(['id' => $id]));

    }

    /**
     * @return mixed
     *  充值银行卡首页
     */
    public function depositeCard()
    {
        return $this->fetch();
    }

    /**
     * 获取充值银行卡列表
     */
    public function getDepositeCardList(){
        $where = [];
        if ($this->request->param('status') != "") {
            $where['status'] = ['eq', $this->request->param('status')];
        }
        !empty($this->request->param('bank_account_username')) && $where['bank_account_username']
            = ['like', '%'.$this->request->param('bank_account_username').'%'];

        !empty($this->request->param('bank_account_number')) && $where['bank_account_number']
            = ['like', '%'.$this->request->param('bank_account_number').'%'];
        $fields = 'a.*,b.name';
        $data = $this->logicDepositeCard->getCardList($where, $fields, 'create_time desc',false);
        $count = $this->logicDepositeCard->getCardCount($where);
        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>$count,
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>$count,
                'data'=>$data
            ]);
    }



    //添加充值银行卡
    public function addDepositeCard()
    {
        $this->request->isPost() && $this->result($this->logicDepositeCard->saveCard($this->request->post(),'add'));
        $this->assign('bank',$this->logicBanker->getBankerList());
        return $this->fetch();
    }

    //编辑充值银行卡
    public function editDepositeCard()
    {

        $this->request->isPost() && $this->result($this->logicDepositeCard->saveCard($this->request->post(),'edit'));
        $this->assign('bank',$this->logicBanker->getBankerList());
        $this->assign('info',$this->logicDepositeCard->getCard($this->request->param('id')));
        return $this->fetch();
    }

    //删除充值银行卡
    public function delDepositeCard()
    {
        $this->result($this->logicDepositeCard->delCard(['id' => $this->request->param('id')]));
    }

    /**
     * 指定码商
     */
    public function appoint_ms()
    {
        $id = $this->request->param('id', '');
        if ($this->request->isAjax()){
            $this->modelDaifuOrders->startTrans();
            $ms_id = $this->request->param('ms_id');
            $order = $this->modelDaifuOrders->lock(true)->where(['id' => $id, 'status' =>  1])->find();
            if ($order['ms_id'])
            {
                $this->error('该订单已分配码商！');
            }
            if (!$order){
                $this->error('订单不存在！');
            }
            $ms = $this->modelMs->where('userid', $ms_id)->find();
            if (!$ms) {
                $this->error('码商不存在！');
            }
            $order->ms_id = $ms_id;
            $order->status = 3;
            $order->save();
            $this->modelDaifuOrders->commit();

            $Ms =  $this->modelMs->where('userid', $ms_id)->find();
            if ($Ms && $Ms['tg_group_id']){
                //发送信息给渠道飞机群
                $TgLogic = new TgLogic(['tg_bot_type' => 'df']);
                //文本信息
		$sendText1 = '金额：' . intval($order['amount']).PHP_EOL.PHP_EOL.
			     '姓名：' . $order['bank_owner'] . PHP_EOL .
                             '卡号：' . $order['bank_number'] . PHP_EOL .
                             '银行：' . $order['bank_name'] . PHP_EOL ;
                $sendText2 = '请按照金额打款，不要多打和重复打款，否则无法追回，打款完麻烦发转账成功截图在群里，谢谢—';
                $TgLogic->sendMessageTogroup($sendText1, $Ms['tg_group_id']);
                $TgLogic->sendMessageTogroup($sendText2, $Ms['tg_group_id']);
            }

            $this->success('操作成功');
        }
        $yhhMs =  $this->modelDaifuOrders->getYhkMs();
        $this->assign('yhkMs', $yhhMs);
        $this->assign('id', $id);
        return $this->fetch();
    }
}
