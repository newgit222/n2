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

namespace app\api\service\request;

use app\api\service\ApiRequest;
use think\Request;

/**
 * 网关检验抽象类
 *
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 * @package app\api\logic\gateway
 */
abstract class ApiCheck extends ApiRequest
{
    /**
     * 下一个check实体
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @var
     */
    private $nextCheckInstance;

    /**
     * 校验方法
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param Request $request
     * @return mixed
     */
    abstract public function doCheck(Request $request);

    /**
     * 设置责任链上的下一个对象
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param ApiCheck $check
     * @return ApiCheck
     */
    public function setNext(ApiCheck $check)
    {
        $this->nextCheckInstance = $check;
        return $check;
    }

    /**
     * 启动
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param Request $request
     */
    public function start(Request $request)
    {
        $this->doCheck($request);
        // 调用下一个对象
        if (! empty($this->nextCheckInstance)) {
            $this->nextCheckInstance->start($request);
        }
    }
}
