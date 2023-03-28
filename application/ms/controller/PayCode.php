<?php

namespace app\ms\controller;

use app\common\library\enum\CodeEnum;
use app\common\logic\CodeLogic;
use app\common\logic\EwmOrder;
use app\common\logic\Queuev1Logic;
use app\common\model\GemapayCodeModel;
use think\Db;
use think\Request;

/**
 *码商二维码管理
 * Class PayCode
 * @package app\agent\controller
 */
class PayCode extends Base
{
    /**
     * 二维码列表
     * @param Request $request
     * @return mixed
     */
    public function lists(Request $request)
    {



        $account_name = $request->param('account_name', '', 'trim');
	if(strlen( $account_name<10)){
	    $account_name && $map['a.account_name'] = ['like', '%' . $account_name . '%'];
        }
        $map = [];
        $map['a.ms_id'] = $this->agent_id;
        $map['a.is_delete'] = 0;

        $gemapayCode = Db::name('ewm_pay_code');
        $listGemaPayCode = $gemapayCode->alias('a')
            ->field('*')
            ->where($map)
            ->order('id desc')
            ->paginate(10);

        $list = $listGemaPayCode->items();
        $CodeLogic = new CodeLogic();

        foreach ($list as $k => $v) {
            $v['type'] = 3; //3是银行卡
            $v['add_admin_id'] = 1;//目前就当是支付系统admin超级管理员
            $position = $CodeLogic->getQueenPostion($v['id'], $v['type'], $v['add_admin_id']);
            $list[$k]['queen_postion'] = $position;
        }
        $positions = array_column($list, 'queen_postion');
        array_multisort($positions, SORT_ASC, SORT_REGULAR, $list);
        $count = $listGemaPayCode->total();
        // 获取分页显示
        $page = $listGemaPayCode->render();
        $this->assign('count', $count);
        $this->assign('list', $list); // 賦值數據集
        $this->assign('count', $count);
        $this->assign('page', $page); // 賦值分頁輸出
        return $this->fetch();
    }

     public function qun_lists(Request $request)
    {
        $account_name = $request->param('account_name', '', 'trim');
	if(strlen( $account_name<10)){
	    $account_name && $map['a.account_name'] = ['like', '%' . $account_name . '%'];
        }
        $map = [];
        $map['a.ms_id'] = $this->agent_id;
        $map['a.is_delete'] = 0;

        $gemapayCode = Db::name('ewm_pay_code');
        $listGemaPayCode = $gemapayCode->alias('a')
            ->field('*')
            ->where($map)
            ->order('id desc')
            ->paginate(10);

        $list = $listGemaPayCode->items();
        $CodeLogic = new CodeLogic();

        foreach ($list as $k => $v) {
            $v['type'] = 1; //3是银行卡
            $v['add_admin_id'] = 1;//目前就当是支付系统admin超级管理员
            $position = $CodeLogic->getQueenPostion($v['id'], $v['type'], $v['add_admin_id']);
            $list[$k]['queen_postion'] = $position;
        }
        $positions = array_column($list, 'queen_postion');
        array_multisort($positions, SORT_ASC, SORT_REGULAR, $list);
        $count = $listGemaPayCode->total();
        // 获取分页显示
        $page = $listGemaPayCode->render();
        $this->assign('count', $count);
        $this->assign('list', $list); // 賦值數據集
        $this->assign('count', $count);
        $this->assign('page', $page); // 賦值分頁輸出
        return $this->fetch();
    }
    /**
     * 银行卡
     * @var string[]
     */
    protected $banks = [
	    '农业银行','山西省农村信用社',
'工商银行','恒丰银行','华夏银行','山东省农村信用社',
'招商银行','台州银行',
'平安银行','广发银行',
'吉林银行','广州（潮州）农商银行',
'武汉农商银行',
'吉林昌邑榆银村镇银行',
'河南省农村信用社',
'河北省农村信用社',
'海南省农村信用社',
'安徽省农村信用社','四川省农村信用社','浙江省农村信用社','广东省农村信用社','广西省农村信用社','黑龙江省农村信用社','湖北省农村信用社','南京银行','中原银行','浦发银行','光大银行','安徽当涂新华村镇银行','江苏长江商业银行','渤海银行','保定银行'
    ];


