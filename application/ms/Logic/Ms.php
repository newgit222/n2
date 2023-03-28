<?php

namespace app\ms\logic;

use app\common\library\enum\CodeEnum;
use think\Request;
use app\common\logic\BaseLogic;

class Ms extends BaseLogic
{

    //验证登录密码是否正确
    public function check_pwd_one($value, $userId)
    {
        $where['userid'] = $userId;
        $u_info = $this->modelMs->where($where)->field('login_pwd,login_salt')->find();
        $salt = $u_info['login_salt'];
        $pwd = $u_info['login_pwd'];
        if ($pwd == pwdMd52($value, $salt))
        {
            return true;
        }
        else
        {
            return false;
        }
    }



    public function updateLoginPassword($userId, $oldPassword, $newPassword, $newRePassword)
    {
        if (empty($oldPassword))
        {
            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '请输入登录密码'];
        }

        if(strlen($newPassword) < 6|| strlen($newPassword) > 16)
        {
            return ['code' => CodeEnum::ERROR, 'msg' =>'密码必须大于6位,小于16位！' ];
        }

        //密码强度
//        if(judgepassword($newPassword)<4)
//        {
//            return ['code' => CodeEnum::ERROR, 'msg' =>'新密码强度不够！' ];
//
//        }


        if ($newPassword != $newRePassword) {
            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '两次输入登录密码不一致'];
        }

        $User = $this->modelMs;
       // $User->startTrans();
        //验证旧密码
        if (!$this->check_pwd_one($oldPassword, $userId))
        {
            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '旧登录密码错误'];
        }

        //=============登录密码加密==============
        if ($newPassword)
        {
            $salt = substr(md5(time()), 0, 3);
            $data['login_salt'] = $salt;
            $data['login_pwd'] = pwdMd52($newPassword, $salt);
        }

        $where['userid'] = $userId;
        $res = $User->where($where)->update($data);
        if($res)
        {
       //     $User->commit();
//            Cache::set('user_LoginPassword_'.$userId,'0');
            return ['code' => \app\common\library\enum\CodeEnum::SUCCESS, 'msg' => '修改成功'];
        }
        else
        {
         //   $User->rollback();
            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '修改失败'];
        }
    }





    /*
     *检测用户访问ip
     * @param $user_id
     */
    public function checkUserVisiteIp($user_id)
    {

        $AgentLogModel = new AgentLogModel();
        $userLoginInfo = $AgentLogModel->order(['createtime'=>'desc'])->where(['agent_id'=>$user_id])->find();
        if(empty($userLoginInfo))
        {
            return ['code'=>CodeEnum::SUCCESS,'msg'=>'success',];
        }
        if($userLoginInfo['ip'] != clientIp())
        {
            return ['code'=>CodeEnum::ERROR,'msg'=>'访问IP异常'];
        }
        //用户的访问ip是否白名单里面

        return ['code'=>CodeEnum::SUCCESS,'msg'=>'success'];
    }


    /*
     *判断用户是否在白名单内
     *
     */
    public function checkUserInWhiteIps($user_id)
    {
        $userAuthIps = $this->modelUser->where(['userid'=>$user_id])->value('auth_ips');
        $userAuthIps = explode(',',trim($userAuthIps));
        if(empty($userAuthIps))
        {
            return ['code'=>CodeEnum::ERROR,'msg'=>'请联系管路员添加IP白名单'];
        }
        if(!in_array(clientIp(),$userAuthIps))
        {
            return ['code'=>CodeEnum::ERROR,'msg'=>'检测到IP不再白名单内,请联系管理员添加IP白名单'];
        }

        return ['code'=>CodeEnum::SUCCESS,'msg'=>'success'];
    }


    /**
     * 登录错误
     */
    public function errorLogin($name,$msg,$type){
        switch ($type){
            case 'user':
                $UserModel = new UserModel();
                $user = $UserModel->where(['account'=>$name,'status'=>CodeEnum::SUCCESS])->find();
                if($user){
                    $this->occurError($user->userid,$msg);
                }
                break;
            case 'admin':
                $AdminModel = new AdminModel();
                $admin = $AdminModel->where(['username'=>$name,'status'=>CodeEnum::SUCCESS])->find();
                if($admin){
                    $this->occurError($admin->id,$msg,'admin');
                }
                break;
        }

    }


    /*
   *用户发生异常执行逻辑
   *$errorMsg 异常备注信息
   */
    public function occurError($uid,$errorMsg,$type='ms')
    {

        switch ($type){
            case 'user':
                //冻结用清除用户token     这里会影响监控 不清除token
                $UserModel = new UserModel();
                $user['status'] = 0;
                $user['blocking_reason'] = "【访问IP:".clientIp()."】".$errorMsg;
//                $user['token']  = '';
                $UserModel->updateInfo(['userid'=>$uid],$user);

                //代理下线
                session('agent_id',null);
                break;
            case 'admin':
                $AdminModel = new AdminModel();
                $AdminModel->where(['id'=>$uid])->update(['status'=>CodeEnum::ERROR,'blocking_reason'=>$errorMsg]);
                break;
        }

        //记录
        $this->exceptionLog($uid,$type,$errorMsg);

    }


    /**
     * 异常记录
     */
    public function exceptionLog($uid,$type,$msg){

        $param = Request::instance()->param();
        $insert = [
            'uid'   =>  $uid,
            'type'   =>  $type,
            'param'   =>  json_encode($param),
            'createtime'   =>  time(),
            'ip'   =>  get_userip(),
            'msg'   =>  $msg
        ];
        $ExceptionLogModel = new ExceptionLogModel();

        $ExceptionLogModel->insert($insert);


    }









    /**
     * 注册逻辑
     * @param array $param
     */
