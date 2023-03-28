<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2019/12/2
 * Time: 20:32
 */

namespace app\ms\controller;

//登录
use app\admin\model\UserModel;
use app\common\library\enum\CodeEnum;
use think\captcha\Captcha;
use think\Controller;

class Login extends Controller
{

    public function index()
    {
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
	    if(ctype_alnum($account)==false){
			$this->error('密码错误');
             }
            $password = $this->request->post('password');
            $code = $this->request->post('code');
            $verfy_code = $this->request->post('verfy_code');
            $IndexLogic = new \app\ms\Logic\Index();
            // 验证用户名密码是否正确
            $res = $IndexLogic->login($account, $password, null, false, 'agent', $code, $verfy_code);
            if ($res['code'] != CodeEnum::SUCCESS) {
                $this->error($res['msg']);
            }
            $this->success($res['msg'], url('index/index'));
        }
        return $this->fetch();
    }


    /**
     * 生成验证码
     */
    public function verfy_img()
    {
        $captcha = new Captcha();
        $captcha->fontSize = 20;
        $captcha->length = 4;
        $captcha->useNoise = false;
        return $captcha->entry('agent_login');
    }


    public function out()
    {
        session('agent_id', null);
        $this->redirect(url('index'));
//        $this->success('退出成功');
    }




}
