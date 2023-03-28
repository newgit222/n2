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
use think\Db;
use think\Exception;
use think\Log;
use think\Validate;

class User extends BaseLogic
{
    /**
     * 获取商户列表
     *
     * @param array $where
     * @param string $fieldaddUser
     * @param string $order
     * @param int $paginate
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserList($where = [], $field = '*', $order = '', $paginate = 20)
    {
        $this->modelUser->limit = !$paginate;
        $this->modelUserAccount->alias('a');

        $join = [
            ['admin_group b', 'a.uid = b.id'],
        ];

        $this->modelUserAccount->join = $join;
        return $this->modelUser->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取商户认证列表
     *
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $paginate
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserAuthList($where = [], $field = '*', $order = '', $paginate = 20)
    {
        $this->modelUserAuth->limit = !$paginate;
        return $this->modelUserAuth->getList($where, $field, $order, $paginate);
    }

    /**
     * 获取用户总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserCount($where = [])
    {
        return $this->modelUser->getCount($where);
    }

    /**
     * 获取用户认证数据总数
     *
     * @param $where
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserAuthCount($where = [])
    {
        return $this->modelUserAuth->getCount($where);
    }

    /**
     * 获取商户信息详情
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserInfo($where = [], $field = true)
    {
        return $this->modelUser->getInfo($where, $field);
    }

    /**
     * 获取认证信息
     *
     * @param array $where
     * @param bool $field
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserAuthInfo($where = [], $field = true)
    {
        return $this->modelUserAuth->getInfo($where, $field);
    }

    /**
     * 获取费率详情
     *
     * @param array $where
     * @param bool $field
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserProfitInfo($where = [], $field = true)
    {
        return $this->modelUserProfit->getInfo($where, $field);
    }


    /**
     * @param array $where
     * @param bool $field
     * @return mixed
     *  获取代付费率
     */
    public function getUserDaifuProfitInfo($where = [], $field = true)
    {
        return $this->modelUserDaifuprofit->getInfo($where, $field);
    }


    /**
     * 获取费率列表
     *
     * @param array $where
     * @param string $field
     * @param string $order
     * @param bool $paginate
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getUserProfitList($where = [], $field = '*', $order = '', $paginate = false)
    {

        return $this->modelUserProfit->getList($where, $field, $order, $paginate);
    }

    /**
     * 添加商户指定渠道
     */
    public function addAppoint($data)
    {

        if (!isset($data['uid']) || !$data['uid']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择商户'];
        }
        if (!isset($data['pay_code_id']) || !$data['pay_code_id']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择支付产品'];
        }
        if (!isset($data['cnl_id']) || !$data['cnl_id']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择渠道'];
        }
        //判断是否存在
        $appoint = $this->modelUserPayCodeAppoint->where(['uid' => $data['uid'], 'pay_code_id' => $data['pay_code_id']])->find();
        if ($appoint) {
            return ['code' => CodeEnum::ERROR, 'msg' => '同一个商户和支付产品只能指定一个渠道'];
        }

        $data['createtime'] = time();
        $this->modelUserPayCodeAppoint->setInfo($data);
        return ['code' => CodeEnum::SUCCESS, 'msg' => '添加成功'];
    }


    /**
     * 添加商户指定渠道
     */
    public function editAppoint($data)
    {

        if (!isset($data['uid']) || !$data['uid']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择商户'];
        }
        if (!isset($data['pay_code_id']) || !$data['pay_code_id']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择支付产品'];
        }
        if (!isset($data['cnl_id']) || !$data['cnl_id']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '请选择渠道'];
        }
        if (!isset($data['appoint_id']) || !$data['appoint_id']) {
            return ['code' => CodeEnum::ERROR, 'msg' => '非法操作'];
        }

        //判断是否存在
        $appoint = $this->modelUserPayCodeAppoint->where(['uid' => $data['uid'], 'pay_code_id' => $data['pay_code_id'], 'appoint_id' => ['not in', $data['appoint_id']]])->find();
        if ($appoint) {
            return ['code' => CodeEnum::ERROR, 'msg' => '同一个商户和支付产品只能指定一个渠道'];
        }

