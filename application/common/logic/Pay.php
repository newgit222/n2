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
use app\common\model\PayAccount;
use think\Db;
use think\Log;

class Pay extends BaseLogic
{

    /**
     * 下单时通过pay_code 获取渠道下的可用商户配置
     *
     * @param $order
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAllowedAccount($order)
    {

        //1.传入支付方式获取对应渠道cnl_id
//        $codeInfo = $this->modelPayCode->getInfo(['code' => $order['channel'],'account_type_type'=>'pay'], 'id as co_id,cnl_id,cnl_weight');
        $codeInfo = $this->modelPayCode->getInfo(['code' => $order['channel']], 'id as co_id,cnl_id,cnl_weight');
        /**  zhangxiaohei 2020-3-10 update 权重越大 优先级越高**/
//        $codeInfos = $this->modelPayCode->getList(['code' => $order['channel']], 'id as co_id,cnl_id,cnl_weight')->toArray()['data'];
//        $codeInfo = [];
//        if($codeInfos) {
//            $array = [];
//            foreach ($codeInfos as $k => $v) {
//                if ($v['cnl_weight']) {
//                    $cnl_weight = json_decode($v['cnl_weight'], true)[$v['cnl_id']];
//                    $array[$k] = $cnl_weight;
//                }
//            }
//            //如果有设置了渠道权重的 值越大 优先级越高
//            if ($array) {
//                arsort($array);
//                foreach ($array as $k => $v) {
//                    $codeInfo = $codeInfos[$k];
//                    break;
//                }
//            } else {
//                //随机取一个
//                $codeInfo = $codeInfos[rand(0, count($codeInfos) - 1)];
//            }
//        }else{
//            return ['errorCode' => '400028','msg' => '渠道不存在'];
//        }
        /**  end zhangxiaohei 2020-3-10 update 权重越大 优先级越高  **/

        if (empty($codeInfo)) {
            return ['errorCode' => '400028', 'msg' => '渠道不存在' . $order['channel']];
        }
//        $codeInfo = $codeInfo->toArray();

        //本pay_code对应的渠道权重
        $codeCnlWeights = json_decode($codeInfo['cnl_weight'], true);
        $codeCnlWeights = $codeCnlWeights ? array_filter($codeCnlWeights) : [];
        //2.cnl_id获取支持该方式的渠道列表
        $channels = $this->modelPayChannel->getColumn(['id' => ['in', $codeInfo['cnl_id']], 'status' => ['eq', '1']],
            'id,name,action,timeslot,return_url,notify_url,remarks,limit_moneys');

        //判断商户是否指定渠道   zhangxiaohei  5-21 update

        //读取指定渠道表
        $appoint = $this->modelUserPayCodeAppoint->where(['uid' => $order['uid'], 'pay_code_id' => $codeInfo['co_id']])->find();
        if ($appoint) {
            $channels_tmp = $this->modelPayChannel->getColumn(['id' => ['in', $appoint['cnl_id']], 'status' => ['eq', '1']],
                'id,name,action,timeslot,return_url,notify_url,remarks');
            if ($channels_tmp) {
                $channels = $channels_tmp;
            }
        }


        //jine pipei
        $achannelIds = [];
        foreach ($channels as $channel) {
            $achannelIds[] = $channel['id'];
        }
        $accounts = $this->modelPayAccount->getColumn(['cnl_id'            => ['in', $achannelIds],
                                                       'status'            => ['eq', '1'],
                                                       'max_deposit_money' => ['egt', $order['amount']],
                                                       'min_deposit_money' => ['elt', $order['amount']],
        ],
            'id,co_id,name,single,daily,timeslot,param,cnl_id');


        $newChannels = [];
        foreach ($accounts as $account) {
            if (in_array($codeInfo['co_id'], explode(",", $account['co_id']))) {
                foreach ($channels as $channel) {
                    if ($account['cnl_id'] == $channel['id']) {
                        $newChannels[$channel['id']] = $channel;
                        break;
                    }
                }

            }
        }

