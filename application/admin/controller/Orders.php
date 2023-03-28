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
use app\common\library\enum\OrderStatusEnum;
use think\Db;
use think\Exception;
use think\migration\command\seed\Run;

class Orders extends BaseAdmin
{


  public function checktest(){
//下面为验证参数
      require_once EXTEND_PATH.'PHPGangsta/GoogleAuthenticator.php';

      $ga = new \PHPGangsta_GoogleAuthenticator();
      //$code = $_GET['code'];//客户提交上来的谷歌验证APP里面对应的验证码
      $code = '648050';
//该用户绑定谷歌验证生成的唯一秘钥
      $secret = '2GTERTQPQBVLLKKC';

//验证用户提交的验证码是否正确
      $checkResult = $ga->verifyCode($secret, $code, 1);
      var_dump($checkResult);


  }


    public function  test(){
        require_once EXTEND_PATH.'PHPGangsta/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();

        $secret = $ga->createSecret();
//        echo $secret;exit;

        //cho "Secret is: ".$secret."\n\n";exit;

        $qrCodeUrl = $ga->getQRCodeGoogleUrl('lijianyun', $secret);
//        echo $qrCodeUrl;exit;
        echo "<img src='$qrCodeUrl'>";exit;
        dd($qrCodeUrl);

        echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n";

        $oneCode = $ga->getCode($secret);
        echo "Checking Code '$oneCode' and Secret '$secret':\n";

        $checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
        if ($checkResult) {
            echo 'OK';
        } else {
            echo 'FAILED';
        }
    }


    protected  function minitesOrderAcount($where)
    {

        $join = [
            ['pay_account b', 'a.cnl_id = b.id','left'],
            ['pay_channel c', 'b.cnl_id = c.id','left'],
        ];
        //todo need repear
//        unset($where);
        $result = [];
        $where['a.create_time'] = ['between',[time()-600,time()]];
        //十分钟订单数量
        $result['total_num'] = $this->modelOrders->where($where)->alias('a')->join($join)->count();
        //平均订单数量
        $result['average_num'] = $result['total_num']/10;
        //十分钟成功订单数量
        $where['a.status'] = '2';

        $result['success_num'] = $this->modelOrders->where($where)->alias('a')->join($join)->count();
        $result['success_rate'] =  $result['success_num']==0?0: sprintf("%.2f",$result['success_num']/$result['total_num'])*100;
        return $result;


//        $where['create_time'] = ['between',[time()-600,time()]];
//        return $this->modelOrders->where($where)->count();
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

        !empty($this->request->param('channel')) && $where['a.channel']
            = ['eq', $this->request->param('channel')];

        !empty($this->request->param('cnl_id')) && $where['c.id']
            = ['eq', $this->request->param('cnl_id')];

        //时间搜索  时间戳搜素
        $parseTime =  $this->parseRequestDate3();
        $where['a.create_time'] =$parseTime;
        $orderCal  = $this->logicOrders->getOrdersAllStat($where)['fees'];
        //符合条件的10分钟前订单统计
        $orderCal['pre_minis_order_count'] = $this->minitesOrderAcount($where);

        $orderCal['percent'] =  $orderCal['paid_count']==0?0: sprintf("%0.2f",$orderCal['paid_count']/$orderCal['total_count'])*100; 
        exit(json_encode($orderCal));


       // echo  sprintf('%.2f',$searchTotalOrderAmount['searchTotalOrderAmount']);
    }