        $this->modelUserPayCodeAppoint->setInfo($data);
        return ['code' => CodeEnum::SUCCESS, 'msg' => '添加成功'];
    }

    /**
     * 删除商户指定渠道
     */
    public function delAppoint($where)
    {
        $this->modelUserPayCodeAppoint->deleteInfo($where, true);
        return ['code' => CodeEnum::SUCCESS, 'msg' => '删除成功'];
    }


    /**
     * 添加一个商户
     * @param $data
     * @return array|string
     * @author 勇敢的小笨羊
     */
    public function addUser($data)
    {
        //TODO 数据验证
        $validate = $this->validateUserValidate->scene('add')->check($data);
        if (!$validate) {
            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateUserValidate->getError()];
        }
        //TODO 添加数据
        Db::startTrans();
        try {
            //密码
            $data['is_verify'] = 1;
            $data['password'] = data_md5_key($data['password']);
            $auth_ips = $data['auth_login_ips'];
            $auth_ips = explode(',', $auth_ips) ? explode(',', $auth_ips) : [];
            //验证ip
            foreach ($auth_ips as $ip) {
                $ip = trim($ip);
                if (empty($ip)) {
                    continue;
                }
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    return ['code' => CodeEnum::ERROR, 'msg' => 'ip格式填写错误'];
                }
            }


            //如果是跑分出码对应的码商的ids
            if (isset($data['pao_ms_ids'])) {
                $pao_ms_ids = $data['pao_ms_ids'];
                $pao_ms_ids = explode(',', $pao_ms_ids) ? explode(',', $pao_ms_ids) : [];
                //验证ip
                foreach ($pao_ms_ids as $ip) {
                    $ip = trim($ip);
                    if (empty($ip)) {
                        continue;
                    }
                }
            }

           // $data['mch_secret'] = md5($data['uid']);
            //基本信息
            $user = $this->modelUser->setInfo($data);

            //更新商户的tg群id
            $this->modelUser->setInfo(['mch_secret'=>md5($user)],['uid' => $user]);


            //账户记录
            $this->modelUserAccount->setInfo(['uid' => $user]);
            //资金记录
            $this->modelBalance->setInfo(['uid' => $user]);
            //生成API记录
            $this->modelApi->setInfo([
                'uid' => $user,
                // 'domain' =>  $data['siteurl'],
                //'sitename' =>  $data['sitename'],
                'key' => md5(request()->time() . "password"),
            ]);

            //加入邮件队列
            $jobData = $this->getUserInfo(['uid' => $user], 'uid,account,username');

            //邮件场景
            $jobData['scene'] = 'register';
            $this->logicQueue->pushJobDataToQueue('AutoEmailWork', $jobData, 'AutoEmailWork');


            action_log('新增', '新增商户。UID:' . $user);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '添加商户成功', 'data' => ['uid' => $user]];
        } catch (\Exception $ex) {
            dd($ex->getMessage());
            Db::rollback();
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }


    /**
     * 编辑商户
     *
     * @param $data
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function editUser($data)
    {

        //TODO  验证数据
//        $validate = $this->validateUserValidate->scene('edit')->check($data);
//
//        if (!$validate) {
//
//            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateUserValidate->getError()];
//        }
        //TODO 修改数据
        Db::startTrans();
        try {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = data_md5_key($data['password']);
            }
            if (empty($data['auth_code'])) {
                unset($data['auth_code']);
            } else {
                $data['auth_code'] = data_md5($data['auth_code']);
            }

            if (isset($data['auth_login_ips'])) {
                $auth_ips = $data['auth_login_ips'];
                $auth_ips = explode(',', $auth_ips) ? explode(',', $auth_ips) : [];
                //验证ip
                foreach ($auth_ips as $ip) {
                    $ip = trim($ip);
                    if (empty($ip)) {
                        continue;
                    }
                    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                        return ['code' => CodeEnum::ERROR, 'msg' => 'ip格式填写错误'];
                    }
                }
            }


            //如果是跑分出码对应的码商的ids
            if (isset($data['pao_ms_ids'])) {
                $pao_ms_ids = $data['pao_ms_ids'];
                $pao_ms_ids = explode(',', $pao_ms_ids) ? explode(',', $pao_ms_ids) : [];
                //验证ip
                foreach ($pao_ms_ids as $ip) {
                    $ip = trim($ip);
                    if (empty($ip)) {
                        continue;
                    }
                }
            }
            $data['mch_secret'] = md5($data['uid']);

            $this->modelUser->setInfo($data);

            action_log('修改', '修改商户信息。UID:' . $data['uid']);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '编辑成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * 认证信息保存
     *
     * @param $data
     *
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function saveUserAuth($data)
    {
        //TODO  验证数据
        $validate = $this->validateUserAuth->check($data);

        if (!$validate) {

            return ['code' => CodeEnum::ERROR, 'msg' => $this->validateUserAuth->getError()];
        }
        //TODO 修改数据
        Db::startTrans();
        try {

            if (!empty($data['card'])) $data['card'] = json_encode(array_values($data['card']));
            //修改数据
            $this->modelUserAuth->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action, $action . '个人信息认证。ID:' . $data['id']);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => $action . '个人认证信息成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }


    /**
     * 分润配置
     *
     * @param $data
     *
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function saveUserProfit($data)
    {
        //TODO 修改数据
        Db::startTrans();
        try {

            $this->modelUserProfit->setList($data, true);  //强制覆盖

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '分润配置成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }


    public function saveUserDaifuProfit($data)
    {
        //TODO 修改数据
        Db::startTrans();
        try {
            if (isset($data['uid'])){
                $daifuprofit =  $this->modelUserDaifuprofit->where('uid', $data['uid'])->find();
                $daifuprofit && $data['id'] = $daifuprofit['id'];
            }

            $this->modelUserDaifuprofit->setInfo($data);

            Db::commit();
            return ['code' => CodeEnum::SUCCESS, 'msg' => '费率配置成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * 修改密码
     *
     * @param $data
     *
     * @return array
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function changePwd($data)
    {
        //TODO  验证数据
        $validate = $this->validatePassword->check($data);

        if (!$validate) {

            return ['code' => CodeEnum::ERROR, 'msg' => $this->validatePassword->getError()];
        }

        //查询用户
        $user = $this->getUserInfo(['uid' => is_login()], 'password');

        //验证原密码
        if ($user && data_md5_key($data['oldpassword']) == $user['password']) {

            $result = $this->setUserValue(['uid' => is_login()], 'password', data_md5_key($data['password']));

            action_log('修改', '修改密码');

            return $result && !empty($result) ? ['code' => CodeEnum::SUCCESS, 'msg' => '修改密码成功']
                : ['code' => CodeEnum::ERROR, 'msg' => '修改失败'];
        } else {
            return ['code' => CodeEnum::ERROR, 'msg' => '原密码不正确'];
        }
    }

    /**
     * 删除商户
     * @param array $where
     * @return array
     * @author 勇敢的小笨羊
     */
    public function delUser($where = [])
    {
        Db::startTrans();
        try {
            $this->modelUser->deleteInfo($where, true);
            $this->modelUserAccount->deleteInfo($where, true);
            $this->modelBalance->deleteInfo($where, true);
            $this->modelApi->deleteInfo($where, true);

            Db::commit();

            action_log('删除', '删除商户' . $where['uid']);

            return ['code' => CodeEnum::SUCCESS, 'msg' => '删除商户成功'];
        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }


    /**
     * 设置信息
     *
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function setUserValue($where = [], $field = '', $value = '')
    {
        return $this->modelUser->setFieldValue($where, $field, $value);
    }


    /*
     *用户支付产品
     * @param array $where
     * @param $field
     * @return mixed
     */
    public function userPayCode($where = [], $field = true)
    {
        return $this->modelUserPayCode->getInfo($where, $field);
    }


    public function userPayCodes($where = [], $field = true, $order = 'create_time desc', $paginate = 15)
    {
        $this->modelUserPayCode->alias('a');
        $join = [
            ['pay_code b', 'a.co_id = b.id'],
        ];
        $this->modelUserPayCode->join = $join;

        return $this->modelUserPayCode->getList($where, $field, $order, $paginate);
    }


    /**
     *  用户正对这个code费率
     */
    public function userCodeProfit($co_id, $uid)
    {
        $urate = $this->modelPayAccount->alias('a')
            ->where('find_in_set(' . $co_id . ',co_id)')
            ->where(['a.status' => 1])
            ->where(['b.uid' => $uid])
            ->orderRaw('rand()')
            ->join('user_profit b', 'a.id=b.cnl_id', 'left')
            ->value('b.urate');
        return $urate;
    }


    /*
     *处理用户的支付产品
     * @param $uid
     * @param $data
     */
    public function doUserPayCodes($uid, $data)
    {

        Db::startTrans();
        try {

            $userCodeModel = $this->modelUserPayCode;
            $userCodeModel->where(['uid' => $uid])->delete();
            foreach ($data as $k => $v) {
                $data[$k]['status'] = isset($v['status']) ? $v['status'] : 0;
            }

            $ret = $userCodeModel->insertAll($data);
            if ($ret) {
                Db::commit();
                return ['code' => CodeEnum::SUCCESS, 'msg' => '操作成功'];
            }
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '操作失败'];

        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * @param $col_id
     * @param int $status
     * 设置商户支付产品状态
     */
    public function setUserCodeStatus($col_id, $status = 0)
    {

        $this->modelUserPayCode->startTrans();
        try {
            $this->modelUserPayCode->where(['co_id' => $col_id])->delete();
            //获取所有的商户
            $data = $this->logicUser->getUserList([], true, 'create_time desc', false);
            $install = [];
            foreach ($data as $k => $v) {
                $install[] = [
                    'uid' => $v['uid'],
                    'co_id' => $col_id,
                    'status' => $status,
                    'create_time' => time()
                ];
            }
            $result = $this->modelUserPayCode->insertAll($install);
            if (!$result) {
                throw new Exception('操作失败');
            }
            $this->modelUserPayCode->commit();
        } catch (Exception $e) {
            return ['code' => CodeEnum::ERROR, 'msg' => $e->getMessage()];
        }


        return ['code' => CodeEnum::SUCCESS, 'msg' => '操作成功'];
    }


    /*
     *处理支付产品对应的用户
     * @param $uid
     * @param $data
     */
    public function doPayCodesUser($co_id, $data)
    {
        Db::startTrans();
        try {

            $userCodeModel = $this->modelUserPayCode;
            $userCodeModel->where(['co_id' => $co_id])->delete();
            foreach ($data as $k => $v) {
                $data[$k]['status'] = isset($v['status']) ? $v['status'] : 0;
            }
            $ret = $userCodeModel->insertAll($data);
            if ($ret) {
                Db::commit();
                return ['code' => CodeEnum::SUCCESS, 'msg' => '操作成功'];
            }
            Db::rollback();
            return ['code' => CodeEnum::ERROR, 'msg' => '操作失败'];

        } catch (\Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            return ['code' => CodeEnum::ERROR, config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }


    /**
     * @param $app_key
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function PadminUsers($padmin_id)
    {
        /*   $where['status']=  $this->modelUserPadmin::BLING_STATUS;
           if($padmin_id!=config('paofen_super_admin_id')){

                $where['p_admin_id']  = $padmin_id;
           }
           $userPadmins=  $this->modelUserPadmin->where($where)->select();
           return $userPadmins;*/
    }


    public function bindTgGroupIdtoUserBySercert($secret, $groupId)
    {
        $this->modelUser->where(['mch_secret' => $secret])->setField('tg_group_id', $groupId);
    }


    /**
     * 获取码商关联的商户
     * @param $msId
     */
    public function getUsersByMsId($msId)
    {

        $fields = 'a.uid,a.username,b.enable';
        return $this->modelUser->alias('a')
            ->join('balance b', 'a.uid=b.uid', 'left')
            ->where('find_in_set(' . $msId . ',pao_ms_ids)')
            ->field($fields)
            ->select();
    }


}
