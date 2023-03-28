<?php


namespace app\ms\controller;


use app\common\library\enum\CodeEnum;
use app\common\model\EwmOrder;
use app\common\model\EwmPayCode;
use app\index\model\ConfigModel;
use app\ms\Logic\SecurityLogic;
use think\Db;
use think\Model;
use think\Request;

/**
 *二码商二维码订单
 * Class PayOrder
 * @package app\ms\controller
 */
class PayOrder extends Base
{

    /**
     * 码商订单列表
     * @param Request $request
     * @return mixed
     */
    public function lists(Request $request)
    {
        $status = $request->param('status', -1, 'intval'); //状态

        $isBack = $request->param('is_back', -1, 'intval');//状态

        return $this->common($status, false, $isBack);
    }


    public function common($status = -1, $isUploadCredentials = false, $isBack = false)
    {
        $request = Request::instance();
        $map = [];
        //订单编号
        $order_no = addslashes(trim($request->param('order_no')));
        $order_no && $map['order_no'] = ['like', '%' . $order_no . '%'];

        //用户名
        $gema_username = addslashes(trim($request->param('gema_username', '')));#用户名
        if (!empty($gema_username)) {
            $map['o.gema_username'] = ['like', '%' . $gema_username . '%'];
        }
        ($isBack != -1) && $map['o.is_back'] = $isBack;

        if ($isBack == 0) {
            $status = 1;
        }

        //收款人姓名
        $payUserName = addslashes(trim($request->param('pay_username')));
//        $payUserName && $map['pay_username'] = ['like', "%{$payUserName}%"];
        $payUserName && $map['pay_username'] = addslashes(trim($payUserName));

        //正式姓名
        $pay_user_name = addslashes(trim($request->param('pay_user_name')));
        $pay_user_name &&  $map['pay_user_name'] = ['eq', $pay_user_name];

         $amount = addslashes(trim($request->param('amount')));
        $amount && $map['order_price'] = $amount;
        //新增其他条件
        ($status != -1) && $map['o.status'] = intval($status);

        $this->assign('status', $status);
        //时间
        //码商最多只能搜索三天内的订单
        $day = 2;
        $startTime = $request->param('start_time');
        empty($startTime) &&  $startTime =  date("Y-m-d 00:00:00");
        $startTime && time()-strtotime($startTime)>86400*$day &&  $startTime =date("Y-m-d H:i:s", strtotime("-{$day} day"));

        $endTime = $request->param('end_time', date("Y-m-d 23:59:59"));
        $this->assign('status', $status);
        if ($startTime && empty($endTime)) {
            $map['add_time'] = ['egt', strtotime($startTime)];
        }
        if (empty($startTime) && $endTime) {
            $map['add_time'] = ['elt', strtotime($endTime)];
        }
        if ($startTime && $endTime) {
            $map['add_time'] = ['between', [strtotime($startTime), strtotime($endTime)]];
        }

        $this->assign('start_time', $startTime);
        $this->assign('end_time', $endTime);
        $code_type = intval($this->request->param('code_type'));
        if ($code_type) {
            $map['o.code_type'] = $code_type;
        }

        $fileds = [
            "o.*",
            "u.mobile",
            "u.account",
        ];


        $code_id = $this->request->param('code_id');
        if ($code_id) {
            $map['o.code_id'] = $code_id;
        } else {
            $map['o.code_id'] = ['neq', 0];
        }

        $map['gema_userid'] = $this->agent_id;

        $listData = Db::name('ewm_order')->alias('o')->field($fileds)
            ->join("ms u", "o.gema_userid=u.userid", "left")
            ->where($map)->order('id desc')
            ->paginate(10);


        //当前条件下订单总金额以及总提成
        $totalOrderPrice = Db::name('ewm_order')->alias('o')
            ->join("ms u", "o.gema_userid=u.userid", "left")->where($map)->sum('order_price');//订单

        $totalTc = Db::name('ewm_order')->alias('o')
            ->join("ms u", "o.gema_userid=u.userid", "left")->where($map)->sum('bonus_fee');//提成

        //当前订单下的订单成功率
        $totalOrderCount = Db::name('ewm_order')->alias('o')
            ->join("ms u", "o.gema_userid=u.userid", "left")->where($map)->count();

        unset($map['o.status']);
        $totalOrderSuccessCount = Db::name('ewm_order')->alias('o')
            ->join("ms u", "o.gema_userid=u.userid", "left")
            ->where($map)
            ->where(['o.status' => '1'])
            ->count();

        //当前条件下订单成功金额
        $totalOrderSuccessPrice = Db::name('ewm_order')->alias('o')
            ->join("ms u", "o.gema_userid=u.userid", "left")
            ->where($map)
            ->where(['o.status' => '1'])
            ->sum('order_price');
        $this->assign('totalOrderSuccessPrice', $totalOrderSuccessPrice);


        if ($totalOrderSuccessCount > 0) {
            $orderPercent = empty($totalOrderCount) ? 0.00 : sprintf("%.2f", $totalOrderSuccessCount / $totalOrderCount) * 100;
        } else {
            $orderPercent = 0;
        }
        $this->assign('orderPercent', $orderPercent);


        $listData->appends($this->request->param());
        $list = $listData->items();
        $count = $listData->count();
        $page = $listData->render();

        foreach ($list as $key => $vals) {
            $list[$key]['s_type_name'] = '无';
            $code = EwmPayCode::where(['id' => $vals['code_id']])->find();
            $list[$key]['account_number'] = $code['account_number'];
            $list[$key]['bank_name'] = $code['bank_name'];
			$list[$key]['account_name'] = $code['account_name'];
            $list[$key]['qr_image'] = $code['image_url'];
            $list[$key]['strArea'] = '无';
            $list[$key]['group_name'] = '无';
            //用户真实姓名
            $list[$key]['real_name'] = ctype_alnum($vals['pay_user_name'])?$vals['pay_username']:$vals['pay_user_name'];

        }
        $this->assign('count', $count);
        $this->assign('list', $list); // 賦值數據集
        $this->assign('totalOrderPrice', $totalOrderPrice);
        $this->assign('totalTc', $totalTc);
        $this->assign('page', $page); // 賦值分頁輸出
        //$device = $this->isMobileDevice() ? 'index_mobile' : 'list';
        $device = 'list';
        return $this->fetch($device);
    }