/*    public function  register($param=[]){
        $inventCode = $param['invent_code'];
        $mobile  = $param ['mobile'];
        $username  = $param ['username'];
        $login_pwd  = $param ['login_pwd'];

        $UserInviteSetting = new \app\index\model\UserInviteSettingModel();
        $setting = $UserInviteSetting->where(array('code'=>$inventCode))->find();

        if(empty($setting)){
            return ['status' => CodeEnum::ERROR, 'message' =>$inventCode. '!推荐人不存在！' ];
        }
        $salt = strrand(4);
        $UserModel =  new \app\index\model\UserModel();
        $cuser= $UserModel->where(array('account'=>$mobile))->find();
        $muser= $UserModel->where(array('mobile'=>$mobile))->find();
        if(!empty($cuser) || !empty($muser)){
            return ['status' => CodeEnum::ERROR, 'message' => '手机号已经被注册！'];
        }

        $userLogic = new UserLogic();
        $data['pid'] = $setting['user_id'];
        $data['gid'] = 0;
        $data['ggid'] = 0;
        $data['account'] = $mobile;
        $data['mobile'] = $mobile;
        $data['u_yqm'] = $inventCode;
        $data['add_admin_id'] = $setting['admin_id'];
        //以下值要有默认值
        $data['email'] = $data['security_pwd'] = $data['usercard'] = $data['security_salt'] = $data['rz_st'] = '';
        $data['tx_status'] = $data['userqq'] = $data['u_ztnum'] = $data['group_id'] = 0;
        $data['zsy'] = 0.00;
        $data['username'] = $username;
        $data['login_pwd'] = $userLogic->pwd_md5($login_pwd,$salt);
        $data['login_salt'] = $salt;
        $data['reg_date'] = time();
        $data['reg_ip'] = $userLogic->get_userip();
        $data['status'] = 1;
        //$data['user_credit']= 5;
        $data['use_grade']= 1;
        $data['tx_status']= 1;

        $ure_re = Db::name('user')->insert($data);
        if($ure_re)
        {
            return ['status' => CodeEnum::SUCCESS, 'message' => '注册成功！'];
        }
        else
        {
            return ['status' => CodeEnum::ERROR, 'message' => '网络错误！'];
        }

    }*/


    /**
     * 登录逻辑
     *
     */
    public function login($account, $password, $map = null)
    {
        //去除前后空格
        $account = addslashes(trim($account));
        
        if (!isset($account) || empty($account)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '账号不能为空'];
        }
        if (!isset($password) || empty($password)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '密码不能为空'];
        }

        $map['account'] =  $account;

        $UserModel =  new \app\index\model\UserModel();

        $user_info = $UserModel->where($map)->find();
        if (!$user_info)
        {
            return ['code' => CodeEnum::ERROR, 'msg' => '账号或密码错误'];
        }
        elseif ($user_info['status'] <= 0)
        {
            return ['code' => CodeEnum::ERROR, 'msg' => '您的账号已锁定，请联系管理员!'];
        }
        else
        {
            if (pwdMd5($password, $user_info['login_salt']) != $user_info['login_pwd']&&0)
            {
                return ['code' => CodeEnum::ERROR, 'msg' => '账号或密码错误！'];
            }
            else
            {
                $data['token'] = md5(time()."password");
                $UserModel->where($map)->update($data);

                return ['code' => CodeEnum::SUCCESS, 'msg' => '登录成功', 'data'=>$data];
            }
        }
    }






    /*****************************************************************************/


    public function getIndexInfo($userId)
    {
        $UserModel = new User();
        $ConfigModel = new ConfigModel();

        $userInfo = $UserModel->where(array('userid' => $userId))->find();
        $conf = $ConfigModel->field('value')->where(['name' => 'USER_NAV'])->find();

        $data['config'] = json_decode($conf['value'], true);
        $data['userinfo'] = $userInfo;
        return $data;
    }