    /**
     * 订单列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function index()
    {
        $parseTime =  $this->parseRequestDate3();
        $where['a.create_time'] = $parseTime;
        $orderCal  = $this->logicOrders->getOrdersAllStat($where);

        //符合条件的10分钟前订单统计
        $this->assign('orderCalMinites', $this->minitesOrderAcount($where));
        $this->assign($orderCal);
        $this->assign('code', $this->logicPay->getCodeList());
        $this->assign('channels', $this->logicPay->getChannelList([],true,'create_time desc',100));
        //单独高出成功率
        $percent =  $orderCal['fees']['paid_count']==0?0: sprintf("%.2f",$orderCal['fees']['paid_count']/$orderCal['fees']['total_count'])*100;
        $this->assign('percent', $percent);
        //所有的支付渠道
        return $this->fetch();
    }

    //修改订单状态
    public function update()
    {
        $where['id'] = ['eq', $this->request->param('id')];
        $orderModel = model('orders');

        $result = $orderModel->where($where)->find();
        if (!empty($result) && $result['status'] == '1'  ) {
            $model = new \app\api\logic\Notify();
            $model->updateOrderInfo($result, 2);
            $OrdersNotify = new  \app\common\logic\OrdersNotify();
            $logicQueue = new  \app\common\logic\Queue();
            $bdRemarks = htmlspecialchars($this->request->param('bd_remarks'));
            if(empty($bdRemarks))
            {
                    return ['code' => CodeEnum::ERROR, 'msg' => '必须填写备注' ];
            }
            $OrdersNotify->saveOrderNotify($result);
            $logicQueue->pushJobDataToQueue('AutoOrderNotify' , $result , 'AutoOrderNotify');
            //单独修改补单备注(编辑封闭新增放开原则)todo 此处后期事务处理最好
            $orderModel->where($where)->setField('bd_remarks', $bdRemarks);
        }
        $this->result(!empty($result) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
            ]
        );
    }

    /**
     * 交易列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getList()
    {

        //状态
        if ($this->request->param('status') != "") {
            $where['a.status'] = ['eq', $this->request->param('status')];
        }

        if ($this->request->param('is_status') != "") {
            $is_status = $this->request->param('is_status');
            if($is_status =='0'){
                $where['e.is_status'] = ['neq', '200'];
            }elseif($is_status == '-1') {
                $where['e.is_status'] = ['eq', ' '];
            }else{
                $where['e.is_status'] = ['eq', $this->request->param('is_status')];
            }
        }

        !empty($this->request->param('orderNum')) && $where['trade_no']
            = ['eq', $this->request->param('orderNum')];
        !empty($this->request->param('trade_no')) && $where['out_trade_no']
            = ['eq', $this->request->param('trade_no')];
        //组合搜索
        // !empty($this->request->param('trade_no')) && $where['trade_no']
        //   = ['eq', $this->request->param('trade_no')];

        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];

        !empty($this->request->param('channel')) && $where['channel']
            = ['eq', $this->request->param('channel')];
        !empty($this->request->param('cnl_id')) && $where['c.id']
            = ['eq', $this->request->param('cnl_id')];

        //时间搜索  时间戳搜素
        $where['a.update_time'] = $this->parseRequestDate3();
        $filds  = 'a.*,c.name as channel_name,d.name as pay_code_name,e.result,e.is_status,e.id as eid';
//        echo json_encode($where);
        $data = $this->logicOrders->getOrderList3($where, $filds, 'a.update_time desc', false);
        $numberList = [];
        foreach ($data as $k=>$d) {
            $numberList[] = $d['trade_no'];


            //加一个回调通知状态
            //当支付成功的时候才会看有没有回调成功
//            if ($d['status'] == '2') {
//                //回调
//                $data[$k]['notify'] = $this->logicOrders->getOrderNotify(['order_id' => $d['id']]);
//            }else{
//                $data[$k]['notify'] = ['is_status'=>'-1','result'=>'WAIT'];
//            }
//            //交易去掉
//            $accountInfo =  $this->logicPay->getAccountInfo(['id'=>$d['cnl_id']]);
//            $channelInfo  =  $this->logicPay->getChannelInfo(['id'=>$accountInfo['cnl_id']]);
//            $data[$k]['channe_name'] = $channelInfo['name'];
        }

        $sql = "SELECT out_trade_no,orderNum FROM cm_ownpay_order WHERE out_trade_no in('" . join("','", $numberList) . "')";

        $taobaolist = Db::query($sql);
        //print_R($taobaolist);exit;
        $taobaolist = empty($taobaolist) ? array() : array_column($taobaolist, 'orderNum', 'out_trade_no');
        //获取淘宝单号
        if (!empty($data)) {

            foreach ($data as $key => $row) {
                if(!empty($row['visite_time']))
                {
                    $data[$key]['visite_time'] = date("Y-m-d H:i:s", $row['visite_time']);
                }
                else
                {
                    $data[$key]['visite_time'] = "<i style='color:red;'>未访问</i>";
                }



                if (isset($taobaolist[$row['trade_no']])) {
                    $data[$key]['orderNum'] = $taobaolist[$row['trade_no']];

                } else {
                    $data[$key]['orderNum'] = "";

                }
            }

        }

        $count = $this->logicOrders->getOrdersCount3($where);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => $count,
                'data' => $data,
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => $count,
                'data' => $data
            ]
        );
    }

    /**
     * 获取详情
     * 1.基本
     * 2.回调
     * 3.商户
     * 4.结算
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function details()
    {
        $where['id'] = $this->request->param('id', '0');

        //订单
        $order = $this->logicOrders->getOrderInfo($where);

        $notify = [];
        //当支付成功的时候才会看有没有回调成功
        if ($order['status'] == '2') {
            //回调
            $notify = $this->logicOrders->getOrderNotify(['order_id' => $where['id']]);
        }

        $this->assign('order', $order);
        $this->assign('notify', $notify);

        return $this->fetch();
    }

    /**
     * 后台管理员补单页面操作
     * @return mixed
     */
    public function budanDetails()
    {
        $where['id'] = $this->request->param('id', '0');

        //订单
        $order = $this->logicOrders->getOrderInfo($where);

        $notify = [];
        //当支付成功的时候才会看有没有回调成功
        if ($order['status'] == '2') {
            //回调
            $notify = $this->logicOrders->getOrderNotify(['order_id' => $where['id']]);
        }

        $this->assign('order', $order);
        $this->assign('notify', $notify);

        return $this->fetch();
    }

