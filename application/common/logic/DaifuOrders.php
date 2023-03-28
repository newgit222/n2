<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/7
 * Time: 21:54
 */

namespace app\common\logic;


use think\Db;
use think\Exception;
use think\migration\command\seed\Run;

class DaifuOrders extends BaseLogic
{


    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setParam();
    }

    /**
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     * 获取订单列表
     */
    public function getOrderList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelDaifuOrders->limit = !$paginate;
        return $this->modelDaifuOrders->getList($where, $field, $order, $paginate);
    }

    /**
     * @param array $where
     * @return mixed
     * 获取订单总数
     */
    public function getOrderCount($where = [])
    {
        return $this->modelDaifuOrders->getCount($where);
    }


    /*
     * 统计订单相关数据
     *
     * @param array $where
     */
    public function calOrdersData($where = [])
    {
        //订单总金额
        $data['total_money'] = $this->modelDaifuOrders->where($where)->value('sum(amount) as total_mount');
        //订单总订单数量
        $data['total_count'] = $this->modelDaifuOrders->where($where)->count('id');
        //订单完成金额
        $where['status'] = 2;
        $data['total_finish_money'] = $this->modelDaifuOrders->where($where)->value('sum(amount) as total_mount');
        //完成订单数量
        $data['total_finish_count'] = $this->modelDaifuOrders->where($where)->count('id');
        //成功率
        if ($data['total_finish_count'] == 0 || $data['total_count'] == 0) {
            $success_percent = '0.00';
        } else {
            $success_percent = sprintf("%.2f", $data['total_finish_count'] / $data['total_count']);
        }
        $data['success_percent'] = $success_percent;
        return $data;
    }


    /**
     * @param array $where
     * @param bool $field
     * @return mixed
     *  获取订单信息
     */
    public function getOrderInfo($where = [], $field = true)
    {
        return $this->modelDaifuOrders->getInfo($where, $field);
    }

    /**
     * 订单统计
     *
     * @param array $where
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrdersAllStat($where = [])
    {
//        var_dump($where);
        $this->modelDaifuOrders->alias('a');
        return [
            'fees' => $this->modelDaifuOrders->getInfo($where, "COALESCE(count(a.id),0) as total_count,COALESCE(sum(a.amount),0) as total,count(case when a.`status`=2 then 1 else null end) as paid_count,COALESCE(sum(if(a.status=2,amount,0)),0) as paid")
        ];
    }

    /**
     * 订单审核成功
     */
    public function successOrder($id)
    {
        return $this->saveOrder($id, true);
    }

    /**
     * 订单驳回
     */
    public function errorOrder($id)
    {
        return $this->saveOrder($id, false);
    }


    /**
     * 代付成功 减少冻结余额
     */
    public function successChanges($order)
    {
        //代付成功  冻结金额减少
        $result = $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'] + $order['service_charge'] + $order['single_service_charge'], '代付订单' . $order['out_trade_no'] . '成功,冻结金额减少', 'disable', true);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * 驳回订单 返还余额，冻结金额减少
     */
    public function errorChanges($order)
    {
        //代付成功  冻结金额减少
        $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'] + $order['service_charge'] + $order['single_service_charge'], '代付订单' . $order['out_trade_no'] . '失败,返还余额', 'enable');
        $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'] + $order['service_charge'] + $order['single_service_charge'], '代付订单' . $order['out_trade_no'] . '失败,冻结金额减少', 'disable', true);
        return true;
    }


    /**
     * @param $amount
     * @param $uid
     * @return array
     *  获取手续费金额
     */
    public function getServiceCharge($amount, $uid)
    {
        $result = [
            'service_charge' => '0',
            'single_service_charge' => '0',
        ];
        //手续费
        $daifu_profit = $this->modelUserDaifuprofit->getInfo(['uid' => $uid]);
        if ($daifu_profit) {
            //单笔手续费+费率
            $result['service_charge'] = $daifu_profit['service_charge'];
            $result['single_service_charge'] = sprintf("%.2f", $daifu_profit['service_rate'] * $amount);
        }
//        $balance = $this->logicBalance->getBalanceInfo(['uid' => $uid]);
        $balance = $this->modelBalance->lock(true)->where(['uid' => $uid])->find();
        if (!$balance) {
            return ['code' => '0', 'msg' => '余额不足'];
        }

        if ($balance['enable'] < ($result['service_charge'] + $result['single_service_charge'] + $amount)) {
            return ['code' => '0', 'msg' => '余额不足'];
        }
        return ['code' => '1', 'msg' => '请求成功', 'data' => $result];
    }


    /**
     * 创建订单用户用户余额冻结
     */
    public function createChange($order)
    {

        //余额+手续费冻结
        $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'] + $order['service_charge'] + $order['single_service_charge'], '代付订单' . $order['out_trade_no'] . '下单成功,冻结金额增加', 'disable');
        $this->logicBalanceChange->creatBalanceChange($order['uid'], $order['amount'] + $order['service_charge'] + $order['single_service_charge'], '代付订单' . $order['out_trade_no'] . '下单成功,余额减少', 'enable', true);
        return true;
    }


    /**
     * 处理回调数据
     */
    public function disposeNotifyData($data)
    {
        if (!isset($data['agent_order_no'])) {
            return ['code' => '0', 'msg' => '回调数据错误'];
        }
        $orders = $this->modelDaifuOrders->where(['trade_no' => $data['agent_order_no']])->find();
        if (!$orders) {
            return ['code' => '0', 'msg' => '订单不存在' . $data['agent_order_no']];
        }
        return ['code' => '1', 'msg' => '请求成功', 'data' => $orders];
    }


    /**
     * 订单状态修改
     */
    public function saveOrder($id, $status)
    {
        if (!$id) {
            return ['code' => '0', 'msg' => '非法操作'];
	}
	 $this->modelDaifuOrders->startTrans();
        $order = $this->modelDaifuOrders->where(['id' => $id, 'status' => 3])->lock(true)->find();
        if (!$order) {
            return ['code' => '0', 'msg' => '订单不存在'];
        }

       if($order['status']==2 || $order['status']==0)
       {
          return ['code' => '0', 'msg' => '订单已经完成'];
       }
       // $this->modelDaifuOrders->startTrans();
        try {

            //添加码商余额
            if ($order['ms_id']&& $status){
                //accountLog($order['ms_id'], MsMoneyType::DAIFU_ORDER_SUCCESS, 1, $order['amount'], '代付订单'. $order['trade_no']. '完成');
                accountLog($order['ms_id'], MsMoneyType::DAIFU_ORDER_SUCCESS, 1, $order['amount'],  $order['trade_no']);
            }


            $save = [
                'status' => $status ? '2' : '0',
                'update_time' => time(),
            ];
            if ($status) {
                if (!$this->successChanges($order)) {
                    throw new Exception('用户余额变动失败');
                }
                $save['notify_result'] = $this->sendNotify($order, true);
            } else {
                if (!$this->errorChanges($order)) {
                    throw new Exception('用户余额变动失败');
                }
                $save['notify_result'] = $this->sendNotify($order, false);
            }
            $order->save($save);
            $order->commit();

        } catch (Exception $e) {
            $this->modelDaifuOrders->rollback();
            return ['code' => '0', 'msg' => $e->getMessage()];
        }
        return ['code' => '1', 'msg' => '审核成功'];
    }


    /**
     * 补发回调
     */
    public function retryNotify($id)
    {
        if (!$id) {
            return ['code' => '0', 'msg' => '非法操作'];
        }
        $order = $this->modelDaifuOrders->where(['id' => $id])->find();
        if (!$order) {
            return ['code' => '0', 'msg' => '订单不存在'];
        }
        $notify_result = $this->sendNotify($order, true);
        $result = $order->update(['notify_result' => $notify_result], ['id' => $id]);
        if (!$result) {
            return ['code' => '0', 'msg' => '保存失败'];
        }
        if ($notify_result != 'SUCCESS') {
            return ['code' => '0', 'msg' => '已补发，回调失败'];
        }
        return ['code' => '1', 'msg' => '回调成功'];
    }


    /**
     * 执行回调
     */
    public function sendNotify($order, $status = true)
    {
        //执行商户回调
        $data = [
            'code' => $status ? '1' : '0',
            'msg' => $status ? 'success' : 'fail',
            'out_trade_no' => $order['out_trade_no'],
            'trade_no' => $order['trade_no'],
            'amount' => $order['amount']
        ];
        $agent = $this->checkAgent($order['uid']);
        $res = '';
        if ($agent) {
            $data['sign'] = $this->getSign($data, $agent->key);
            //下级回调
            $res = $this->curl_post($order['notify_url'], json_encode($data));
            \think\Log::notice("df下级回调 notify url " . $order['notify_url'] . "data" . json_encode($data));
            if ($res != 'SUCCESS') {
                $res = 'ERROR';
            }
        } else {
            \think\Log::error("df没有下级回调 notify url " . $order['notify_url'] . "data" . json_encode($data));
        }
        return $res;
    }


    /**
     * 验证
     */
    public
    function checkParams($params, $is_sign)
    {


        //判断代付是否开启
        $whether_open_daifu = \app\common\model\Config::where(['name' => 'whether_open_daifu'])->find()->toArray();
        if ($whether_open_daifu) {
            if ($whether_open_daifu['value'] != '1') {
                return ['code' => '0', 'msg' => '代付未开启'];
            }
        }
        //验证商户号
        $agent = $this->checkAgent($params['mchid']);
        if (!$agent) {
            return ['code' => '0', 'msg' => '商户号错误'];
        }

        //验证该商户是否指定码商
        $mch = $this->logicUser->getUserInfo(['uid' => $params['mchid']]);
        /*$yhkCodeMs = $this->modelDaifuOrders->getRandMs();
        if (empty($mch['pao_ms_ids']) &&  is_null($yhkCodeMs)) {
            return ['code' => '0', 'msg' => '系统未设置代付码商'];
        }*/

        if ($is_sign) {
            //验证sign
            $sign = $this->getSign($params, $agent->key);
            if ($sign != $params['sign']) {
                return ['code' => '0', 'msg' => 'sign错误'];
            }
        }
        //验证订单号是否重复
        $result = $this->modelDaifuOrders->checkOutTradeNo($params['mchid'], $params['out_trade_no']);
        if ($result) {
            return ['code' => '0', 'msg' => '商户订单号重复'];
        }

        //验证代付金额，最大最小设置
        $minAmount = $this->modelConfig->where(['name' => 'daifu_min_amount'])->value('value');
        $maxAmount = $this->modelConfig->where(['name' => 'daifu_max_amount'])->value('value');
        if (is_numeric($minAmount) && $minAmount > 0){
            if (bccomp($params['amount'], $minAmount) == -1){
                return ['code' => '0', 'msg' => '代付金额限制最小'. $minAmount];
            }
        }
        if (is_numeric($maxAmount) && $maxAmount>0){
            if (bccomp($params['amount'], $maxAmount) == 1){
                return ['code' => '0', 'msg' => '代付金额限制最大'. $maxAmount];
            }
        }

        //验证银行编码
        $banker = $this->logicBanker->getBankerInfo(['bank_code' => $params['bank_code']]);
        if (!$banker) {
            return ['code' => '0', 'msg' => '银行编码不支持'];
        }

        $params['bank_id'] = $banker['id'];
        $params['bank_name'] = $banker['name'];
        $params['pao_ms_ids'] = $mch['pao_ms_ids'];
        $params['mch_name'] = $mch['username'];

        return ['code' => '1', 'msg' => '请求成功', 'data' => $params];
    }

    /**
     * 订单查询接口
     */
    public
    function queryOrder($params)
    {
        //判断代付是否开启
        $whether_open_daifu = \app\common\model\Config::where(['name' => 'whether_open_daifu'])->find()->toArray();
        if ($whether_open_daifu) {
            if ($whether_open_daifu['value'] != '1') {
                return ['code' => '0', 'msg' => '代付未开启'];
            }
        }
        //验证商户号
        $agent = $this->checkAgent($params['mchid']);
        if (!$agent) {
            return ['code' => '0', 'msg' => '商户号错误'];
        }
        //验证sign
        $sign = $this->getSign($params, $agent->key);
        if ($sign != $params['sign']) {
            return ['code' => '0', 'msg' => 'sign错误'];
        }

        $order = $this->modelDaifuOrders->getInfo(['out_trade_no' => $params['out_trade_no']]);
        if (!$order) {
            return ['code' => '0', 'msg' => '订单不存在'];
        }
        $result = [
            'status' => $order['status'],//订单状态 1 待处理  2 成功 0 关闭
            'out_trade_no' => $order['out_trade_no'],//商户订单号
            'trade_no' => $order['trade_no'],//平台订单号
            'create_time' => $order['create_time'],//订单创建时间
            'amount' => $order['amount'],//订单金额
        ];
        $result['sign'] = $this->getSign($result, $agent->key);

        return ['code' => '1', 'msg' => '请求成功', 'data' => $result];
    }


    /**
     * 商户后台申请代付
     */
    public
    function manualCreateOrder($param, $userInfo)
    {
        //参数验证
        if (!isset($param['amount'])
            || !isset($param['bank_code'])
            || !isset($param['bank_number'])
            || !isset($param['bank_owner'])
            || !isset($param['bank_number'])
            || !isset($param['body'])
        ) {
            return ['code' => '0', 'msg' => '非法操作'];
        }
        if (!($param['amount'])
            || !($param['bank_code'])
            || !($param['bank_number'])
            || !($param['bank_owner'])
            || !($param['bank_number'])
//            || !($param['body'])
        ) {
            return ['code' => '0', 'msg' => '请输入必填参数'];
        }
        //添加对应参数
        $param['mchid'] = $userInfo['uid'];
        $param['notify_url'] = '';
        $param['out_trade_no'] = create_order_no();
        return $this->createOrder($param, false);
    }


    /**
     * 创建代付订单
     */
    public
    function createOrder($params, $is_sign = true)
    {

        $result = $this->checkParams($params, $is_sign);
        if ($result['code'] != '1') {
            return $result;
        }
        //开启事务
	$this->modelDaifuOrders->startTrans();
	$where1['id'] = 1;
	$dorder = $this->modelDaifuOrders->where($where1)->lock(true)->find();
        $serviceCharge = $this->getServiceCharge($params['amount'], $params['mchid']);
        if ($serviceCharge['code'] != '1') {
            return $serviceCharge;
        }


        $data = [
            'notify_url' => $result['data']['notify_url'],
            'uid' => $result['data']['mchid'],
            'amount' => $result['data']['amount'],
            'bank_number' => $result['data']['bank_number'],
            'bank_owner' => $result['data']['bank_owner'],
            'bank_id' => $result['data']['bank_id'],
            'bank_name' => $result['data']['bank_name'],
            'out_trade_no' => $result['data']['out_trade_no'],
          //  'trade_no' => create_order_no(),
            'trade_no' => $result['data']['out_trade_no'],
            'body' => $result['data']['body'],
            'subject' => isset($result['data']['subject']) ? $result['data']['subject'] : '',
            'create_time' => time(),
            'status' => '1',
            'update_time' => time(),
            'service_charge' => $serviceCharge['data']['service_charge'],
            'single_service_charge' => $serviceCharge['data']['single_service_charge'],
        ];

        //从yhk这个编码所支持的渠道的码商里面随机分配一个码商
/*        $mch = $this->logicUser->getInfo(['uid' => $result['data']['mchid']]);
        if (explode(',',$mch['pao_ms_ids'])[0]){
            $data['ms_id'] = explode(',',$mch['pao_ms_ids'])[0];
        }else{
            $ms_id =  $this->modelDaifuOrders->getRandMs();
            $ms_id &&  $data['ms_id'] = $ms_id;
        }*/

        $result['data']['pao_ms_ids'] && $data['ms_id'] = explode(',',$result['data']['pao_ms_ids'])[0];
        $result['data']['pao_ms_ids'] && $data['status'] = 3;

//        //商户指定的码商
//        $mch = $this->logicUser->getInfo(['uid' => $result['data']['mchid']]);
//        $data['ms_id'] = explode(',',$mch['pao_ms_ids'])[0];
//        $data['matching_time'] = time();
        $this->modelDaifuOrders->create($data);
        //冻结金额
        $this->createChange($data);

        //是否通知到跑分码商抢单
        $isNotifypf = false;

        if ($isNotifypf) {
            $data['mch_name'] = $result['data']['mch_name'];
            $data['pao_ms_ids'] = $result['data']['pao_ms_ids'];

            $transfer = $this->createTransfer($data);

            if ($transfer['code'] != '1') {
                $this->modelDaifuOrders->rollback();
                return ['code' => '0', 'msg' => $transfer['msg']];
            }

        }
        $this->modelDaifuOrders->commit();


        return ['code' => 1, 'msg' => '请求成功', 'data' => ['amount' => $data['amount'], 'trade_no' => $data['trade_no'], 'out_trade_no' => $data['out_trade_no']]];

    }


    private
        $key = '';
    private
        $url = '';
    private
        $notify_url = '';
    private
        $notify_ip = '';
    private
        $admin_id = '';

    /**
     * 设置代付请求参数
     */
    public
    function setParam()
    {
        $Config = new Config();
        $this->url = $Config->getConfigInfo(['name' => 'daifu_host', 'group' => '0'])->value;
        $this->key = $Config->getConfigInfo(['name' => 'daifu_key', 'group' => '0'])->value;
        $this->notify_url = $Config->getConfigInfo(['name' => 'daifu_notify_url', 'group' => '0'])->value;
        $this->notify_ip = $Config->getConfigInfo(['name' => 'daifu_notify_ip', 'group' => '0'])->value;
        $this->admin_id = $Config->getConfigInfo(['name' => 'daifu_admin_id', 'group' => '0'])->value;
    }

    /**
     * 验证回调ip
     */
    public
    function checkNotifyIp()
    {
        if ($_SERVER['REMOTE_ADDR'] != $this->notify_ip) {
            return false;
        }
        return true;
    }

    /**
     * 发起代付请求
     */
    public
    function createTransfer($param)
    {
//        $url = 'http://39.107.74.181:8533/api/transfer/create';

        $data = [
            'realname' => $param['bank_owner'],
            'bank_name' => $param['bank_name'],
            'bank_num' => $param['bank_number'],
            'transfer_title' => $param['body'],
            'money' => $param['amount'],
            'order_no' => $param['trade_no'],
            'notify_url' => $this->notify_url,//'http://39.107.74.181:8534/api/dfPay/notify'
            'admin_id' => $this->admin_id,
            'mch_name' => $param['mch_name'],
            'pao_ms_ids' => $param['pao_ms_ids'],
            'admin_id' => $this->admin_id,
        ];

        $data['sign'] = $this->getSign($data, $this->key);
        \think\Log::notice('createTransfer url' . $this->url . 'param' . json_encode($data));
        $result = $this->curl_post($this->url, $data);
        \think\Log::notice('createTransfer data' . $result);
        $result = json_decode($result, true);
        if ($result['status'] != '1') {
            return ['code' => '0', 'msg' => $result['message']];
        }
        return ['code' => '1', 'msg' => '请求成功'];
    }

    /**
     * 获取sign
     */
    public
    function getSign($param, $key)
    {
        if (isset($param['sign'])) {
            unset($param['sign']);
        }
        ksort($param);
        \think\Log::notice('createTransfer data' . http_build_query($param) . '&' . $key);
//echo http_build_query($param) . '&' . $key;
        return md5(http_build_query($param) . '&' . $key);
    }

    /**
     * 获取商户
     */
    public
    function checkAgent($mchid)
    {
        $Api = new Api();
        return $Api->getApiInfo(['uid' => $mchid]);
    }


    /**
     * curl
     * @param string $url [description]
     * @return [type]      [description]
     */

    public
    function curl_post($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


}
