<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/3
 * Time: 18:20
 */

namespace app\common\logic;


use app\common\model\EwmPayCode;
use app\common\model\GemapayCodeModel;

class Queuev1Logic
{

    private $code_queue;

    private $cache_name;

    private function init($type, $admin_id)
    {

        $this->cache_name = 'code_queue' . '_' . $type . '_' . $admin_id;
        $code_queue = cache($this->cache_name);
        //初始化队列
        if (!$code_queue || !json_decode($code_queue)) {
            $code_queue = $this->initQueue($type, $admin_id);
        }

        $this->code_queue = $code_queue ? json_decode($code_queue) : $code_queue;
    }

    /**
     * 队列初始化
     */
    public function initQueue($type, $admin_id)
    {
        $GemapayCode = new EwmPayCode();
        $codes = $GemapayCode->getAviableCodeV2(null, $type, $admin_id);
        if (empty($codes)) {
            return [];
        }
        foreach ($codes as $v) {
            $code_queue[] = $v['id'];
        }
        return json_encode($code_queue);
    }

    /**
     * 执行结束
     */
    private function over()
    {

        $this->code_queue = array_values($this->code_queue);
        cache($this->cache_name, json_encode($this->code_queue));
    }


    /**
     * 队列添加至末尾
     */
    private function rpush($data)
    {
        $this->code_queue[] = $data;
        $this->over();
    }

    /**
     * 队列添加至开头
     */
    private function lpush($data)
    {
        array_unshift($this->code_queue, $data);
        $this->over();
    }


    /**
     * 删除队列中的值
     */
    public function delete($data, $type, $admin_id)
    {
        $this->init($type, $admin_id);
        if (in_array($data, $this->code_queue)) {
            $this->code_queue = array_diff($this->code_queue, [$data]);
            $this->over();
        }
    }


    /**
     * 在数组中末尾添加 之前有的话不做任何操作
     */
    public function radd($data, $type, $admin_id)
    {
        $this->init($type, $admin_id);
        if (!in_array($data, $this->code_queue)) {
            $this->rpush($data);
        }
    }


    /**
     * 在数组中开头添加 之前有的话不做任何操作
     */
    public function ladd($data, $type, $admin_id)
    {
        $this->init($type, $admin_id);
        if (!in_array($data, $this->code_queue)) {
            $this->lpush($data);
        }
    }


    /**
     * 获取队列
     */
    public function get($type, $admin_id)
    {
        $this->init($type, $admin_id);
        $this->over();
        return $this->code_queue;
    }


    /**
     * 返回键
     */
    public function kget($data, $type, $admin_id)
    {
        $this->init($type, $admin_id);
        return array_search($data, $this->code_queue);
    }


    /**
     * 清除
     */
    public function clear($type, $admin_id)
    {
        $this->init($type, $admin_id);

        if (isset($this->code_queue)) {
            $this->code_queue = [];
            $this->over();
        }
    }

    /**
     * 获取第一个元素
     */
    public function lget($type, $admin_id)
    {
        $this->init($type, $admin_id);
        return reset($this->code_queue);
    }

    /**
     * @return mixed
     *  获取最后一个元素
     */
    public function rget($type, $admin_id)
    {
        $this->init($type, $admin_id);
        return end($this->code_queue);
    }

    /**
     * @return mixed
     *  获取第一个元素并删除
     */
    public function dlget($type, $admin_id)
    {
        $this->init($type, $admin_id);
        $data = array_shift($this->code_queue);
        $this->over();
        return $data;
    }

    /**
     * 获取最后一个元素并删除
     */
    public function drget($type, $admin_id)
    {
        $this->init($type, $admin_id);
        $data = array_pop($this->code_queue);
        $this->over();
        return $data;
    }


}