    protected function isMobileDevice()
    {
        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] : '';
        function CheckSubstrs($substrs, $text)
        {
            foreach ($substrs as $substr)
                if (false !== strpos($text, $substr)) {
                    return true;
                }
            return false;
        }

        $mobile_os_list = array('Google Wireless Transcoder', 'Windows CE', 'WindowsCE', 'Symbian', 'Android', 'armv6l', 'armv5', 'Mobile', 'CentOS', 'mowser', 'AvantGo', 'Opera Mobi', 'J2ME/MIDP', 'Smartphone', 'Go.Web', 'Palm', 'iPAQ');
        $mobile_token_list = array('Profile/MIDP', 'Configuration/CLDC-', '160×160', '176×220', '240×240', '240×320', '320×240', 'UP.Browser', 'UP.Link', 'SymbianOS', 'PalmOS', 'PocketPC', 'SonyEricsson', 'Nokia', 'BlackBerry', 'Vodafone', 'BenQ', 'Novarra-Vision', 'Iris', 'NetFront', 'HTC_', 'Xda_', 'SAMSUNG-SGH', 'Wapaka', 'DoCoMo', 'iPhone', 'iPod');

        $found_mobile = CheckSubstrs($mobile_os_list, $useragent_commentsblock) ||
            CheckSubstrs($mobile_token_list, $useragent);

