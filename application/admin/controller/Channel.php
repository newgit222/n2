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


use app\common\library\enum\CodeEnum;

class Channel extends BaseAdmin
{
    /**
     * 支付渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 支付渠道列表
     * @url getList?page=1&limit=10
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function getList(){

        $where = [];
        //组合搜索
        !empty($this->request->param('keywords')) && $where['id|name']
            = ['like', '%'.$this->request->param('keywords').'%'];
        $this->result($this->logicChannel->getChannelAll($where,true, 'create_time desc',false));
    }
    /**
     * 新增支付渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function add()
    {
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicChannel->addChannel($this->request->post()));

        return $this->fetch();
    }

    /**
     * 编辑支付渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function edit(){
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicChannel->editChannel($this->request->post()));

        //获取渠道详细信息
        $this->assign('channel',$this->logicChannel->getChannelInfo(['id' =>$this->request->param('id')]));

        return $this->fetch();
    }

    /**
     * 删除渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function del(){
        // post 是提交数据
        $this->request->isPost() && $this->result(
            $this->logicChannel->delChannel(
                [
                    'id' => $this->request->param('id')
                ])
        );

        // get 直接报错
        $this->error('未知错误');
    }


    /**
     * 渠道资金列表
     */
    public function fundIndex(){
        return $this->fetch();
    }


    /**
     * 修改余额
     */
    public function fundChange(){
        // post 是提交数据
        $channel_id=$this->request->param('channel_id/d');
        if($this->request->isPost()){
            if(session('__token__') != $this->request->param('__token__'))
            {
                $this->result( CodeEnum::ERROR, '请刷新页面重试');
            }
            $setDec=$this->request->param('change_type');
            $amount=$this->request->param('amount');
            $remarks=htmlspecialchars($this->request->param('remarks/s'));
            $ret = $this->logicPayChannelChange->creatPayChannelChange($channel_id,$amount,$remarks,$setDec,1);

            session('__token__',null);
            $code = $ret? CodeEnum::SUCCESS : CodeEnum::ERROR;
            $msg = $ret ? "操作成功" : "操作失败";
            $this->result($code, $msg);
        }
        return $this->fetch();
    }

    function fundDetails(){
        $channel_id  = $this->request->param('channel_id');
        $where['create_time'] = $this->parseRequestDate3();
        $where['channel_id'] = $channel_id;
        $data = $this->logicPayChannelChange->getPayChannelChangeInfo($where);
        $this->assign('channel_id',$channel_id);
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function getFundDetails(){
        $where = [];

        //组合搜索
        $where['channel_id'] = ['eq', $this->request->param('channel_id')];

        $remarks = $this->request->param('remarks');
        $remarks &&  $where['remarks'] =['like',"%".$remarks."%"];
        //时间搜索  时间戳搜素
        $where['create_time'] = $this->parseRequestDate3();

        $isPlarOp =  $this->request->param('is_flat_op',-1);
        ($isPlarOp!=-1) &&  $where['is_flat_op']  = $isPlarOp;
        $field  ='*,sum(increase) as total_increase,sum(reduce) as total_reduce';


        $data = $this->logicPayChannelChange->getPayChannelChangeList($where, true, 'id desc', false);

        $count = $this->logicPayChannelChange->getPayChanneleChangeCount($where);
        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>$count,
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>$count,
                'data'=>$data
            ]
        );
    }

    /**
     * 渠道统计
     */
    public function statistics(){
        return $this->fetch();
    }

    /**
     * 获取渠道统计列表
     */
    public function getStatisticsList(){
        $where = [];
        $data = ($this->logicPay->getChannelStatisticsList($where,true, 'create_time desc',false));


        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>10,
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>10,
                'data'=>$data
            ]);
    }

}