        if (empty($newChannels)) {
            return ['errorCode' => '400028', 'msg' => '金额不存在'];
        }
        //  $newChannels = array_unique($newChannels);
        //3.规则排序选择合适渠道
        /*******************************/
        //TODO 写选择规则  时间、状态、费率 等等
        //规则处理  我先简便写一下
        $channelsMap = [];
        foreach ($newChannels as $key => $val) {
            //$timeslot = json_decode($val['timeslot'],true);
            //if ( strtotime($timeslot['start']) < time() && time() < strtotime($timeslot['end']) ){
            if (1) {
                if (isset($codeCnlWeights[$key])) {
                    $val['weight'] = $codeCnlWeights[$key];
                } else {
                    $val['weight'] = 0;
                }
                $channelsMap[$key] = $val;
            }
        }

        //判断可用
        if (empty($channelsMap)) {
            return ['errorCode' => '400006', 'msg' => 'Route Payment Error. [No available channels]'];
        }


        if (empty($codeCnlWeights)) {
            //没有设置渠道权重-----随机
            $channel = $channelsMap[array_rand($channelsMap)];
        } else {
            //设置了渠道权重 根据权重随机匹配
            $channelsMapTmp = array_values($channelsMap);
            $cnl_id         = getidbyweight($channelsMapTmp);
            $channel        = $channelsMap[$cnl_id];
        }

        //此渠道金额判断
        $limitMoney = trim($channel['limit_moneys']) ? array_filter(explode(',', $channel['limit_moneys'])) : [];
        if ($limitMoney) {
            $limitMoney = array_map(function ($mon) {
                return $mon * 100;
            }, $limitMoney);
            //全部转换为分
            if (!in_array($order['amount'] * 100, $limitMoney)) {
                return ['errorCode' => '400008', 'msg' => 'Order Money Not Allowed'];
            }
        }


        /*******************************/
        //3.获取该渠道下可用账户
        $accounts = $this->modelPayAccount->getColumn(['cnl_id' => ['eq', $channel['id']], 'status' => ['eq', '1']],
            'id,co_id,name,single,daily,timeslot,param');
        //4.规则取出可用账户
        /*******************************/
        //TODO 写选择规则  时间、状态、费率 等等
        //规则处理  我先简便写一下
        $accountsMap = [];
        foreach ($accounts as $key => $val) {
            $timeslot = json_decode($val['timeslot'], true);
            if (in_array($codeInfo['co_id'], str2arr($val['co_id']))) {
                $accountsMap[$key] = $val;
            }
        }

        //判断可用
        if (empty($accountsMap)) {
            return ['errorCode' => '400008', 'msg' => 'Route Payment Error. [No available merchants account.]'];
        }
        $account     = $accountsMap[array_rand($accountsMap)];

        $accountConf = json_decode(htmlspecialchars_decode($account['param']), true);
        //判断配置是否正确
        if (is_null($accountConf)) {
            return ['errorCode' => '400008', 'msg' => 'Route Payment Error. [Payment account was misconfigured.]'];
        }
        //配置合并
        $configMap = array_merge($channel, $accountConf);
        //添加订单支付通道ID
        $this->logicOrders->setOrderValue(['trade_no' => $order['trade_no']], 'cnl_id', $account['id']);
        /*******************************/
        return [
            'channel' => $configMap['action'],
            'action'  => $order['channel'],
            'config'  => $configMap
        ];

    }

    /**
     * 下单时通过pay_code 获取渠道下的可用商户配置
     *
     * @param $order
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getDaifuAllowedAccount($order)
    {

        //1.传入支付方式获取对应渠道cnl_id
        $codeInfo = $this->modelPayCode->getInfo(['code' => $order['channel'], 'account_type_type' => 'daifupay'], 'id as co_id,cnl_id,cnl_weight');
        /**  zhangxiaohei 2020-3-10 update 权重越大 优先级越高**/