    /**
     * 添加二维码
     */
    public function add(CodeLogic $codeLogic)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'EwmPayCode');
            if (true !== $result) {
                $this->error($result);
            }
            $result = $codeLogic->addQRcode($data);
            if ($result['code'] = CodeEnum::ERROR) {
                $this->error("上传失败," . $result['msg']);
            }
            $this->success($result['msg'], url('lists'));
        }
        $this->assign('banksList', $this->banks);
        return $this->fetch();
    }

     /**
     * 添加群二维码
     */
    public function qun_add(CodeLogic $codeLogic)
    {
        if ($this->request->isPost()) {
			
			if (( ($_FILES["file"]["type"] == "image/jpeg")
                || ($_FILES["file"]["type"] == "image/pjpeg")
                || ($_FILES["file"]["type"] == "image/png"))
            && ($_FILES["file"]["size"] < 2000000))
        {
            if ($_FILES["file"]["error"] > 0)
            {
                $this->error("Return Code: " . $_FILES["file"]["error"],3);
            }
            else
            {
                if($_FILES["file"]["type"] == "image/jpeg"){
                    $ext = '.jpg';
                }
                if($_FILES["file"]["type"] == "image/pjpeg"){
                    $ext = '.jpg';
                }
                if($_FILES["file"]["type"] == "image/png"){
                    $ext = '.png';
                }
                $name = md5(microtime()).$ext;
                if (file_exists("public/uploads/" . $name))
                {
                    echo $_FILES["file"]["name"] . " already exists. ";
                }
                else
                {
                    move_uploaded_file($_FILES["file"]["tmp_name"],
                        "public/uploads/" .$name);
                  //  $this->success("Stored in: " . "upload/" . $_FILES["file"]["name"],'/ownpay/add',3);
                }
            }
        }
        else
        {
            $this->error('非法图片，请选择二维码图片','/ownpay/add',3);
        }
			
			
			
			
            $data = $this->request->param();
           // $result = $this->validate($data, 'EwmPayCode');
           // if (true !== $result) {
             //   $this->error($result);
           // }
		    $data['file_url']= "public/uploads/" . $name;
            $result = $codeLogic->addQunQRcode($data);
            if ($result['code'] = CodeEnum::ERROR) {
                $this->error("上传失败," . $result['msg']);
            }
            $this->success($result['msg'], url('qun_lists'));
        }
        $this->assign('banksList', $this->banks);
        return $this->fetch();
    }
    /**
     * 删除二维码
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del(Request $request)
    {
        $id =intval( trim($request->param('id')));

        $codeInfo = $this->modelEwmPayCode->find($id);
        if ($codeInfo['ms_id'] != session('agent_id')) {
            $this->error('删除失败,信息错误');
        }
        $re = $this->modelEwmPayCode
            ->where('id', $id)
            ->delete();


        if ($re) {
            $QueueLogic = new Queuev1Logic();
            $codeInfo['type'] = 3;
            $QueueLogic->delete($id, $codeInfo['type'], 1);
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     *　激活二维码
     */
    public function activeCode(CodeLogic $codeLogic)
    {
        $codeTypeId =intval( $this->request->param('code_id'));

        if (!$codeTypeId) {
            $this->error('非法操作');
        }
        $res = $codeLogic->activeCode($this->agent_id, $codeTypeId);
        if ($res['code'] == \app\common\library\enum\CodeEnum::ERROR) {
            $this->error($res['msg']);
        }
        $this->success($res['msg']);
    }


    /**
     * 冻结二维码
     */
    public function disactiveCode(CodeLogic $codeLogic)
    {
        $codeTypeId = intval($this->request->param('code_id'));
        if (!$codeTypeId) {
            $this->error('非法操作');
        }
        $res = $codeLogic->disactiveCode($this->agent_id, $codeTypeId);
        if ($res['code'] == \app\common\library\enum\CodeEnum::ERROR) {
            $this->error($res['msg']);
        }
        $this->success($res['msg']);
    }


    /**
     * 二维码统计信息
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function statistics(Request $request)
    {
      //$type = $request->param('type', 0, 'intval');
        //eyword = $request->param('keyword', 0, 'intval');

       //mobile = $request->param('mobile', 0, 'intval');
       $account = $request->param('account', 0, 'vlidateError');
      $account_name = $request->param('account_name', 0, 'intval');

        $map = [];

       //type && $map['type'] = $type;
      //$keyword && $map['a.id'] = $keyword;

//      $mobile && $map['b.mobile'] = ['like', '%' . $mobile . '%'];
  //    $account && $map['b.account'] = ['like', '%' . $account . '%'];
    //  $account_name && $map['a.account_name'] = ['like', '%' . $account_name . '%'];


        $add_time = [];
        //时间
        $startTime = $request->param('start_time');
        empty($startTime) && $startTime = date("Y-m-d 00:00:00", time());
        $endTime = $request->param('end_time');
        empty($endTime) && $endTime = date("Y-m-d 23:59:59", time());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);


        $add_time = ['between', [strtotime($startTime), strtotime($endTime)]];
        $map['a.ms_id'] = $this->agent_id;
        $GemapayOrderLogic = new EwmOrder();
        $GemapayOrderModel = new \app\common\model\EwmOrder();
        $where = [
            'add_time' => $add_time
        ];

        $orders = $GemapayOrderModel->field('code_id')->where($where)->select();

        $array = [];
        foreach ($orders as $k => $v) {
            $array[] = $v['code_id'];
        }
        $map['a.id'] = ['in', $array];


        $gemapayCode = Db::name('ewm_pay_code');
        $listGemaPayCode = $gemapayCode->alias('a')
            ->field('a.*,b.account,b.username')
            ->join('cm_ms b', 'a.ms_id=b.userid', "LEFT")
            ->where($map)
            ->order('id desc')->paginate(50);
        $list = $listGemaPayCode->items();

        foreach ($list as $k => $v) {
            $where = [
                'code_id' => $v['id'],
            ];
            $where['add_time'] = $add_time;
            $list[$k]['orders'] = $GemapayOrderLogic->getTotalPrice($where);
        }
        $count = $listGemaPayCode->total();
        // 获取分页显示
        $page = $listGemaPayCode->render();
        $this->assign('count', $count);

        $this->assign('list', $list); // 賦值數據集
        $this->assign('count', $count);
        $this->assign('page', $page); // 賦值分頁輸出


//        //分组列表
//        $GemaPayGroup = new GemaPayGroupModel();
//        $this->assign('groupList', $GemaPayGroup->getGroupList($this->agent->userid));
        return $this->fetch();
    }


}
