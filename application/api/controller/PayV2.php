<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/5/25
 * Time: 20:21
 */

namespace app\api\controller;



use app\common\controller\BaseApi;
use app\common\library\exception\ForbiddenException;
use app\common\library\exception\ParameterException;
use think\Log;
use think\Request;

class PayV2 extends BasePay
{
//前置操作来验证  不适用tags了
    protected $beforeActionList = [
        'checkRequestParam'  =>  ['only'=>'unifiedorder,query'],
    ];

    /**
     * 收银台跳转支付
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     *
     * @throws ForbiddenException
     */
    public function cashier()
    {
        throw new ForbiddenException(['msg' => '等待开发...']);
    }


    public function doCheck($data)
    {
        $params = $this->request->post();
        foreach ($data as $v) {
            if (! isset($params[$v]) || empty($params[$v])) {
                throw new ParameterException([
                    'msg'=>"缺少参数[{$v}]",
                    'errorCode'=>400000
                ]);
            }
        }
    }
    /**
     * 统一扫码支付
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function unifiedorder()
    {


        //下单地址二  参数名称 修改

        $paramCheck = ['notifyUrl','returnUrl','channel','out_trade_no','total_fee','goodsname'];
        $this->doCheck($paramCheck);
        $post = $this->request->post();
        $param = [
            'mchid' => $post['mchid'],
            'out_trade_no' => $post['out_trade_no'],
            'amount' => $post['total_fee'],
            'channel' => $post['channel'],
            'notify_url' => $post['notifyUrl'],
            'return_url' => $post['returnUrl'],
            'body' => $post['goodsname'],
        ];


        $startTime  = time();
        //传入预支付订单信息 => 支付对象返
        if(config('proxy_debug'))
        {
            //代理过去
            $hosts = config('real_host');
            $requestUrl  =$hosts."api/createOrder";
            $res = httpRequest($requestUrl,'post',$param);
        }else
        {
            //本host处理
            $res = $this->logicPrePay->orderPay($param);
        }

        $data = [
            'code' => 0,
            'msg'  =>'请求成功',
            'data' => $res,
        ];
        $useTime = time()-$startTime;

        $log = "本笔订单耗时{$useTime}秒,REQUEST_PARAMS:".json_encode($this->request->post())."|RESPONSE_PARAMS:".json_encode($data);
        Log::notice($log);


        echo  json_encode($data);die();
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
            'msg'  =>'请求成功',
            'data' => $res,
        ];
        echo json_encode($data);die();
    }

    /**
     * 代付款统一下单接口
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function daifuorder()
    {

        $startTime  = time();
        //传入预支付订单信息 => 支付对象返
        //本host处理
        $res = $this->logicPrePay->daifuOrderPay($this->request->post());
        $data = [
            'code' => 0,
            'msg'  =>'请求成功',
            'data' => $res,
        ];
        $useTime = time()-$startTime;

        $log = "本笔订单耗时{$useTime}秒,REQUEST_PARAMS:".json_encode($this->request->post())."|RESPONSE_PARAMS:".json_encode($data);
        Log::notice($log);


        echo  json_encode($data);die();

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
            'msg'  =>'请求成功',
            'data' => $res,
        ];
        return json_encode($data);
    }


}
