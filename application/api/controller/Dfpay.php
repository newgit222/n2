<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/10
 * Time: 20:40
 */

namespace app\api\controller;


use app\common\controller\DaifuBaseApi;
use app\common\logic\DaifuOrders;
use think\Exception;
use think\Log;

class Dfpay extends DaifuBaseApi
{
    protected $verification = ['pay'];


    /**
     * 代付
     */
    public function pay()
    {
        $DaifuOrders = new DaifuOrders();
        $result = $DaifuOrders->createOrder($this->request->post());
        if ($result['code'] != '1') {
            $this->error($result['msg']);
        }
        $this->success('请求成功', null, $result['data']);
    }


    /**
     * 订单查询接口
     */
    public function query()
    {
        $DaifuOrders = new DaifuOrders();
        $result = $DaifuOrders->queryOrder($this->request->post());
        if ($result['code'] != '1') {
            $this->error($result['msg']);
        }
        $this->success('请求成功', null, $result['data']);
    }


    /**
     * 回调
     */
    public function notify()
    {
        try {

            $input = file_get_contents("php://input");
            Log::notice("dfnotify notify data" . $input);
            $data = json_decode($input, true);
            //验证ip
            $DaifuOrdersLogic = new DaifuOrders();
            $DaifuOrders = new \app\common\model\DaifuOrders();
            if (!$DaifuOrdersLogic->checkNotifyIp()) {
                Log::notice("daifu order notify error ip " . $_SERVER['REMOTE_ADDR']);
                throw new Exception('回调IP错误');
            }
            //接收到回调之后 返回给下级
            if (!isset($data['agent_order_no'])) {
                Log::error('daifu order notify error 回调数据错误');
                throw new Exception('错误');
            }
            $orders = $DaifuOrders->where(['trade_no' => $data['agent_order_no']])->find();
            if (!$orders) {
                Log::error('daifu order notify error 订单不存在');
                throw new Exception('订单不存在');
            }

            if ($data['code'] == 0) {
                $result = $DaifuOrdersLogic->errorOrder($orders['id']);
            } else {
                $result = $DaifuOrdersLogic->successOrder($orders['id']);
            }
            //修改订单状态
            if ($result['code'] != '1') {
                Log::error('daifu order notify error' . $result['msg']);
//                throw new Exception('错误');
            }
            echo "SUCCESS";
        } catch (Exception $e) {
            echo "error";
            die();
        }


    }


    /**
     * 验证
     */
    protected function validateParams($action)
    {
        switch ($action) {
            case 'pay':
                $this->addRule('amount', 'require'); //金额
                $this->addRule('mchid', 'require');  //商户号
                $this->addRule('notify_url', 'require'); //回调地址
                $this->addRule('bank_number', 'require');    //银行卡号
                $this->addRule('bank_owner', 'require'); //姓名
                $this->addRule('bank_code', 'require');  //银行编码
                $this->addRule('out_trade_no', 'require');   //商户订单号
                $this->addRule('body', 'require');   //描述
                $this->addRule('sign', 'require');   //sign
                break;
            case "query":
                $this->addRule('out_trade_no', 'require');   //商户订单号
                $this->addRule('mchid', 'require');   //商户号
                $this->addRule('sign', 'require');   //sign
                break;
        }
    }
}