<?php


namespace app\common\model;


/***
 * 码商二维码模型
 * Class EwmPayCode
 * @package app\common\model
 */
class EwmPayCode extends BaseModel
{

    protected $autoWriteTimestamp = false;



    //打开中
    const STATUS_ON = 0;

    //关闭中
    const STATUS_OFF = 1;

    //支付中
    const STATUS_PAYING = 1;

    //空闲中　
    const STATUS_NOPAYING = 0;

    //关闭中
    const STATUS_CLOSE = 2;

    //二维码生成订单最大个数
    const LIMIT_NUM = 200;

    //每个金额最大个数
    const MONEY_LIMIT_NUM = 20;

    //每个码最大收款额
    const CODE_MONEY_LIMIT = 10000;


    /**
     * @param $money
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *  获取可以使用的二维码
     */
    function getAviableCodeV2($money = null, $type = 3, $admin_id = 1)
    {
        //二维码类型
//        $where["code.type"] = $type;

        //二维码激活
        $where["code.status"] = self::STATUS_YES;

        //二维码没有被锁定
        $where["code.is_lock"] = self::STATUS_NO;

        //二维码没被删除
        $where["code.is_delete"] = self::STATUS_NO;

        //余额足够
        if ($money) {
            $where["u.money"] = array('gt', $money);
        }

//        $where['u.add_admin_id'] = $admin_id;
        //用户正常开工
        $where["u.status"] = self::STATUS_YES;

        //用户工作状态
        $where["u.work_status"] = self::STATUS_YES;

        $order = "code.order_today_all ASC";
        //选内容
        $fileds = [
            "code.*",
        ];
        $this->join('cm_ms u', "u.userid=code.ms_id", "LEFT");
        $data = $this->alias('code')->field($fileds)->where($where)->order($order)->select();
         return $data;
    }


    /**
     * 获取一个最优使用的二维码
     * @param $money
     * @param null $type
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getAviableCode($money, $type, $member_id)
    {

        $EwmOrderModel = new EwmOrder();
		//EwmOrder
        //判断code类型 如果是银行卡的话 需要排除半个小时以外的订单金额
       /* $wheres['add_time'] = ['gt', (time() - 1800)];
        $where['code_id'] = ['gt', '0'];
        $where["order_pay_price"] = $money;
        $fileds = [
            "code_id",
        ];

        $codes = $GemapayOrderModel
            ->field($fileds)
            ->where(' code_id > 0 and order_pay_price = "' . $money . '" and status = "' . $GemapayOrderModel::WAITEPAY . '" or  add_time > "' . (time() - 120) . '" and  code_id > 0 and order_pay_price = "' . $money . '"   ')
            ->select();
        unset($where);
        if (!empty($codes)) {
            $ids = [];
            foreach ($codes as $code) {
                if (!empty($code['code_id'])) {
                    $ids[] = $code['code_id'];
                }
            }
            $ids = array_unique($ids);
            if (!empty($ids)) {
                $where["code.id"] = array("not in", $ids);
            }
        }*/
		
        if($type == 4)
		{
            $code_ids =[];
			$codes = $EwmOrderModel
            ->where(' code_id > 0 and order_price = "' . $money . '" and status = "' . $EwmOrderModel::WAITEPAY . '" and   code_type= 4 and member_id='.$member_id)
            ->select();
			foreach($codes as $code)
			{
				$code_ids[] = $code['code_id']; 
			}
			
			 $ids = array_unique($code_ids);
			 if (!empty($ids)) {
                $where["code.id"] = array("not in", $ids);
            } 
		}
		//$where["code.id"] = array("not in", $ids);
        //二维码类型

        //二维码激活
        $where["code.status"] = self::STATUS_YES;

        //二维码没有被锁定
        $where["code.is_lock"] = self::STATUS_NO;

        //二维码没被删除
        $where["code.is_delete"] = self::STATUS_NO;
	 if($type==4)
         {
           $where["code.code_type"] = $type;
          }	
	//	$where["code.code_type"] = $type;

        //余额足够
        $where["u.userid"] = $member_id;
//var_dump($where);die();
        
       $where["u.money"] = array('gt', $money);
        
        //用户正常开工
        $where["u.status"] = self::STATUS_YES;

        //用户工作状态
        $where["u.work_status"] = self::STATUS_YES;
        $order = 'id asc';

/*        if (time() % 100 > 10) {
            $order = "order_today_all ASC, last_online_time desc";
        } else {
            $order = "last_online_time desc";
        }*/
//        $where['b.id'] = ['null', ''];
        //选内容
        $fileds = [
            "code.*",
        ];

        $this->join('cm_ms u', "u.userid=code.ms_id", "LEFT");
//        $this->join('cm_ewm_block_ip b', "b.ms_id=code.ms_id and b.block_visite_ip ='" . get_userip() ."'", "LEFT");

        $data = $this->alias('code')->field($fileds)->where($where)->order($order)->select();
       if($type==4)
        {
        //  echo $this->alias('code')->getLastSql();
          // echo 3;die();
        }
		//去掉等于4的
        return $data;
    }



    /**
     * 增加ｃｏｄｅ支付个数
     * @param $id
     * @return false|int
     */
    public function incTodayOrder($id)
    {
        $where = [
            'id' => $id
        ];
        $ret   = $this->where($where)->setInc("order_today_all");
        return $ret;
    }

}
