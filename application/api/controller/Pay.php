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

namespace app\api\controller;

use app\api\service\ApiRespose;
use app\common\controller\BaseApi;
use app\common\library\exception\ForbiddenException;
use app\common\model\EwmOrder;
use app\common\model\Orders;
use GuzzleHttp\Client;
use think\Log;
use think\Request;

/**
 * 所有的支付操作，都需要对输入执行参数校验，避免接口受到攻击。
 * 　　● 验证输入参数中各字段的有效性验证，比如用户ID,商户ID,价格，返回地址等参数。
 * 　　● 验证账户状态。交易主体、交易对手等账户的状态是处于可交易的状态。
 * 　　● 验证订单：如果涉及到预单，还需要验证订单号的有效性，订单状态是未支付。为了避免用户缓存某个URL地址，还需要校验下单时间和支付时间是否超过预定的间隔。
 * 　　● 验证签名。签名也是为了防止支付接口被伪造。 一般签名是使用分发给商户的key来对输入参数拼接成的字符串做MD5 Hash或者RSA加密，然后作为一个参数随其他参数一起提交到服务器端。
 *
 */
class Pay extends BasePay
{
    //前置操作来验证  不适用tags了
    protected $beforeActionList = [
        'checkRequestParam' => ['only' => 'unifiedorder,query'],
    ];

    /**
     * 收银台跳转支付
     *
     * @throws ForbiddenException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     */
    public function cashier()
    {
        throw new ForbiddenException(['msg' => '等待开发...']);
    }

