<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 8/28/19
 * Time: 10:45 PM
 */

namespace app\common\model;

use think\Model;
class OwnpayOrder extends Model
{
    const  STAT_INIT = 0;
    const STAT_UNPAY = 1;
    const STAT_PAYED = 2;
    const STAT_PAYING = 3;
    const STAT_PAYEDRROR = 101;
    const STAT_DELETE = 102;

    //只支持微信
    const PAY_TYPE_VX = 1;

    //只支持支付宝
    const PAY_TYPE_ZFB = 2;

    //支付宝和微信都支持
    const PAY_TYPE_VX_ZFB = 3;

    //支持微信H5
    const PAY_TYPE_VX_H5 = 4;

    //微信Ｈ５支付过期时间
    const VX_PAY_LINK_EXPRIE_TIME = 180;

    /**
     * 获取可以支付的订单
     * @param $amout
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUnpayOrder($amout, $shopIds="", $shopType, $payType = self::PAY_TYPE_ZFB)
    {
        $where[] = $this->getTable() . '.orderPrice = :amount';
        $param['amount'] = $amout;
        $where[] = $this->getTable() . '.status = :status';
        $param['status'] = self::STAT_UNPAY;

        $where[] = $this->getTable() . '.addTime > :addTime';
        $param['addTime'] = request()->time() - (23*60*60+30*60);

        $where[] = $this->getTable() . '.error_times < :error_times';
        $param['error_times'] = 5;

        if($payType == self::PAY_TYPE_ZFB)
        {
            $payTypes = [
                self::PAY_TYPE_ZFB,
                self::PAY_TYPE_VX_ZFB,
            ];
            $where[] = $this->getTable() . '.pay_type in ('.implode("," , $payTypes).')';
        }

        if($payType == self::PAY_TYPE_VX)
        {
            $payTypes = [
                self::PAY_TYPE_VX,
                self::PAY_TYPE_VX_ZFB,
            ];

            $where[] = $this->getTable() . '.pay_type in ('.implode("," , $payTypes).')';
        }

        if($payType == self::PAY_TYPE_VX_H5)
        {
            $payTypes = [
                self::PAY_TYPE_VX,
                self::PAY_TYPE_VX_ZFB,
            ];
            $where[] = $this->getTable() . '.vx_qr_url <> ""';
            $where[] = $this->getTable() . '.pay_type in ('.implode("," , $payTypes).')';
            $where[] = $this->getTable() . '.update_vx_time >'.(request()->time() - self::VX_PAY_LINK_EXPRIE_TIME);
        }

        if(!empty($shopIds))
        {
            $where[] = $this->getTable() . '.storeid in ('.implode("," , $shopIds).')';
        }

        $fields = [
            $this->getTable() .".id",
        ];

        $order = $this->getTable() . '.addTime asc';
        //$order = "error_times asc, ".$this->getTable() . '.addTime asc';

        //30秒在线的订单
        $join = [
            [
                $this->getTable("shop"),
                $this->getTable().'.storeid = '. $this->getTable("shop").'.id',
                'left'
            ]
        ];

        $where[] = $this->getTable("shop") . '.type = :type';
        $param['type'] = $shopType;

        $where[] = $this->getTable("shop") . '.onlinedate > :onlinedate';
        $param['onlinedate'] = request()->time() - 300;

        $where = empty($where) ? '' : join(' AND ', $where);
        $res = $this->where($where)->field($fields)->join($join)->bind($param)->order($order)->lock(true)->find();
        return $res;
    }
    /**
     * 创建带支付订单
     * @param $id
     * @param $tradeNo
     * @return OwnpayOrder
     */
    public function createPayingOrder($id, $tradeNo)
    {
        $data["out_trade_no"] = $tradeNo;
        $data["status"]       = self::STAT_PAYING;
        $data["apply_pay_time"] = request()->time();
        $where['id'] = $id;

        return $this->where($where)->update($data);
    }

    public function resetPayingOrder($tradeNo)
    {
        $where["out_trade_no"] = $tradeNo;
        $info = $this->where($where)->find();
        if(empty($info))
        {
            return false;
        }

        if($info["status"] == self::STAT_PAYING)
        {
            $Order = new \app\common\model\Orders();
            $orderTrade = $Order->getTradeOrder($tradeNo);
            $where["out_trade_no"] = $tradeNo;
            $data["status"]       = self::STAT_UNPAY;
            $data["apply_pay_time"] = 0;
            $data["out_trade_no"] = 0;
            $data["error_times"] = $info["error_times"] + 1;
            $data["reset_info"] = empty($info['reset_info']) ? $orderTrade["out_trade_no"] : $info['reset_info'].",".$orderTrade["out_trade_no"];
            return $this->where($where)->update($data);
        }
        return false;
    }