//    public function updateLoginPassword($userId, $oldPassword, $newPassword, $newRePassword)
//    {
//        if (empty($oldPassword))
//        {
//            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '请输入登录密码'];
//        }
//
//        if(strlen($newPassword) < 6)
//        {
//            return ['code' => CodeEnum::ERROR, 'msg' =>'密码必须大于6位！' ];
//        }
//
//        //密码强度
//        if(judgepassword($newPassword)<4)
//        {
//            return ['code' => CodeEnum::ERROR, 'msg' =>'新密码强度不够！' ];
//
//        }
//
//
//        if ($newPassword != $newRePassword) {
//            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '两次输入登录密码不一致'];
//        }
//
//        $User = new UserModel();
//        $User->startTrans();
//
//        //验证旧密码
//        if (!$User->check_pwd_one($oldPassword, $userId))
//        {
//            return $this->errorLoginPassword($userId);
////            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '旧登录密码错误'];
//        }
//
//        //=============登录密码加密==============
//        if ($newPassword)
//        {
//            $salt = substr(md5(time()), 0, 3);
//            $data['login_salt'] = $salt;
//            $data['login_pwd'] = pwdMd5($newPassword, $salt);
//        }
//
//        $where['userid'] = $userId;
//        $res = $User->isUpdate(true,$where)->save($data);
//        if($res)
//        {
//            $User->commit();
//            Cache::set('user_LoginPassword_'.$userId,'0');
//            return ['code' => \app\common\library\enum\CodeEnum::SUCCESS, 'msg' => '修改成功'];
//        }
//        else
//        {
//            $User->rollback();
//            return ['code' => \app\common\library\enum\CodeEnum::ERROR, 'msg' => '修改失败'];
//        }
//    }

    /**
     * @param $userid
     */
    public function errorLoginPassword($userid){
        $result = loginCountLog('user_LoginPassword_'.$userid);
        if($result['code'] != '1' ){
            //冻结账户
            $this->occurError($userid,$result['msg']);

        }
        return ['code' => CodeEnum::ERROR, 'msg' => '旧密码错误'];
//        ajaxReturn('旧安全码错误','0');die();
    }



    public function  firstAgentCal($user_ids,$dayType='today')
    {
        $orderModel  = new \app\common\model\GemapayOrderModel();
        $fields ="count('id') as total_order_count,count(if(status=1,true,null)) as success_order_count,
        COALESCE(sum(order_price),0) as total_order_money,COALESCE(sum(if(status=1,order_price,0)),0) as succss_total_order_money";
        $orders  =$orderModel->field($fields)
            ->whereTime('add_time',$dayType)
            ->where(['gema_userid'=>['in',$user_ids]])
            ->find();
        return $orders;

    }




    /**
     * 通过用户id检测安全码
     * @param $userId
     * @param $security
     */
    public function checkSecurityByUserId($userId, $security)
    {
        if (empty($security))
        {
            return ['code' => CodeEnum::ERROR, 'msg' => '安全码不能为空'];
        }

        $UserModel = new UserModel();
        $userInfo = $UserModel->find($userId);
        if(empty($userInfo['security_pwd']))
        {
            return ['code' => CodeEnum::ERROR, 'msg' => '请先前往个人中心设置,设置安全码'];
        }

        if((pwdMd5($security,$userInfo['security_salt']) != $userInfo['security_pwd']))
        {
            return ['code' => CodeEnum::ERROR, 'msg' => '安全码错误'];
        }

        return ['code' => CodeEnum::SUCCESS, 'msg' => 'OK'];
    }



}
