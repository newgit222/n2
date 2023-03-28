<?php


namespace app\common\logic;


use app\admin\model\ShopOrderModel;
use app\agent\model\ShopAccountModel;
use app\common\library\enum\CodeEnum;
use app\common\model\EwmPayCode;
use app\common\model\GemapayOrderModel;
use app\ms\Logic\SecurityLogic;
use think\Db;

/**
 * 二维码
 * Class EwmPayCodes
 * @package app\common\logic
 */
class EwmPayCodes extends BaseLogic
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
    public function getCodeList($where = [], $field = true, $order = 'add_time desc', $paginate = 1)
    {

        $this->modelEwmPayCode->alias('a');
        $this->modelEwmPayCode->limit = !$paginate;

        $join = [
            ['ms b', 'a.ms_id = b.userid', 'left'],
        ];
        $this->modelEwmPayCode->join = $join;
        return $this->modelEwmPayCode->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取单总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getCodeCount($where = [])
    {
        return $this->modelEwmPayCode->alias('a')
            ->where($where)
            ->join('ms b', 'a.ms_id = b.userid', 'left')
            ->count();
    }


}