    public function getList()
    {
        $where = "1=1 ";
		$sort = "id DESC";
		if(isset($_GET['sum'])){

		$where = ($_GET['sum']!='')?$where." AND cm_ownpay_order.orderPrice='".$_GET['sum']."'":$where;
		$where = ($_GET['status']!='')?$where." AND cm_ownpay_order.status=".$_GET['status']:$where;
		$where = ($_GET['out_trade_no']!='')?$where." AND (cm_orders.out_trade_no='".$_GET['out_trade_no']."' OR cm_ownpay_order.reset_info like '%".$_GET['out_trade_no']."%')":$where;
		$where = ($_GET['orderNum'])?$where." AND cm_ownpay_order.orderNum='".$_GET['orderNum']."'":$where;
        $where = ($_GET['storeid'])?$where." AND cm_ownpay_order.storeid='".$_GET['storeid']."'":$where;
		$sort = ($_GET['sort']!='')?"cm_ownpay_order.".$_GET['sort']." DESC":$sort;

        }

        if(!empty($_GET['status']) && $_GET['status']==self::STAT_UNPAY)
        {
            $where = $where." AND cm_ownpay_order.addTime >".(request()->time()-(4*23*60*60+30*60));
        }
       // $where = $where." AND cm_ownpay_order.addTime >".(request()->time()-24*60*60);
        $where = $where." AND cm_ownpay_order.status !=". self::STAT_DELETE;
		$fileds = [
            $this->getTable("").".*",
            $this->getTable("").".payTime as pay_time",
            $this->getTable("orders").".out_trade_no as o_num",
            $this->getTable("shop").".type",
        ];

        $join = [
            [
                $this->getTable("orders"),
                $this->getTable().'.out_trade_no = '. $this->getTable("orders").'.trade_no',
                'left'
            ],
            [
                $this->getTable("shop"),
                $this->getTable().'.storeid = '. $this->getTable("shop").'.id',
                'left'
            ]
        ];
        $datas  = $this->field($fileds)->where($where)->join($join)->order($sort)->paginate(50,false, ['query'=>request()->param() ]);
        return $datas;
    }

    public function getNextUnpayExpireVxOrder()
    {
        $time = request()->time();
        $expire = self::VX_PAY_LINK_EXPRIE_TIME-60;
        //所有可以支付的订单根据金额分类
        $where['status'] = self::STAT_UNPAY;
        $where['addTime'] = [">", $time - 23*60*60 + 30*60];

        $fileds = [
            "count(*) as number",
            "orderPrice"
        ];
        $lists =  $this->field($fileds)->where($where)->group("orderPrice")->select();
        $allGroups = [];
        foreach($lists as $g)
        {
            $allGroups[$g->orderPrice] = $g->number;
        }

        //每种金额有多少正在支付个

        $where['update_vx_time'] = [">", $time - $expire];

        $where['addTime'] = [">", $time - 23*60*60 + 30*60];
        $fileds = [
           "count(*) as number",
            "orderPrice"
        ];
        $list =  $this->field($fileds)->where($where)->group("orderPrice")->select();
        $already = [];
        foreach($list as $l)
        {
            $already[$l->orderPrice] = $l->number;
        }

        $money = $this->getKey($allGroups,$already);


        $where['update_vx_time'] = ["<", $time - $expire];
        $where['orderPrice'] = $money;
        $order =  $this->where($where)->find();
        return empty($order->orderNum) ? "" : $order->orderNum;
    }

    public function getKey($all, $already)
    {
        foreach ($all as $key =>$a)
        {
            if(empty($already[$key]))
            {
                $already[$key] = 0;
            }
        }

        asort($already);

        foreach ($already as $key =>$value)
        {
            if($already[$key] < $all[$key])
            {
                return $key;
            }
        }
    }
    public function updateVxLink($where ,$vx_qr_url)
    {
        $data['update_vx_time'] = request()->time();
        $data['vx_qr_url'] = $vx_qr_url;
        $this->where($where)->update($data);
    }












}