    /**
     * 统一扫码支付
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function unifiedorder()
    {
        $startTime = time();
        //传入预支付订单信息 => 支付对象返
        if (config('proxy_debug')) {
            //代理过去
            $hosts = config('real_host');
            $requestUrl = $hosts . "api/pay/unifiedorder";
            $res = httpRequest($requestUrl, 'post', $this->request->post());
        } else {
            //本host处理
            $res = $this->logicPrePay->orderPay($this->request->post());
        }

        $data = [
            'code' => 0,
            'msg' => '请求成功',
            'data' => $res,
        ];
        $useTime = time() - $startTime;

        $log = "本笔订单耗时{$useTime}秒,REQUEST_PARAMS:" . json_encode($this->request->post()) . "|RESPONSE_PARAMS:" . json_encode($data);
        Log::notice($log);


        echo json_encode($data);
        die();
    }

    /**
     * 统一查询接口
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function query()
    {
        //传入预支付订单信息 => 支付对象返回
        //   $res = $this->logicPrePay->daifuOrderQuery($this->request->post());

        $res = $this->logicPrePay->orderQuery($this->request->post());
        $data = [
            'code' => 0,
            'msg' => '请求成功',
            'data' => $res,
        ];
        echo json_encode($data);
        die();
    }

    /**
     * 代付款统一下单接口
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function daifuorder()
    {

        $startTime = time();
        //传入预支付订单信息 => 支付对象返
        //本host处理
        $res = $this->logicPrePay->daifuOrderPay($this->request->post());
        $data = [
            'code' => 0,
            'msg' => '请求成功',
            'data' => $res,
        ];
        $useTime = time() - $startTime;

        $log = "本笔订单耗时{$useTime}秒,REQUEST_PARAMS:" . json_encode($this->request->post()) . "|RESPONSE_PARAMS:" . json_encode($data);
        Log::notice($log);


        echo json_encode($data);
        die();

//        ApiRespose::send($this->logicPrePay->daifuOrderPay($this->request->post()));
    }

    /**
     * 代付款统一查询接口
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function daifuquery()
    {
        //传入预支付订单信息 => 支付对象返回
        $res = $this->logicPrePay->daifuOrderQuery($this->request->post());
        $data = [
            'code' => 0,
            'msg' => '请求成功',
            'data' => $res,
        ];
        return json_encode($data);
    }
   public function updateOrderPayUsername2(Request $request)
   {
	   //  echo 3;die();
	   $trade_no = '2203240224433781';
	   $payusername = '关彬';
       $data = (new EwmOrder())->where('order_no',$trade_no)->find();
       $where['code_id'] = $data['code_id'];
       $where['pay_username'] = $payusername;
       $where['order_price'] = $data['order_price'];
       $where['add_time'] = array(">",$data['add_time']-60*5);
      $data =  (new EwmOrder())->where($where)->find();
       var_dump($data);die();

       $data['code_id'];
   }

    /**
     * 更新订单支付用户名
     */
    public function updateOrderPayUsername(Request $request)
    {
        $trade_no = $request->post('trade_no');
        $payusername = $request->post('pay_username');
        $ip = $request->post('ips');
        if($ip=='119.91.88.109' || $ip=='42.194.142.113')
        {

              Log::error('----ipyouwenti------'. $trade_no.'---'. $payusername.'----'.$ip);
        return $this->error('ok', null, ['pay_username' => $payusername.strlen($payusername)]);
        }

        if ($this->modelEwmBlockIp->where('block_visite_ip', get_userip())->find()){
            return $this->error('没有足够的卡转卡资源请重新拉取订单');
        }
      //  $trade_no = '9341498210926203216';
       // $payusername ='伟哥';
        if (empty($trade_no)) return $this->error('订单号不能为空');
        $aValid = array('-', '_');
        if (!ctype_alnum((str_replace($aValid, '', $trade_no)))) return $this->error('订单号不合法');
        if (empty($payusername)) return $this->error('姓名不能为空');
        if (checkIsChinese($payusername) == false) return $this->error('请输入中文姓名');
        if (strlen($payusername) > 12) return $this->error('付款人姓名最大五位长度');
	$data = (new EwmOrder())->where('order_no',$trade_no)->find();

//	$data = (new EwmOrder())->where('order_no',$trade_no)->find();
       $where2['code_id'] = $data['code_id'];
       $where2['pay_username'] = $payusername;
       $where2['order_price'] = $data['order_price'];
       $where2['add_time'] = array(">",$data['add_time']-60*5);
       $data2 =  (new EwmOrder())->where($where2)->find();
       if(!empty($data2))
       {
           return $this->error('请重新提交订单', null, ['pay_username' => $payusername.strlen($payusername)]);
       }

        if(!empty($data['pay_username']))
        {
            //Log::error('chongfu----'.$trade_no.'---------newip'. $request->post('ips'). $payusername);
            if($payusername==$data['pay_username'])
            {
              return $this->success('ok', null, ['pay_username' => $payusername.strlen($payusername)]);
            }else
            {
            return $this->error('请重新提交订单', null, ['pay_username' => $payusername.strlen($payusername)]);
            }
         }
        $ret = (new EwmOrder())->where('order_no', $trade_no)->setField('pay_username', $payusername);
      //  Log::error('----'.$trade_no.'---------newip'. $request->post('ips'). $payusername);
        return $this->success('ok', null, ['pay_username' => $payusername.strlen($payusername)]);

    }


    /**
     * 上报访问相干信息
     * @param Request $request
     */
    public function recordVisistInfo(Request $request)
    {
        $trade_no = $request->post('trade_no', '');
        if (empty($trade_no)) return $this->error('订单号不能为空');
        if (!ctype_alnum($trade_no)) return $this->error('订单号不合法');
        $visite_ip = $request->post('visite_ip');
        if (empty($visite_ip)) return $this->error('上报访问设备IP不能为空');
        if (!filter_var($visite_ip, FILTER_VALIDATE_IP)) return $this->error('上报ip不合法');
        $visite_clientos = match_str($request->post('visite_clientos'));
        $ret = (new EwmOrder())->where('order_no', $trade_no)->update([
            'visite_ip' => $visite_ip,
            'visite_time' => time(),
            'visite_clientos' => $visite_clientos,
        ]);
        if (false !== $ret) {
            $this->success('上报成功');
        }
        $this->error('上报信息有误');
    }


}
