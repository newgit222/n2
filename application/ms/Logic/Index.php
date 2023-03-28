<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2019/12/2
 * Time: 21:19
 */

namespace app\ms\Logic;

use app\common\library\enum\CodeEnum;
use app\common\logic\BaseLogic;
use think\captcha\Captcha;
use think\Db;

class Index extends BaseLogic
{

    public function check_verify($code, $id = '')
    {

        $captcha = new Captcha();

        return $captcha->check($code, $id);
    }


    /**
     * 用户登录
     *
     */
    public function login($account, $password, $map = null, $freshToken = true, $type = 'index', $code = null, $verfy_code = null)
    {
        //去除前后空格
        $account = trim($account);
        if (!isset($account) || empty($account)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '账号不能为空'];
        }

        if (!ctype_alnum($account)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '账号输入不合法'];
        }
        if (!isset($password) || empty($password)) {
            return ['code' => CodeEnum::ERROR, 'msg' => '密码不能为空'];
        }

        //检测图形验证码

        if ($type == 'agent') {
            if ($this->check_verify($verfy_code, 'agent_login') == false) {
                return ['code' => CodeEnum::ERROR, 'msg' => '图形验证码错误'];
            }
        }
        $UserModel = $this->modelMs;

        $map['mobile|username'] = array('eq', $account, 'or');
        $map['status'] = '1';
        $user_info = $UserModel->where($map)->find();
        if (!$user_info) {
            return ['code' => CodeEnum::ERROR, 'msg' => '账号或密码错误'];
        } elseif ($user_info['status'] == 0) {
            return ['code' => CodeEnum::ERROR, 'msg' => '您的账号已冻结，请联系管理员!'];
        } else {
            //验证码是否开启
            if ($user_info['google_status'] == 1) {
                if (!$code) {
                    return ['code' => '505', 'msg' => '请输入google验证码'];
                }
                //验证code
                $googleAuthLogic = new \app\common\logic\GoogleAuth();
                if ($googleAuthLogic->checkGoogleCode($user_info['google_secretkey'], $code) == false) {
                    return ['code' => '505', 'msg' => 'google验证码不正确'];
                }
            }

            if (pwdMd52($password, $user_info['login_salt']) != $user_info['login_pwd']) {
//                $result = loginCountLog('user_' . $account);
//                if ($result['code'] != '1') {
//                    $User = $UserModel->where(['account' => $account, 'status' => CodeEnum::SUCCESS])->find();
//                    if ($User) {
//                        $UserLogic = new Ms();
//                        $UserLogic->occurError($User['userid'], $result['msg']);
//                    }
//                }
                return ['code' => CodeEnum::ERROR, 'msg' => '账号或密码错误'];
            } else {
                if ($freshToken) {
                    $data['token'] = md5(time() . "password");
                    $UserModel->where($map)->update($data);
                } else {
                    $data['token'] = $user_info['token'];
                }

                //记录登录日志
//                $agentLogic = new \app\agent\Logic\IndexLogic();
//                $agentLogic->agentLog($user_info['userid']);

                \think\Cache::set('user_' . $account, '0');
                if ($type == 'index') {

                    $info['userid'] = $user_info['userid'];
                    $info['add_admin_id'] = $user_info['add_admin_id'];
                    $info['have_screrity'] = empty($user_info['security_pwd']) ? 0 : 1;
                    $data['user_info'] = $info;
                    return ['code' => CodeEnum::SUCCESS, 'msg' => '登录成功', 'data' => $data];
                } else {
                    //设置session
                    session('agent_id', $user_info['userid']);
                    return ['code' => CodeEnum::SUCCESS, 'msg' => '登录成功'];
                }
            }
        }
    }


    /**
     * 记录代理登录日志
     */
    public function agentLog($agent_id)
    {

        $ip = get_userip();
        $insert = [
            'agent_id' => $agent_id,
            'ip' => $ip,
            'createtime' => time(),
            'area' => ''//$area
        ];

        $result = AgentLogModel::insert($insert);
        if (!$result) {
            return ['code' => CodeEnum::ERROR, 'msg' => '错误请重试'];
        }
        return ['code' => CodeEnum::SUCCESS, 'msg' => '成功'];
    }
}