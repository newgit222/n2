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


namespace app\ms\controller;

use app\common\library\enum\CodeEnum;
use app\common\library\enum\UserStatusEnum;
use app\common\model\UserPayCode;
use app\common\model\UserPayCodeAppoint;
use think\Request;

class User extends Base
{
    /**
     * 码商所能代付的商户
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function index(Request $request)
    {
        $msId = $this->agent_id;
        $users =  $this->logicUser->getUsersByMsId($msId);
        $this->assign('users', $users);
        $device = isMobile() ? 'index_mobile' : 'index';
        return $this->fetch($device);
        return $this->fetch();
    }


    /**
     * 码商给商户增加/减少余额
     */
    public function changeBalance(Request $request)
    {

        // post 是提交数据
        $uid = $this->request->param('uid/d');
        if ($this->request->isPost()) {

            $setDec = intval($this->request->param('change_type'));
            $amount = $this->request->param('amount');
            $field = intval($this->request->param('change_money_type'));
            $remarks = addslashes( htmlspecialchars($this->request->param('remarks/s')));

            $ret = $this->logicBalanceChange->creatBalanceChange($uid, $amount, $remarks, $field, $setDec, 1);


            /**  2020-2-20 update  **/
            //如果操作的是增加冻结金额
            if ($field == 'disable') {
                //增加对应余额
                if (!$setDec) {
                    $result = $this->logicBalanceChange->creatBalanceChange($uid, $amount, $remarks, 'enable', !$setDec, 1);
                    if (!$result) {
                        return false;
                    }
                }

            }
            $code = $ret ? CodeEnum::SUCCESS : CodeEnum::ERROR;
            $msg = $ret ? "操作成功" : "操作失败";
            $this->result($code, $msg);
        }
        //商户信息
        $balance =  $this->logicBalance->getBalanceInfo(['uid'=>$uid]);
        $this->assign('balance',$balance);
        return $this->fetch();
    }


}