//        $codeInfos = $this->modelPayCode->getList(['code' => $order['channel']], 'id as co_id,cnl_id,cnl_weight')->toArray()['data'];
//        $codeInfo = [];
//        if($codeInfos) {
//            $array = [];
//            foreach ($codeInfos as $k => $v) {
//                if ($v['cnl_weight']) {
//                    $cnl_weight = json_decode($v['cnl_weight'], true)[$v['cnl_id']];
//                    $array[$k] = $cnl_weight;
//                }
//            }
//            //如果有设置了渠道权重的 值越大 优先级越高
//            if ($array) {
//                arsort($array);
//                foreach ($array as $k => $v) {
//                    $codeInfo = $codeInfos[$k];
//                    break;
//                }
//            } else {
//                //随机取一个
//                $codeInfo = $codeInfos[rand(0, count($codeInfos) - 1)];
//            }
//        }else{
//            return ['errorCode' => '400028','msg' => '渠道不存在'];
//        }
        /**  end zhangxiaohei 2020-3-10 update 权重越大 优先级越高  **/

        if (empty($codeInfo)) {
            return ['errorCode' => '400028', 'msg' => '渠道不存在' . $order['channel']];
        }
//        $codeInfo = $codeInfo->toArray();

        //本pay_code对应的渠道权重
        $codeCnlWeights = json_decode($codeInfo['cnl_weight'], true);
        $codeCnlWeights = $codeCnlWeights ? array_filter($codeCnlWeights) : [];
        //2.cnl_id获取支持该方式的渠道列表
        $channels = $this->modelPayChannel->getColumn(['id' => ['in', $codeInfo['cnl_id']], 'status' => ['eq', '1']],
            'id,name,action,timeslot,return_url,notify_url,remarks');

        //判断商户是否指定渠道


        //jine pipei
        $achannelIds = [];
        foreach ($channels as $channel) {
            $achannelIds[] = $channel['id'];
        }
        $accounts = $this->modelPayAccount->getColumn(['cnl_id'            => ['in', $achannelIds],
                                                       'status'            => ['eq', '1'],
                                                       'max_deposit_money' => ['egt', $order['amount']],
                                                       'min_deposit_money' => ['elt', $order['amount']],
        ],
            'id,co_id,name,single,daily,timeslot,param,cnl_id');


        $newChannels = [];
        foreach ($accounts as $account) {
            if (in_array($codeInfo['co_id'], explode(",", $account['co_id']))) {
                foreach ($channels as $channel) {
                    if ($account['cnl_id'] == $channel['id']) {
                        $newChannels[$channel['id']] = $channel;
                        break;
                    }
                }

            }
        }
        if (empty($newChannels)) {
            return ['errorCode' => '400028', 'msg' => '金额不存在'];
        }
        //  $newChannels = array_unique($newChannels);
        //3.规则排序选择合适渠道
        /*******************************/
        //TODO 写选择规则  时间、状态、费率 等等
        //规则处理  我先简便写一下
        $channelsMap = [];
        foreach ($newChannels as $key => $val) {
            //$timeslot = json_decode($val['timeslot'],true);
            //if ( strtotime($timeslot['start']) < time() && time() < strtotime($timeslot['end']) ){
            if (1) {
                if (isset($codeCnlWeights[$key])) {
                    $val['weight'] = $codeCnlWeights[$key];
                } else {
                    $val['weight'] = 0;
                }
                $channelsMap[$key] = $val;
            }
        }

        //判断可用
        if (empty($channelsMap)) {
            return ['errorCode' => '400006', 'msg' => 'Route Payment Error. [No available channels]'];
        }


        if (empty($codeCnlWeights)) {
            //没有设置渠道权重-----随机
            $channel = $channelsMap[array_rand($channelsMap)];
        } else {
            //设置了渠道权重 根据权重随机匹配
            $channelsMapTmp = array_values($channelsMap);
            $cnl_id         = getidbyweight($channelsMapTmp);
            $channel        = $channelsMap[$cnl_id];
        }
        /*******************************/
        //3.获取该渠道下可用账户
        $accounts = $this->modelPayAccount->getColumn(['cnl_id' => ['eq', $channel['id']], 'status' => ['eq', '1']],
            'id,co_id,name,single,daily,timeslot,param');
        //4.规则取出可用账户
        /*******************************/
        //TODO 写选择规则  时间、状态、费率 等等
        //规则处理  我先简便写一下
        $accountsMap = [];
        foreach ($accounts as $key => $val) {
            $timeslot = json_decode($val['timeslot'], true);
            if (in_array($codeInfo['co_id'], str2arr($val['co_id'])) && strtotime($timeslot['start']) < time() && time() < strtotime($timeslot['end'])) {
                $accountsMap[$key] = $val;
            }
        }

        //判断可用
        if (empty($accountsMap)) {
            return ['errorCode' => '400008', 'msg' => 'Route Payment Error. [No available merchants account.]'];
        }
        $account     = $accountsMap[array_rand($accountsMap)];
        $accountConf = json_decode($account['param'], true);
        //判断配置是否正确
        if (is_null($accountConf)) {
            return ['errorCode' => '400008', 'msg' => 'Route Payment Error. [Payment account was misconfigured.]'];
        }

        //配置合并
        $configMap = array_merge($channel, $accountConf);
        //添加订单支付通道ID
        $result = $this->modelDaifuOrders->setFieldValue(['trade_no' => $order['trade_no']], 'cnl_id', $account['id']);
        /*******************************/
        return [
            'channel' => $configMap['action'],
            'action'  => $order['channel'],
            'config'  => $configMap
        ];

    }

    /**
     * 获取所有支持的支付方式
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAppCodeMap()
    {
        return $this->modelPayCode->getColumn(['status' => 1], 'id,code', $key = 'id');
    }

    /**
     * 获取支付方式列表
     *
     * @param array $where
     * @param $field
     * @param string $order
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getCodeList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        return $this->modelPayCode->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取支付方式总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getCodeCount($where = [])
    {
        return $this->modelPayCode->getCount($where);
    }

    /**
     * 获取渠道列表
     *
     * @param array $where
     * @param $field
     * @param string $order
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getChannelList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        return $this->modelPayChannel->getList($where, $field, $order, $paginate);
    }


    /**
     * 渠道统计
     */
    public function getChannelStatisticsList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $time_15 = time() - (15 * 60);
        $time_20 = time() - (20 * 60);
        $time_30 = time() - (30 * 60);
        $time_60 = time() - (60 * 60);

        $yesterday = mktime(12, 0, 0, date('m'), date('d') - 1, date('Y'));

        $data = Db::query('select id,name,
(SELECT COUNT(o.id) from cm_orders o where   o.create_time >  ' . $time_15 . ' and o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as total_order_15,
(SELECT COUNT(o.id) from cm_orders o where o.status = \'2\' and  o.create_time >  ' . $time_15 . '  and  o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as success_order_15,
(SELECT COUNT(o.id) from cm_orders o where   o.create_time >  ' . $time_20 . ' and o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as total_order_20,
(SELECT COUNT(o.id) from cm_orders o where o.status = \'2\' and  o.create_time >  ' . $time_20 . '  and  o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as success_order_20,
(SELECT COUNT(o.id) from cm_orders o where   o.create_time >  ' . $time_30 . ' and o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as total_order_30,
(SELECT COUNT(o.id) from cm_orders o where o.status = \'2\' and  o.create_time >  ' . $time_30 . ' and  o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1))) as success_order_30,
(SELECT COUNT(o.id) from cm_orders o where   o.create_time >  ' . $time_60 . ' and o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1 ))) as total_order_60,
(SELECT COUNT(o.id) from cm_orders o where o.status = \'2\' and  o.create_time >  ' . $time_60 . ' and  o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1))) as success_order_60,
(SELECT sum(o.amount) from cm_orders o where o.status = \'2\' and  o.create_time >  ' . $yesterday . ' and  o.cnl_id in ((SELECT id FROM cm_pay_account pa where pa.cnl_id = pc.id and pa.status = 1))) as success_mileage_sum
from cm_pay_channel pc  where pc.status = 1;');


        $is_channel_mileage_sum = 0;
        //读取配置  是否显示渠道跑量
        $config = \app\common\model\Config::where(['name' => 'is_channel_mileage_sum'])->find();
        if ($config) {
            $config                 = $config->toArray();
            $is_channel_mileage_sum = $config['value'];
        }
        foreach ($data as $k => $v) {

            $data[$k]['is_channel_mileage_sum'] = $is_channel_mileage_sum;

            if (!$v['success_mileage_sum'] && !$v['total_order_60']) {
                unset($data[$k]);
            } else {
                $data[$k]['success_rate_15'] = 0;
                if ($v['total_order_15'] > 0) {
                    $data[$k]['success_rate_15'] = sprintf("%.1f", ($v['success_order_15'] / $v['total_order_15']) * 100);;
                }
                $data[$k]['success_rate_20'] = 0;
                if ($v['total_order_20']) {
                    $data[$k]['success_rate_20'] = sprintf("%.1f", ($v['success_order_20'] / $v['total_order_20']) * 100);
                }
                $data[$k]['success_rate_30'] = 0;
                if ($v['total_order_30']) {
                    $data[$k]['success_rate_30'] = sprintf("%.1f", ($v['success_order_30'] / $v['total_order_30']) * 100);
                }
                $data[$k]['success_rate_60'] = 0;
                if ($v['total_order_60']) {
                    $data[$k]['success_rate_60'] = sprintf("%.1f", ($v['success_order_60'] / $v['total_order_60']) * 100);
                }
                if (!$v['success_mileage_sum']) {
                    $data[$k]['success_mileage_sum'] = 0;
                }

            }
        }
        return $data;
    }


    /**
     * 获取渠道总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getChannelCount($where = [])
    {
        return $this->modelPayChannel->getCount($where);
    }

    /**
     * 获取渠道账户列表
     *
     * @param array $where
     * @param $field
     * @param string $order
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAccountList($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        return $this->modelPayAccount->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取渠道账户总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAccountCount($where = [])
    {
        return $this->modelPayAccount->getCount($where);
    }

    /**
     * 获取渠道信息
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getChannelInfo($where = [], $field = true)
    {
        return $this->modelPayChannel->getInfo($where, $field);
    }

    /**
     * 获取渠道账户信息
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getAccountInfo($where = [], $field = true)
    {
        return $this->modelPayAccount->getInfo($where, $field);
    }

    /**
     * 获取支付方式信息
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getCodeInfo($where = [], $field = true)
    {
        return $this->modelPayCode->getInfo($where, $field);
    }

    /**
     * 添加一个渠道
     *
     * @param $data
     * @return array|string
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function saveChannelInfo($data)
    {
        //TODO 数据验证
        $validate = $this->validatePayChannel->check($data);

        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validatePayChannel->getError()];
        }
        //渠道ip白名单处理
        $auth_ips = array_filter(explode(PHP_EOL, $data['notify_ips']));
        $tempIps  = [];
        foreach ($auth_ips as $ip) {
            $ip = trim($ip);
            if (empty($ip)) {
                continue;
            }
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return ['code' => CodeEnum::ERROR, 'msg' => 'ip格式填写错误'];
            }
            $tempIps[] = $ip;
        }
        if (empty($tempIps)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请输入回调通知IP白名单'];
        }
        $data['notify_ips'] = trim(implode(',', $tempIps));


        //TODO 添加数据
        Db::startTrans();
        try {

            //时间存储
            $data['timeslot'] = json_encode($data['timeslot']);
            //支付宝平台本渠道的密钥
            $data['channel_secret'] = md5(time());
            $this->modelPayChannel->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, '支付渠道' . $data['name']);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => $action . '渠道成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }


    /**
     * 添加一个渠道账户
     *
     * @param $data
     * @return array|string
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function saveAccountInfo($data)
    {
        //TODO 数据验证
        $validate = $this->validatePayAccount->check($data);

        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validatePayAccount->getError()];
        }

        //TODO 添加数据
        Db::startTrans();
        try {

            //时间存储
            $data['timeslot'] = json_encode($data['timeslot']);
            //方式存储
            $data['co_id'] = isset($data['co_id']) ? arr2str($data['co_id']) : '';

            $this->modelPayAccount->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, '支付渠道账户,' . $data['name']);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => $action . '渠道账户成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }

    /**
     * 添加一个方式
     *
     * @param $data
     * @return array|string
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function saveCodeInfo($data)
    {
        //TODO 数据验证
        $validate = $this->validatePayCode->check($data);

        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validatePayCode->getError()];
        }

        //TODO 添加数据
        Db::startTrans();
        try {

            $data['cnl_id'] = isset($data['cnl_id']) ? arr2str($data['cnl_id']) : '';

            $this->modelPayCode->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, '支付方式,data:' . http_build_query($data));

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => $action . '方式成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }


    /**
     * 改变渠道可用性
     *
     * @param $where
     * @param int $value
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function setChannelStatus($where, $value = 0)
    {
        Db::startTrans();
        try {
            $this->modelPayChannel->setFieldValue($where, $field = 'status', $value);
            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '修改状态成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * 删除一个方式
     *
     * @param $where
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function delCode($where)
    {
        Db::startTrans();
        try {
            $this->modelPayCode->deleteInfo($where);
            action_log('删除', '删除支付方式，ID：' . $where['id']);
            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '删除方式成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '删除支付方式失败'];
        }
    }


    /**
     * @param $where
     * 渠道还原
     * @return array
     */
    public function hyChannel($where)
    {
        Db::startTrans();
        try {
            $this->modelPayChannel->where($where)->setField('status', 1);
            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '还原渠道成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '删除渠道失败'];
        }
    }


    /**
     * 删除一个渠道
     *
     * @param $where
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function delChannel($where)
    {
        Db::startTrans();
        try {
            $this->modelPayChannel->deleteInfo($where);
            action_log('删除', '删除支付渠道，ID：' . $where['id']);
            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '删除渠道成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '删除渠道失败'];
        }
    }


    public function deleteChannel($where)
    {
        Db::startTrans();
        try {
            $this->modelPayChannel->where($where)->delete();
            action_log('物理删除', '删除支付渠道，ID：' . $where['id']);

            //渠道账户ids
            $ids = $this->modelPayAccount->where(['cnl_id' => $where['id']])->column('id');
            //删除渠道账户
            $this->modelPayAccount->where(['cnl_id' => $where['id']])->delete();
            //删除渠道账户费率
            $this->modelUserProfit->where(['cnl_id' => ['in', $ids]])->delete();
            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '删除渠道成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '删除渠道失败'];
        }
    }


    /**
     * 删除一个渠道账户
     *
     * @param $where
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function delAccount($where)
    {
        Db::startTrans();
        try {
            $this->modelPayAccount->deleteInfo($where);
            action_log('删除', '删除支付渠道账户，ID：' . $where['id']);
            //渠道账户就删除渠道账户下的用户分润
            $map['cnl_id'] = $where['id'];
            $this->modelUserProfit->where($map)->delete();

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '删除渠道账户成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, 'msg' => config('app_debug') ? $ex->getMessage() : '删除渠道账户失败'];
        }
    }


    public function bindTgGroupIdtoChannelBySercert($secret, $groupId)
    {
        $this->modelPayChannel->where(['channel_secret' => $secret])->setField('tg_group_id', $groupId);
    }


}
