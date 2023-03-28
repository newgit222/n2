<?php

namespace app\common\logic;

use app\admin\model\UserModel;
use app\agent\model\AgentLogModel;
use app\api\model\BankStatementModel;
use app\common\library\enum\CodeEnum;
use app\common\model\EwmPayCode;
use app\common\model\GemapayCodeModel;
use app\ms\Logic\SecurityLogic;
use app\pay\controller\PayController;
use think\Db;

class CodeLogic extends BaseLogic
{

    public function getQueenPostion($codeId, $type, $admin_id)
    {
        $Queue = new Queuev1Logic();
        //获取队列
        $code_queue = $Queue->get($type, $admin_id);
        //如果不在队列中 返回0

        if (!$code_queue || !in_array($codeId, $code_queue)) {
            return '2147483647';
        }
        //在队列中 返回键+1
        return $Queue->kget($codeId, $type, $admin_id) + 1;

    }

      public function addQunQRcode($insertData)
    {
        //验证安全码
        $UserModel = $this->modelMs;
        $userId = session('agent_id');
        $userInfo = $UserModel->find($userId);
        //是否设置费率
        if (empty($userInfo['bank_rate'])) {
//            return ['code' => CodeEnum::ERROR, 'msg' => '未找到银行卡费率'];
        }

        $SecurityLogic = new SecurityLogic();
        $res = $SecurityLogic->checkSecurityByUserId($userId, $insertData['security']);
        if ($res['code'] == CodeEnum::ERROR) {
            return $res;
        }

        $data['ms_id'] = $userId;
      //  $data['bank_name'] = $insertData['bank_name'];
        $data['account_name'] = $insertData['account_name'];
       // $data['account_number'] = $insertData['account_number'];
        $data['bonus_points'] = $userInfo['bank_rate'];
        $data['user_name'] = $userInfo['username'];
        $data['create_time'] = time();
        $data['updated_at'] = time();
        $data['status'] = $insertData['is_active'];
		$data['code_type'] = 4;
		$data['image_url'] = $insertData['file_url'];
        $result = $this->modelEwmPayCode->insertGetId($data);
        if (!$result) {
            return ['code' => CodeEnum::ERROR, 'msg' => '保存失败,请一会儿再试'];
        }
        $is_active = $insertData['is_active'];
        //如果激活 添加到队列
        if ($is_active && $userInfo['work_status'] == 1) {
            $QueueLogic = new Queuev1Logic();
            $userInfo['add_admin_id'] = 1;//写死
            $QueueLogic->radd($result, 3, $userInfo['add_admin_id']);

        }
        return ['code' => CodeEnum::SUCCESS, 'msg' => '添加成功'];
    }


    /**
     * 添加二维码现在只要银行卡
     * @param $data
     * @return array
     */
    public function addQRcode($insertData)
    {
        //验证安全码
        $UserModel = $this->modelMs;
        $userId = session('agent_id');
        $userInfo = $UserModel->find($userId);
        //是否设置费率
        if (empty($userInfo['bank_rate'])) {
//            return ['code' => CodeEnum::ERROR, 'msg' => '未找到银行卡费率'];
        }

        $SecurityLogic = new SecurityLogic();
        $res = $SecurityLogic->checkSecurityByUserId($userId, $insertData['security']);
        if ($res['code'] == CodeEnum::ERROR) {
            return $res;
        }

        $data['ms_id'] = $userId;
        $data['bank_name'] = $insertData['bank_name'];
        $data['account_name'] = $insertData['account_name'];
        $data['account_number'] = $insertData['account_number'];
        $data['bonus_points'] = $userInfo['bank_rate'];
        $data['user_name'] = $userInfo['username'];
        $data['create_time'] = time();
        $data['updated_at'] = time();
        $data['status'] = $insertData['is_active'];
        $result = $this->modelEwmPayCode->insertGetId($data);
        if (!$result) {
            return ['code' => CodeEnum::ERROR, 'msg' => '保存失败,请一会儿再试'];
        }
        $is_active = $insertData['is_active'];
        //如果激活 添加到队列
        if ($is_active && $userInfo['work_status'] == 1) {
            $QueueLogic = new Queuev1Logic();
            $userInfo['add_admin_id'] = 1;//写死
            $QueueLogic->radd($result, 3, $userInfo['add_admin_id']);

        }
        return ['code' => CodeEnum::SUCCESS, 'msg' => '添加成功'];
    }


    /**
     * 激活二维码
     * @param $userId
     * @param $codeId
     * @return array
     */
    public function activeCode($userId, $codeId)
    {
        $UserModel = $this->modelMs;
        $userinfo = $UserModel->where(['userid' => $userId])->find();
        $GemapayCodeModel = $this->modelEwmPayCode;
        $where['id'] = $codeId;
        $where['ms_id'] = $userId;
        $data['status'] = $GemapayCodeModel::STATUS_YES;
        $data['forbidden_reason'] = '';
        $data['updated_at'] = time();
        $codeInfo = $GemapayCodeModel->where($where)->find();
        $ret = $GemapayCodeModel->where($where)->update($data);
        if ($ret !== false) {
            //判断用户是否在开工
            $user = $UserModel->where(['userid' => $userId])->find();
            if ($user['work_status'] == CodeEnum::SUCCESS) {
                $QueueLogic = new Queuev1Logic();
                $codeInfo['type'] = 3;
                $QueueLogic->radd($codeId, $codeInfo['type'], 1);
            }
            return ['code' => CodeEnum::SUCCESS, 'msg' => '激活成功！'];
        } else {
            return ['code' => CodeEnum::ERROR, 'msg' => '已经激活成功！'];
        }
    }



    /**
     * 冻结二维码
     * @param $userId
     * @param $codeId
     * @return array
     */
    public function disactiveCode($userId, $codeId)
    {
        $UserModel = $this->modelMs;
        $userinfo = $UserModel->where(['userid' => $userId])->find();
        $GemapayCodeModel = $this->modelEwmPayCode;
        $codeInfo = $GemapayCodeModel->find($codeId);
        $where['id'] = $codeId;
        $where['ms_id'] = $userId;
        $data['status'] = $GemapayCodeModel::STATUS_NO;
        $data['updated_at'] = time();
        $ret = $GemapayCodeModel->where( $where)->update($data);
        if ($ret !== false) {
            $QueueLogic = new Queuev1Logic();
            $QueueLogic->delete($codeId,3,1);
            return ['code' => CodeEnum::SUCCESS, 'msg' => '冻结成功！'];
        } else {
            return ['code' => CodeEnum::ERROR, 'msg' => '已经冻结成功！'];
        }
    }


}
