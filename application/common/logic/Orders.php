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

namespace app\common\logic;


use app\common\library\enum\CodeEnum;
use app\common\library\exception\OrderException;
use app\common\model\DaifuOrders;
use app\common\validate\DaifuQueryOrder;
use GuzzleHttp\Client;
use think\Db;
use think\Exception;
use think\Log;

class Orders extends BaseLogic
{

    /**
     *
     * 获取订单列表
     *
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     * @author 勇敢的小笨羊
     */
    public function getOrderList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelOrders->limit = !$paginate;

        return $this->modelOrders->getList($where, $field, $order, $paginate);
    }


    /**
     * 重新一个吧  保留原来的
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getOrderList2($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelOrders->alias('a');

        $join = [
            ['pay_account b', 'a.cnl_id = b.id', 'left'],
            ['pay_channel c', 'b.cnl_id = c.id', 'left'],
            ['pay_code d', 'a.channel = d.code', 'left'],
        ];

        $this->modelOrders->join  = $join;
        $this->modelOrders->limit = !$paginate;

        return $this->modelOrders->getList($where, $field, $order, $paginate);
    }


    /**
     * 重新一个吧  保留原来的
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     */
    public function getOrderList3($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelOrders->alias('a');

        $join = [
            ['pay_account b', 'a.cnl_id = b.id', 'left'],
            ['pay_channel c', 'b.cnl_id = c.id', 'left'],
            ['pay_code d', 'a.channel = d.code  ', 'left'],
            ['orders_notify e', 'e.order_id = a.id', 'left'],
        ];


        $this->modelOrders->group('trade_no');
        $this->modelOrders->join  = $join;
        $this->modelOrders->limit = !$paginate;

        return $this->modelOrders->getList($where, $field, $order, $paginate);
    }


    /**
     * 获取订单信息
     *
     * @param array $where
     * @param bool|string $field
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrderInfo($where = [], $field = true)
    {
        return $this->modelOrders->getInfo($where, $field);
    }

    /**
     * 获取订单信息
     *
     * @param array $where
     * @param bool|string $field
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getDaifuOrderInfo($where = [], $field = true)
    {
        return $this->modelDaifuOrders->getInfo($where, $field);
    }

    /**
     * 获取订单异步信息
     *
     * @param array $where
     * @param bool $field
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrderNotify($where = [], $field = true)
    {
        return $this->modelOrdersNotify->getInfo($where, $field);
    }


    /**
     * 新获取订单数量方法 怕出错  以前的不删除
     * @param array $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getOrdersCount2($where = [])
    {

        return $this->modelOrders->alias('a')
            ->where($where)
            ->join('pay_account b', 'a.cnl_id = b.id', 'left')
            ->join('pay_channel c', 'b.cnl_id = c.id', 'left')
            ->count();

    }


    /**
     * 新获取订单数量方法 怕出错  以前的不删除
     * @param array $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getOrdersCount3($where = [])
    {

        return $this->modelOrders->alias('a')
            ->where($where)
            ->join('pay_account b', 'a.cnl_id = b.id', 'left')
            ->join('pay_channel c', 'b.cnl_id = c.id', 'left')
            ->join('pay_code d', 'a.channel = d.code', 'left')
            ->join('orders_notify e', 'e.order_id = a.id', 'left')
            ->count();

    }

    /**
     * 获取单总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getOrdersCount($where = [])
    {

        return $this->modelOrders->getCount($where);
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
        $this->modelOrders->alias('a');
        $join = [
            ['pay_account b', 'a.cnl_id = b.id', 'left'],
            ['pay_channel c', 'b.cnl_id = c.id', 'left'],
        ];

        $this->modelOrders->join = $join;

        return [
            'fees' => $this->modelOrders->getInfo($where, "COALESCE(count(a.id),0) as total_count,COALESCE(sum(a.amount),0) as total,count(case when a.`status`=2 then 1 else null end) as paid_count,COALESCE(sum(if(a.status=2,amount,0)),0) as paid,COALESCE(sum(a.user_in),0) as user,COALESCE(sum(a.agent_in),0) as agent,COALESCE(sum(a.platform_in),0) as platform")
        ];
    }

    /**
     * 获取控制台统计
     *
     * @param array $where
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getWelcomeStat($where = [])
    {
        $order = 'create_time desc';
        return [
            'order' => $this->modelOrders->getInfo($where, "count(id) as total,sum(user_in) as user_all_in,count(if(status=2,true,null)) as success,count(if(status=1,true,null)) as wait,count(if(status=0,true,null)) as failed,COALESCE(cast(sum(amount) AS decimal(15,2)),0) as fees,COALESCE(sum(if(status=1,amount,0)),0) as unpaid,COALESCE(cast(sum(if(status=2,amount,0)) AS decimal(15,2)),0) as paid", $order, $paginate = false),
            'user'  => $this->modelUser->getInfo($where, "count(uid) as total,count(if(is_verify=0,true,null)) as failed", $order, $paginate = false),
            'cash'  => $this->modelBalanceCash->getInfo($where, 'count(id) as total,count(if(status=2,true,null)) as success,count(if(status=1,true,null)) as wait,COALESCE(sum(if(status=0,amount,0)),0) as failed', $order, $paginate = false),
            'fees'  => $this->modelOrders->getInfo($where, "COALESCE(cast(sum(amount) AS decimal(15,2)),0) as total,COALESCE(cast(sum(if(status=2,amount,0)) AS decimal(15,2)),0) as paid")
        ];
    }

    /**
     * 获取代付相关统计
     * @param array $where
     */
    public function getDaifuStat($where = [])
    {
        $order = 'create_time desc';
        return [
            'dayfuorder' => $this->modelDaifuOrders->getInfo($where, "count(id) as total,count(if(status=2,true,null)) as success,count(if(status=1,true,null)) as wait,count(if(status=0,true,null)) as failed,COALESCE(cast(sum(amount) AS decimal(15,2)),0) as fees,COALESCE(sum(if(status=1,amount,0)),0) as unpaid,COALESCE(cast(sum(if(status=2,amount,0)) AS decimal(15,2)),0) as paid", $order, $paginate = false),
            'recharge'   => $this->modelDepositeOrders->getInfo($where, "count(id) as total,count(if(status=1,true,null)) as success,count(if(status=0,true,null)) as wait,count(if(status=2,true,null)) as failed,COALESCE(cast(sum(amount) AS decimal(15,2)),0) as fees,COALESCE(sum(if(status=0,amount,0)),0) as unpaid,COALESCE(cast(sum(if(status=1,amount,0)) AS decimal(15,2)),0) as paid", $order, $paginate = false),
        ];

    }


    public
    function getOrderChannelStat($where = [], $field = "a.cnl_id,count(a.cnl_id) as total_orders,count(case when a.`status`=2 then 1 else null end) as paid_orders,COALESCE(sum(a.amount),0) as total_fee_all,COALESCE(sum(if(a.status = 1,a.amount,0)),0) as total_fee_dis,COALESCE(sum(if(a.status = 2,a.amount,0)),0) as total_fee_paid,b.id,b.name,b.remarks,b.daily,b.rate", $order = 'a.create_time desc', $paginate = 15)
    {
        $this->modelOrders->group = 'a.cnl_id';
        $this->modelOrders->alias('a');

        $join = [
            ['pay_account b', 'b.id = a.cnl_id'],
        ];

        $this->modelOrders->join = $join;
        return $this->modelOrders->getList($where, $field, $order, $paginate = false);
    }

    /**
     * 年月订单以及交易额统计
     *
     * @param array $where
     * @return array|mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function getOrdersMonthStat($where = [])
    {
        $this->modelOrders->group = 'month';
        return $this->modelOrders->getList($where, "count(id) as total_orders,COALESCE(sum(`amount`),0) as total_amount,FROM_UNIXTIME(create_time,'%m') as month", false, false);
    }

    /**
     * 获取商户订单统计
     *
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function getOrderUserStat($where = [], $field = "a.uid,b.username,count(a.uid) as total_orders,count(if(a.status=2,true,null)) as total_paid_count,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(a.status=1,amount,0)),0) as total_fee_dis,COALESCE(sum(if(a.status=2,amount,0)),0) as total_fee_paid,COALESCE(sum(if(a.status=2,platform_in,0)),0) as total_plat_fee", $order = 'a.uid asc', $paginate = 15)
    {
        $this->modelOrders->alias('a');
        $this->modelOrders->group = 'uid';
        $join                     = [
            ['user b', 'a.uid = b.uid', 'left'],
        ];
        $this->modelOrders->join  = $join;
        return $this->modelOrders->getList($where, $field, $order, $paginate = false);
    }


    /**
     * 根据每个商户的每个pay_code进行统计
     * @param array $where
     * @param string $field
     * @param string $order
     * @param bool $paginate
     * @return mixed
     */
    public
    function getOrderUserStatByPayCode($where = [], $field = "c.username as username,b.name as pay_code_name,a.uid,channel,count(channel) as channel_count,count(if(a.status=2,true,null)) as total_paid_channel_count", $order = 'a.create_time desc', $paginate = 15)
    {
        $this->modelOrders->group = 'a.channel';
        $this->modelOrders->alias('a');
        $join                    = [
            ['pay_code b', 'a.channel = b.code', 'left'],
        ];
        $this->modelOrders->join = $join;
        return $this->modelOrders->getList($where, $field, $order, $paginate = false);
    }


    /**
     * 获取渠道订单统计
     *
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $paginate
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */


    /**
     * 获取某订单支付通道配置
     *
     * @param $order_no
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function getOrderPayConfig($order_no)
    {

        return $this->logicPay->getAccountInfo([
                'id' => $this->modelOrders->getValue(['trade_no' => $order_no], 'cnl_id')
            ]
        );
    }

    /**
     * 推送队列
     *
     * @param $order_id
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function pushOrderNotify($order_id = '')
    {
        //订单
        $order = $this->getOrderInfo(['id' => $order_id]);
        //加入队列
        $result = $this->logicQueue->pushJobDataToQueue('AutoOrderNotify', $order, 'AutoOrderNotify');
        if ($result) {
            $returnmsg = ['code' => CodeEnum::SUCCESS, 'msg' => '推送队列成功'];
        } else {
            $returnmsg = ['code' => CodeEnum::SUCCESS, 'msg' => '推送队列成功'];
        }
        action_log('推送', '推送异步订单通知，单号：' . $order['out_trade_no']);
        return $returnmsg;
    }


    /**
     * @param string $order_id
     * @return array
     * 重发回调 不推送队列
     */
    public
    function pushOrderNotifyV2($order_id = '')
    {
        //订单
        $order = $this->getOrderInfo(['id' => $order_id]);
        if (!$order) {
            return ['code' => CodeEnum::ERROR, 'msg' => '订单不存在'];
        }

        $where        = array();
        $where['uid'] = $order['uid'];
        $LogicApi     = new \app\common\logic\Api();
        $appKey       = $LogicApi->getApiInfo($where, "key");
        $to_sign_data = $this->buildSignData($order->toArray(), $appKey["key"]);
        Log::notice("posturl: " . $order['notify_url']);
        Log::notice("sign data: " . json_encode($to_sign_data));

        try {
            $client = new Client();
            Log::notice('补发回调');
            $response   = $client->request(
                'POST', $order['notify_url'], ['form_params' => $to_sign_data, 'timeout' => '5']
            );
            $statusCode = $response->getStatusCode();
            $contents   = $response->getBody()->getContents();
            Log::notice("response code: " . $statusCode . " response contents: " . $contents);
            if ($statusCode == 200 && !is_null($contents)) {
//                判断放回是否正确
                if ($contents == "SUCCESS") {
                    //TODO 更新写入数据
                    $result       = [
                        'result'    => $contents,
                        'is_status' => $statusCode
                    ];
                    $ordersNotify = (new OrdersNotify())->where(['order_id' => $order['id']]);
                    $ordersNotify->update($result);
                    return ['code' => CodeEnum::SUCCESS, 'msg' => '回调发送成功'];
                }
            }
            return ['code' => CodeEnum::ERROR, 'msg' => '回调发送失败'];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Notify Error:[' . $e->getMessage() . ']');
            return ['code' => CodeEnum::ERROR, 'msg' => $e->getMessage()];
        }
    }


    /**
     * 构建返回数据对象
     *
     * @param $data
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    private
    function buildSignData($data, $md5Key)
    {
        //除去不需要字段
        unset($data['id']);
        unset($data['uid']);
        unset($data['cnl_id']);
        unset($data['puid']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['update_time']);
        unset($data['update_time']);
        unset($data['income']);
        unset($data['user_in']);
        unset($data['agent_in']);
        unset($data['platform_in']);
        unset($data['currency']);
        unset($data['client_ip']);
        unset($data['return_url']);
        unset($data['notify_url']);
        unset($data['extra']);
        unset($data['subject']);
        unset($data['bd_remarks']);
        unset($data['remark']);
        unset($data['visite_show_time']);
        unset($data['real_need_amount']);
        unset($data['image_url']);
        unset($data['request_log']);
        unset($data['visite_time']);
        unset($data['request_elapsed_time']);

        $data['amount']       = sprintf("%.2f", $data['amount']);
        $data['order_status'] = 1;
        ksort($data);

        $signData = "";
        foreach ($data as $key => $value) {
            $signData = $signData . $key . "=" . $value;
            $signData = $signData . "&";
        }
        $str = $signData . "key=" . $md5Key;

//        print("<info>md5 str:".$str."</info>\n");
        $sgin         = md5($str);
        $data['sign'] = $sgin;
        //返回
        return $data;
    }


    /**
     * 创建支付订单
     *
     * @param $orderData
     * @return mixed
     * @throws OrderException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function createPayOrder($orderData)
    {
        //TODO 事务处理
        $order                 = new Orders();
        $where["uid"]          = $orderData['mchid'];
        $where["out_trade_no"] = $orderData['out_trade_no'];

        $orderInfo = $order->where($where)->find();
        if (!empty($orderInfo)) {
            throw new OrderException([
                'msg'     => "Create Order Error, Order No exsite.",
                'errCode' => '200003'
            ]);
        }
        Db::startTrans();
        try {
            //这里就这样   不改了
            $order          = new Orders();
            $User           = new \app\common\model\User();
            $userInfo       = $User->where('uid=' . $orderData['mchid'])->find();
            $order->puid    = empty($userInfo['puid']) ? 0 : $userInfo['puid']; //代理id
            $order->uid     = $orderData['mchid']; //商户ID
            $order->subject = $orderData['subject'];//支付项目
            $order->body    = $orderData['body'];//支付具体内容

            $order->trade_no = intval($orderData['mchid'] % 100) . (microtime(true) * 10000) . rand(100000, 999999);//支付单号
            //读取配置 如果后台配置是使用平台下级订单号
            $request_pay_type = \app\common\model\Config::where(['name' => 'request_pay_type'])->find()->toArray();
            if ($request_pay_type) {
                if ($request_pay_type['value'] == 2) {
                    $order->trade_no = $orderData['out_trade_no'];
                }
            }
            $order->remark       = $orderData['remark'];
            $order->out_trade_no = $orderData['out_trade_no'];//商户单号
            $order->amount       = $orderData['amount'];//支付金额
            $order->currency     = $orderData['currency'];//支付货币
            $order->channel      = $orderData['channel'];//支付渠道
            $order->client_ip    = "192.168.0.1";//订单创建IP
            $order->return_url   = urldecode($orderData['return_url']);//通知Url
            $order->notify_url   = urldecode($orderData['notify_url']);//通知Url
            $order->extra        = json_encode(!empty($orderData['extparam']) ? $orderData['extparam'] : []);//拓展参数
            $order->save();

            Db::commit();
            //  余额 = 可用余额（可提现金额） + 冻结余额（待结算金额） =》 未支付金额每日清算
            //   可用余额是从冻结余额转入的
            //写入待支付金额 creatBalanceChange('100001','100',$remarks = '记录资金变动测试','字段',$setDec = true);
            //$this->logicBalanceChange->creatBalanceChange($order->uid,$order->amount,'单号'.$orderData['out_trade_no'].'预下单支付金额','disable');

            return $order;

        } catch (\Exception $e) {
            //记录日志
            Log::error("Create Order Error:[{$e->getMessage()}]");
            Db::rollback();
            //抛出错误异常
            throw new OrderException([
                'msg'     => "Create Order Error, Please Try Again Later." . $e->getMessage(),
                'errCode' => '200001'
            ]);
        }
    }

    /**
     * 创建支付订单
     *
     * @param $orderData
     * @return mixed
     * @throws OrderException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function createDaifuPayOrder($orderData)
    {
        //TODO 事务处理
        Db::startTrans();
        try {
            $bank_id = intval($orderData['bank_code']);
            //这里就这样   不改了
            $DaifuOrders               = new DaifuOrders();
            $DaifuOrders->uid          = $orderData['mchid']; //商户ID
            $DaifuOrders->subject      = $orderData['subject'];//支付项目
            $DaifuOrders->body         = $orderData['body'];//支付具体内容
            $DaifuOrders->trade_no     = create_order_no();//支付单号
            $DaifuOrders->out_trade_no = $orderData['out_trade_no'];//商户单号
            $DaifuOrders->amount       = $orderData['amount'];//支付金额

            $DaifuOrders->bank_number = $orderData['bank_number'];//支付货币
            $DaifuOrders->bank_id     = $bank_id;//真实用户名
            $DaifuOrders->bank_owner  = $orderData['bank_owner'];//支付渠道
            $DaifuOrders->bank_name   = "日本银行";//订单创建IP
            $DaifuOrders->notify_url  = $orderData['notify_url'];//通知Url

            $DaifuOrders->save();
            Db::commit();

            //  余额 = 可用余额（可提现金额） + 冻结余额（待结算金额） =》 未支付金额每日清算
            //   可用余额是从冻结余额转入的
            //写入待支付金额 creatBalanceChange('100001','100',$remarks = '记录资金变动测试','字段',$setDec = true);
            $this->logicBalanceChange->creatBalanceChange($DaifuOrders->uid, $DaifuOrders->amount, '单号' . $orderData['out_trade_no'] . '预下单支付金额', 'disable');

            return $DaifuOrders;

        } catch (\Exception $e) {
            //记录日志
            Log::error("Create Order Error:[{$e->getMessage()}]");
            Db::rollback();
            //抛出错误异常
            throw new OrderException([
                'msg'     => "Create Order Error, Please Try Again Later." . $e->getMessage(),
                'errCode' => '200001'
            ]);
        }
    }

    /**
     * 设置某个字段参数
     *
     * @param array $where
     * @param string $field
     * @param string $value
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public
    function setOrderValue($where = [], $field = 'cnl_id', $value = '')
    {
        $this->modelOrders->setFieldValue($where, $field, $value);
    }


    /*
     * 统计订单相关数据
     *
     * @param array $where
     */
    public
    function calOrdersData($where = [])
    {
        //订单总金额cast(sum(amount) AS decimal(15,2))
        $data['total_money'] = $this->modelOrders->where($where)->value('cast(sum(amount) AS decimal(15,2)) as total_mount');
        //平台总收入
        $data['total_platform_in'] = $this->modelOrders->where($where)->value('cast(sum(platform_in) AS decimal(15,2)) as total_platform_in');
        //代理总收入
        $data['total_agent_in'] = $this->modelOrders->where($where)->value('cast(sum(agent_in) AS decimal(15,2)) as total_agent_in');
        //手续费
        $data['service_charge'] = $this->modelOrders->where($where)->value('cast(sum(platform_in+agent_in) AS decimal(15,2)) as service_charge');
        //用户总收入
        $data['total_user_in'] = $this->modelOrders->where($where)->value('cast(sum(user_in) AS decimal(15,2)) as total_user_in');
        //订单总订单数量
        $data['total_count'] = $this->modelOrders->where($where)->count('id');
        //订单完成金额
        $where['status']            = 2;
        $data['total_finish_money'] = $this->modelOrders->where($where)->value('cast(sum(amount) AS decimal(15,2)) as total_mount');
        //完成订单数量
        $data['total_finish_count'] = $this->modelOrders->where($where)->count('id');
        //成功率
        if ($data['total_finish_count'] == 0) {
            $success_percent = '0.00';
        } else {
            $success_percent = sprintf("%.2f", $data['total_finish_count'] / $data['total_count']);
        }
        $data['success_percent'] = $success_percent;
        return $data;
    }

    public function getOrderChannelStatV2($pay_code = 'h5_zfb', $minite = 15)
    {
        $timeStamps = time() - $minite * 60;
        $sql        = "SELECT a.id AS pay_channel_id,a.NAME AS pay_chanel_name,
(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=30  and a.create_time >= '{$timeStamps}' 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_30_count,
(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=30  and  a.create_time >= '{$timeStamps}' and  a.`status`=2 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_30_success_count,

(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=50  and a.create_time >= '{$timeStamps}' 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_50_count,
(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=50  and  a.create_time >= '{$timeStamps}' and  a.`status`=2 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_50_success_count,

(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=100  and a.create_time >= '{$timeStamps}' 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_100_count,
(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=100  and  a.create_time >= '{$timeStamps}' and  a.`status`=2 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_100_success_count,

(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=200  and a.create_time >= '{$timeStamps}' 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_200_count,
(SELECT count( a.id ) FROM cm_orders a	LEFT JOIN cm_pay_account b ON a.cnl_id = b.id LEFT JOIN cm_pay_channel c ON b.cnl_id = c.id   WHERE a.amount=200  and  a.create_time >= '{$timeStamps}' and  a.`status`=2 	AND channel = '{$pay_code}' AND c.id = pay_channel_id ) AS order_price_200_success_count
FROM	cm_pay_channel a";
        $data       = Db::query($sql);
        foreach ($data as $k => $v) {
            $data[$k]['success_rate_30'] = 0;
            if ($v['order_price_30_count'] > 0) {
                $data[$k]['success_rate_30'] = sprintf("%.1f", ($v['order_price_30_success_count'] / $v['order_price_30_count']) * 100);;
            }
            $data[$k]['success_rate_50'] = 0;

            if ($v['order_price_50_count'] > 0) {
                $data[$k]['success_rate_50'] = sprintf("%.1f", ($v['order_price_50_success_count'] / $v['order_price_50_count']) * 100);;
            }
            $data[$k]['success_rate_100'] = 0;

            if ($v['order_price_100_count'] > 0) {
                $data[$k]['success_rate_100'] = sprintf("%.1f", ($v['order_price_100_success_count'] / $v['order_price_100_count']) * 100);;
            }
            $data[$k]['success_rate_200'] = 0;

            if ($v['order_price_200_count'] > 0) {
                $data[$k]['success_rate_200'] = sprintf("%.1f", ($v['order_price_200_success_count'] / $v['order_price_200_count']) * 100);;
            }

        }
        return $data;
    }

}