    /**
     * 退款列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function refund()
    {
        $where = [];
        //组合搜索
        !empty($this->request->param('keywords')) && $where['trade_no|out_trade_no|uid|id']
            = ['like', '%' . $this->request->param('keywords') . '%'];

        !empty($this->request->param('channel')) && $where['channel']
            = ['eq', $this->request->param('channel')];

        !empty($this->request->param('status')) && $where['status']
            = ['eq', $this->request->param('status')];

        $this->assign('list', $this->logicOrders->getOrderList($where));

        return $this->fetch();
    }


    /**
     * 商户订单统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function user()
    {
        return $this->fetch();
    }

    /*
     *导出商户统计数据
     *
     */
    public function  exportUserCal()
    {
//        $where = [];
//        //组合搜索
//        !empty($this->request->param('uid')) && $where['uid']
//            = ['eq', $this->request->param('uid')];
//
//        //时间搜索  时间戳搜素
//        $datime = $this->parseRequestDate3();
//        $where['a.create_time'] =$datime;
//
//        $data = $this->logicOrders->getOrderUserStat($where);


        $where = [];
        //组合搜索
        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];

        //时间搜索  时间戳搜素
        $where['a.create_time'] = $this->parseRequestDate3();
        $data = $this->logicOrders->getOrderUserStat($where);
        $data = $data ? collection($data)->toArray() : [];

        foreach ($data as $k => $v) {
            $percent = $v['total_paid_count'] == 0 ? 0 : sprintf("%.2f", $v['total_paid_count'] / $v['total_orders']) * 100;
            $data[$k]['percent'] = $percent . '%';
        }

        if(empty($this->request->param('uid')))
        {
            $noOrderUsers =  $this->modelUser->field('uid,username')->where(['uid'=>['notin',array_column($data,'uid'),'']])->order(['uid'=>'asc'])->select();

            foreach($noOrderUsers as $user)
            {
                $row['uid'] = $user['uid'];
                $row['total_fee_all'] = '0.00';
                $row['total_fee_dis'] = '0.00';
                $row['total_fee_paid'] = '0.00';
                $row['total_plat_fee'] = '0.00';
                $row['total_orders'] = 0;
                $row['total_paid_count'] = 0;
                $row['username'] = $user['username'];
                $row['percent'] = '0%';
                $data[]=$row;
            }

        }

