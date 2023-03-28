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

namespace app\admin\controller;

use app\common\controller\Common;
use think\Request;

class Login extends Common
{
    /**
     * 登录首页
     *
     * @return mixed
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function index(Request $request)
    {
        //登录检测
        is_admin_login() && $this->redirect(url('index/index'));

        //读取配置
        $index_view_path = \app\common\model\Config::where(['name' => 'index_view_path'])->find()->toArray();
        $view = $index_view_path['value'] == 'view1' ? 'baisha' : 'index';
        return $this->fetch($view);
    }

    /**
     * 登录处理
     *
     * @param string $username
     * @param string $password
     * @param string $vercode
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function login($username = '', $password = '', $vercode = '', $google_code = '')
    {
      
        $this->result($this->logicLogin->dologin($username, $password, $vercode, $google_code));
    }

    /**
     * 注销登录
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function logout()
    {
        $this->result($this->logicLogin->logout());
    }

    /**
     * 清理缓存
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function clearCache()
    {
        $this->result($this->logicLogin->clearCache());
    }
}