        if ($found_mobile) {
            return true;
        } else {
            return false;
        }
    }


    /***
     * 确认收款&强制补单
     * @param Request $request
     */
    public function issueOrder(Request $request)
    {
        $orderId = intval($this->request->post('id'));
        $security = $this->request->post('pass');
        //防止重复提交
        $result = $this->validate(
            [
                '__token__' => $this->request->post('__token__'),
            ],
            [
                '__token__' => 'require|token'
            ]);
        if (true !== $result) {
            $this->error($result);
        }


        //判断是否是下级订单列表过来的
//        $user_id = $this->request->post('user_id');
        /*      if ($user_id) {
                  //验证是否为下级用户
                  if (!in_array($user_id, $this->children)) {
                      $this->error('非法操作');
                  }
              }*/
        $GemaOrder = new \app\common\logic\EwmOrder();
        $res = $GemaOrder->setOrderSucessByUser($orderId, $this->agent->userid, $security, 0, 0);

        if ($res['code'] == CodeEnum::ERROR) {
            $this->error($res['msg']);
        }
        $this->success('操作成功');
    }

    /***
     * 确认收款&强制补单
     * @param Request $request
     */
    public function refundOrder(Request $request)
    {
        $orderId = intval($this->request->post('id'));
        $security = $this->request->post('pass');
        //防止重复提交
        $result = $this->validate(
            [
                '__token__' => $this->request->post('__token__'),
            ],
            [
                '__token__' => 'require|token'
            ]);
        if (true !== $result) {
            $this->error($result);
        }

        $SecurityLogic = new SecurityLogic();

        //判断交易密码
        $result = $SecurityLogic->checkSecurityByUserId($this->agent->userid, $security);
        //判断用收款ip是否和最近登录的ip是否一致
        if ($result['code'] == CodeEnum::ERROR) {
            $this->error($result['msg']);
        }

        $where['gema_userid'] = $this->agent->userid;
        $where['id'] = $orderId;
        $where['status'] = 0;

        $order = $this->modelEwmOrder->where($where)->lock(true)->find();

        if ( empty($order)){
            $this->error('订单不存在');
        }

        $order->status = 3;
        $order->save();

        $this->success('更新成功');

    }


    /**
     * 二维码统计
     * @return mixed
     */

    public function statistics(EwmOrder $GemapayOrderModel)
    {
        //总订单数
        $result['total_sum'] = $GemapayOrderModel->where(['gema_userid' => $this->agent->userid])->whereTime('add_time', $this->request->param('day', 'today', 'trim'))->count();
        //成功订单数
        $result['success_sum'] = $GemapayOrderModel->where(['gema_userid' => $this->agent->userid, 'status' => '1'])->whereTime('add_time', $this->request->param('day', 'today', 'trim'))->count();
        //成功订单金额
        $result['success_price'] = $GemapayOrderModel->where(['gema_userid' => $this->agent->userid, 'status' => '1'])->whereTime('add_time', $this->request->param('day', 'today', 'trim'))->sum('order_price');
        //分润金额
        $result['user_bouns_fee'] = $GemapayOrderModel->where(['gema_userid' => $this->agent->userid, 'status' => '1'])->whereTime('add_time', $this->request->param('day', 'today', 'trim'))->sum('bonus_fee');
        $this->assign('info', $result); // 賦值數據集
        return $this->fetch();
    }

    public function abnormal()
    {
        $orderId = intval($this->request->post('id'));
        $security = $this->request->post('pass');
        //防止重复提交
        $result = $this->validate(
            [
                '__token__' => $this->request->post('__token__'),
                'abnormal' => $this->request->post('abnormal'),
            ],
            [
                '__token__' => 'require|token',
                'abnormal' => 'require|between:1,2'
            ],[
                'abnormal.require' => '类型不能为空！',
                'abnormal.between' => '类型错误！'
            ]);
        if (true !== $result) {
            $this->error($result);
        }

        $SecurityLogic = new SecurityLogic();
        //判断交易密码
        $result = $SecurityLogic->checkSecurityByUserId($this->agent_id, $security);
        //判断用收款ip是否和最近登录的ip是否一致
        if ($result['code'] == CodeEnum::ERROR) {
            $this->error($result['msg']);
        }

        $order = $this->modelEwmOrder->find($orderId);
        ($this->request->post('abnormal') == 1) && $order->name_abnormal = 1  ;
        ($this->request->post('abnormal') == 2) && $order->money_abnormal = 1  ;
        $order->save();
        $this->success('操作成功');
    }

}