        //组装header 响应html为execl 感觉比PHPExcel类更快
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">商户UID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">商户名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单总数</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">完成订单数</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易总额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">成交总额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">平台收入总额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">成功率</td>';
        $strTable .= '</tr>';
        if(is_array($data)){
            foreach($data as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['uid'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['username'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_orders'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_paid_count'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_fee_all'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_fee_paid'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_plat_fee'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['percent'].'</td>';
                $strTable .= '</tr>';
                unset($data[$k]);
            }
        }
        $strTable .='</table>';
        downloadExcel($strTable,'userCal');

    }



    /**
     * 商户交易统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function userList()
    {
        $where = [];
        //组合搜索
        !empty($this->request->param('uid')) && $where['a.uid']
            = ['eq', $this->request->param('uid')];

        //时间搜索  时间戳搜素
        $where['a.update_time'] = $this->parseRequestDate3();
        $where['b.status'] =['neq',-1];
        $data = $this->logicOrders->getOrderUserStat($where);
        $data = $data ? collection($data)->toArray() : [];

        foreach ($data as $k => $v) {
            $percent = $v['total_paid_count'] == 0 ? 0 : sprintf("%.2f", $v['total_paid_count'] / $v['total_orders']) * 100;
            $data[$k]['percent'] = $percent . '%';
        }

        if(empty($this->request->param('uid')))
        {
            $noOrderUsers =  $this->modelUser->field('uid,username')->where(['status'=>['neq',-1],'uid'=>['notin',array_column($data,'uid'),'']])->order(['uid'=>'asc'])->select();
            $i =1;
            foreach($noOrderUsers as $user)
            {
                $row['uid'] = $user['uid'];
                $row['total_fee_all'] = '0.00';
                $row['total_fee_dis'] = '0.00';
                $row['total_fee_paid'] = '0.00';
                $row['total_plat_fee'] = '0.00';
                $row['total_orders'] = 0;
                $row['total_paid_count'] = 0;
                $row['username'] = $user['username'];
                $row['percent'] = '0%';
                $data[]=$row;
            }

        }

        //$this->modelUser->where([''])


//        foreach($data as $k=>$v)
//        {
//            //获取用户每个paycode的完成订单和总订单数量--用户在此pay_code已经有订单
//             $channel_cal =  $this->logicOrders->getOrderUserStatByPayCode(['uid'=>$v['uid']]);
//             $channel_cal_desc= [];
//             if($channel_cal)
//             {
//                  foreach ($channel_cal as $k1=>$v1)
//                  {
//                      $channel_cal_desc[]= "【支付产品：{$v1['pay_code_name']},总订单数：{$v1['channel_count']},完成订单数：{$v1['total_paid_channel_count']}】";
//                  }
//             }
//             //金额统计
//            $data[$k]['money_cal'] = $v['total_fee_all'].'/'. $v['total_fee_paid'];
//            $data[$k]['channel_cal'] = implode('|',$channel_cal_desc);
//        }

        //$this->result($data || !empty($data) ? [CodeEnum::SUCCESS,'',$data] : [CodeEnum::ERROR,'暂无数据','']);
        $count = count($data);

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
     * 商户渠道统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function channel()
    {
        $this->assign('channel', $this->logicPay->getChannelList());
        return $this->fetch();
    }

    /**
     * 商户渠道统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function channelList()
    {
        $where = [];
        //组合搜索
        !empty($this->request->param('cnl_id')) && $where['a.cnl_id']
            = ['eq', $this->request->param('cnl_id')];

        //时间搜索  时间戳搜素
        $where['a.create_time'] = $this->parseRequestDate3();

        $data = $this->logicOrders->getOrderChannelStat($where);
        if(is_array($data) && !empty($data))
        {

            foreach($data as $k=>$v)
            {
                $success_percent = ($v['paid_orders'] == 0)?0:
                    sprintf("%.2f",$v['paid_orders']/$v['total_orders'],2)*100;
                $data[$k]['success_percent'] = $success_percent.'%';
            }

        }

        $count = count($data);

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
     * 这里还是写入队列
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     */
//    public function subnotify()
//    {
//        $this->result($this->logicOrders->pushOrderNotify($this->request->param('order_id')));
//    }

    public function subnotify()
    {
        $this->result($this->logicOrders->pushOrderNotifyV2($this->request->param('order_id')));
    }

    /**
     * 导出订单
     */
    public function  exportOrder(){
       //  set_time_limit(0);
       // ini_set('max_execution_time', '5000');
       // ini_set('memory_limit', '4096M');
        //组合搜索
        !empty($this->request->get('trade_no')) && $where['out_trade_no']
            = ['like', '%'.$this->request->get('trade_no').'%'];

        !empty($this->request->get('channel')) && $where['channel']
            = ['eq', $this->request->get('channel')];

        //时间搜索  时间戳搜素
        $date['start'] = $this->request->param('start');
        $date['end'] = $this->request->param('end');
        $start = empty($date['start']) ? date('Y-m-d',time()) : $date['start'];
        $end = empty($date['end']) ? date('Y-m-d',time()+3600*24) : $date['end'];
        $where['a.update_time'] = ['between', [strtotime($start), strtotime($end)]];

        //状态
        if(!empty($this->request->get('status')) || $this->request->get('status') === '0')
        {
            $where['a.status'] = $this->request->get('status');
        }
        //导出默认为选择项所有
        $filds  = 'a.*,c.name as channel_name,d.name as pay_code_name';
        $orderList = $this->logicOrders->getOrderList2($where, $filds, 'a.update_time desc', false);
///var_dump(1);die();
        //组装header 响应html为execl 感觉比PHPExcel类更快
        $orderStatus =['订单关闭','等待支付','支付完成','异常订单'];
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">ID标识</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">商户号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收入</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">平台收入</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">上游订单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">上游支付渠道</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">创建时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">更新时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['uid'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;' . $val['out_trade_no'] . ' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['amount'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_in'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['platform_in'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;' . $val['trade_no'] . ' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['channel_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['update_time'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$orderStatus[$val['status']].'</td>';
                $strTable .= '</tr>';
                unset($orderList[$k]);
            }
        }
        $strTable .='</table>';
        downloadExcel($strTable,'order');
    }



    /**
     * 商户渠道统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function channelV2()
    {
        $this->assign('channel', $this->logicPay->getChannelList());
        return $this->fetch();
    }

    /**
     * 商户渠道统计
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function channelStatV2()
    {
        $payCode = $this->request->param('pay_code', 'h5_zfb');
        $minite = $this->request->param('minite', '15');
        $data = $this->logicOrders->getOrderChannelStatV2($payCode, $minite);
        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg' => '',
                'count' => 10,
                'data' => $data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg' => '暂无数据',
                'count' => 10,
                'data' => $data
            ]);
    }

}
