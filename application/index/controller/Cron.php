<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/7
 * Time: 22:19
 */

namespace app\index\controller;


use app\common\model\Config;
use app\common\model\OrdersNotify;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\Db;
use think\Log;

class Cron
{
   public function tongbu()
{
//$data =  db('ewm_order')->where(['status' =>1])->find();
	$data = db()->query('select o.trade_no,e.notify_url from cm_ewm_order as e left join cm_orders as o on o.trade_no = e.order_no  where e.status=1 and e.add_time>unix_timestamp(now())-60*60*48 and o.status=1 order by o.create_time desc limit 100');
	var_dump($data);
foreach($data as $d)
{
$postData['out_trade_no'] = $d['trade_no'];
$orderInfo['notify_url'] =  $d['notify_url'];
//var_dump($postData);die();
$ret = httpRequest($orderInfo['notify_url'], 'post', $postData);
}
//var_dump($data);die();
//echo 3;die();

}
 public function closeChannel()
{
 //1.获取所有余额大于5万的商户

//2.判断是否关闭了渠道

//3.关闭所有渠道，通知商户


}
public function test()
{
var_dump( $_SERVER);die();
echo clientIp();
}
    /**
     * @return mixed
     *  代付订单列表
     */
    public function index()
    {
        $where = ['uid' => is_login()];
        //组合搜索
        !empty($this->request->get('trade_no')) && $where['out_trade_no']
            = ['like', '%' . $this->request->get('trade_no') . '%'];

        !empty($this->request->get('channel')) && $where['channel']
            = ['eq', $this->request->get('channel')];

        //时间搜索  时间戳搜素
        $date = $this->request->param('d/a');

        $start = empty($date['start']) ? date('Y-m-d H:i:s', time() - 3600 * 24) : $date['start'];
        $end = empty($date['end']) ? date('Y-m-d', time() + 3600 * 24) : $date['end'];
        $where['create_time'] = ['between', [strtotime($start), strtotime($end)]];
        //状态
        if (!empty($this->request->get('status')) || $this->request->get('status') === '0') {
            $where['status'] = $this->request->get('status');
        }
//        print_r($where);

//        var_dump($where);die();
        $orderLists = $this->logicDaifuOrders->getOrderList($where, true, 'create_time desc', 10);
        //查询当前符合条件的订单的的总金额  编辑封闭 新增放开 原则
        $cals = $this->logicDaifuOrders->calOrdersData($where);
        $this->assign('list', $orderLists);
        $this->assign('cal', $cals);
        $this->assign('code', []);//$this->logicDaifuOrders->getCodeList([]));
        $this->assign('start', $start);
        $this->assign('end', $end);
        return $this->fetch();
    }


    /**
     * 申请代付
     */
    public function apply()
    {
        //用户信息
        $userInfo = $this->logicUser->getUserInfo(['uid' => session('user_info.uid')]);

        //google验证其二维码
        require_once EXTEND_PATH . 'PHPGangsta/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $where = ['uid' => is_login()];
        if ($this->request->isPost()) {
            if ($userInfo['is_need_google_verify']) {
                //google身份验证
                $code = input('b.google_code');
                $secret = session('google_secret');
                $checkResult = $ga->verifyCode($secret, $code, 1);
                if ($checkResult == false) {
                    $this->result(0, 'google身份验证失败 ！！！');
                }
            }
            //校验令牌
            $token = input('__token__');
//            if(session('__token__')!= $token){
//                $this->result(0,'请不要重复发起代付,请刷新页面重试 ！！！');
//            }
            session('__token__', null);

            //校验是否允许发起代付从前端
            if ($userInfo->is_can_df_from_index != 1) {
                $this->result(0, '您不允许在前端发起代付申请 ！！！');
            }

            if ($this->request->post('b/a')['uid'] == is_login()) {
                $this->result($this->logicDaifuOrders->manualCreateOrder($this->request->post('b/a'), $userInfo));
            } else {
                $this->result(0, '非法操作，请重试！');
            }
        }
        //详情
        $this->common($where);
        //收款账户
        $secret = $ga->createSecret();
        session('google_secret', $secret);
        $this->assign('user', $userInfo);
        //银行
        $this->assign('banker', $this->logicBanker->getBankerList());
        $this->assign('google_qr', $ga->getQRCodeGoogleUrl($userInfo['account'], $secret));
        return $this->fetch();
    }


    /**
     * Common
     *
     * @param array $where
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function common($where = [])
    {
        //资产信息
        $this->assign('info', $this->logicBalance->getBalanceInfo($where));
        //银行
        $this->assign('banker', $this->logicBanker->getBankerList());

    }


    /**
     * 导出订单
     */
    public function myexportOrder()
    {
        $where = ['uid' => is_login()];
        //组合搜索
        !empty($this->request->get('trade_no')) && $where['out_trade_no']
            = ['like', '%' . $this->request->get('trade_no') . '%'];

        !empty($this->request->get('channel')) && $where['channel']
            = ['eq', $this->request->get('channel')];

        //时间搜索  时间戳搜素
        $date = $this->request->param('d/a');

        $start = empty($date['start']) ? date('Y-m-d', time()) : $date['start'];
        $end = empty($date['end']) ? date('Y-m-d', time() + 3600 * 24) : $date['end'];
        $where['create_time'] = ['between', [strtotime($start), strtotime($end)]];
        //状态
        if (!empty($this->request->get('status')) || $this->request->get('status') === '0') {
            $where['status'] = $this->request->get('status');
        }
        //导出默认为选择项所有
        $orderList = $this->logicDaifuOrders->getOrderList($where, true, 'create_time desc', false);

        //组装header 响应html为execl 感觉比PHPExcel类更快
        $orderStatus = ['订单关闭', '等待支付', '支付完成', '异常订单'];
        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">ID标识</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">订单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">金额</td>';
//        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收入</td>';
//        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付渠道</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">创建时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">更新时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
        $strTable .= '</tr>';
        if (is_array($orderList)) {
            foreach ($orderList as $k => $val) {
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;' . $val['id'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['out_trade_no'] . ' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['amount'] . '</td>';
//                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_in'].'</td>';
//                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['channel'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['create_time'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['update_time'] . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . $orderStatus[$val['status']] . '</td>';
                $strTable .= '</tr>';
                unset($orderList[$k]);
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, 'daifuorder');
    }

    /**
     * 定时回调
     */
    public function orderCallback()
    {
        if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1'){
            // echo 'error'; die();
        }
        $order_wh = [
            'is_status' => ['eq', 404],
            'update_time' => ['gt', time()-60*20],
            'times' => ['lt', 5],
            'result' => ['neq', 'SUCCESS']
        ];

        $cron_map = [1,2,5,10,15];

        $orders_notify = Db::name('orders_notify')
            ->where($order_wh)
            ->order('create_time desc')
            ->field('order_id,times,create_time')
            ->select();
        echo '=============================';
        //取一条出来执行
        foreach ($orders_notify as $order){
            if ($order['times'] == 0 or ( time()>= ($order['create_time'] + $cron_map[$order['times']] * 60) )){
                $take_out_order = $order;
                // try {
                $orderNotify = (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->find();
                if ($orderNotify['result'] == 'SUCCESS'){
                    //                Db::commit();
                    continue;
                }
                $order = Db::name('orders')->where('id', $take_out_order['order_id'])->find();
                $result = $this->doOrderCallback($order, $orderNotify['times']);
                echo $order['out_trade_no'];
                if ($result && strtoupper(trim($result['result'])) == 'SUCCESS') {
                    //成功记录数据
                    $result['result'] = strtoupper(trim($result['result']));
                    //  $result['content'] = $result['result'];
                    (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->update($result);

                    \think\Log::notice('订单回调成功，订单号：' . $order['id']);
                }else if ($result  && $result['result'] != 'SUCCESS'){
                    //失败记录数据

                    (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->update([
                        'times'   => $orderNotify['times'] + 1,
                        'update_time'=>time(),
                        'result' => $result['result']
                    ]);
                }else{

                    (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->update([
                        'times'   => $orderNotify['times'] + 1,
                        'update_time'=>time(),
                    ]);
                }

                //}catch (\Exception $e){
                //  \think\Log::error('定时回调错误：' . $e->getMessage());
                //return false;
                // }
                echo 'success';
            }
        }

    }

    private function doOrderCallback($data, $times)
    {
        //要签名的数据
        $where = array();
        $where['uid'] = $data['uid'];
        $LogicApi = new \app\common\logic\Api();
        $appKey = $LogicApi->getApiInfo($where, "key",($data['uid']==100063||$data['uid']==100068|| $data['uid']==100067));
        $to_sign_data =  $this->buildSignData($data, $appKey["key"],($data['uid']==100063||$data['uid']==100068|| $data['uid']==100067));
        //签名串

        \think\Log::notice("\r\n");
        \think\Log::notice("posturl: ".$data['notify_url']);
        \think\Log::notice("sign data: ".json_encode($to_sign_data));
        try{
            $client = new Client();
            $Config = new Config();
            $proxy_debug = $Config->where(['name'=>'transfer_ip_list'])->value('value');
            $orginal_host = $Config->where(['name'=>'orginal_host'])->value('value');
            $time=5;
            if($data['uid']==100110||$data['uid']==100099){
                $time = 9;
            }
            if($proxy_debug  && $times >=2 && $orginal_host )
//                if(config('proxy_debug') && $attempts >=2 )
            {
                \think\Log::notice('中转服务器回调'.$times);
                \think\Log::notice('中转服务器回调'.$orginal_host);
                //是否需要代理服务器处理让代理请求
//                    $hosts = config('orginal_host');
                $hosts = $orginal_host;
                $url = $hosts.'?notify_url='.urlencode($data['notify_url']);
                $response = $client->request(
                    'POST', $url, ['form_params' => $to_sign_data,'timeout'=>5]
                );

            }else{
                \think\Log::notice('本服务器回调'.$times);
                $response = $client->request(
                    'POST', $data['notify_url'], ['form_params' => $to_sign_data,'timeout'=>5]
                );

            }


            $statusCode = $response->getStatusCode();
            $contents = $response->getBody()->getContents();
            \think\Log::notice("订单回调 notify url " . $data['notify_url'] . "data" . json_encode($to_sign_data).'返回内容:'.$contents);
            \think\Log::notice("response code: ".$statusCode." response contents: ".$contents);
            print("<info>response code: ".$statusCode." response contents: ".$contents."</info>\n");
            // JSON转换对象
            if ( $statusCode == 200 && !is_null($contents)){
                //判断放回是否正确
//                    if ($contents == "SUCCESS"){
                //TODO 更新写入数据
                return [
                    'result'   => $contents,
                    'is_status'   => $statusCode
                ];
//                    }
//                    return false;
            }
            return false;
        }catch (RequestException $e){
            \think\Log::error('Notify Error:['.$e->getMessage().']');
            return false;
        }

        return false;
    }

    private function buildSignData($data,$md5Key,$need_remark=false){
        $orderId = $data['id'];
        //除去不需要字段
        unset($data['id']);
        unset($data['uid']);
        unset($data['cnl_id']);
        unset($data['puid']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['update_time']);
        unset($data['update_time']);
        unset($data['income']);
        unset($data['user_in']);
        unset($data['agent_in']);
        unset($data['platform_in']);
        unset($data['currency']);
        unset($data['client_ip']);
        unset($data['return_url']);
        unset($data['notify_url']);
        unset($data['extra']);
        unset($data['subject']);
        unset($data['bd_remarks']);
        unset($data['visite_show_time']);
        unset($data['real_need_amount']);
        unset($data['image_url']);
        unset($data['request_log']);
        unset($data['visite_time']);
        unset($data['request_elapsed_time']);
        unset($data['channel_pay_url']);
//        unset($data['cnl_in']);

        $data['amount'] = sprintf("%.2f", $data['amount']);
        $data['order_status'] = 1;
        ksort($data);

        $signData = "";
        foreach ($data as $key=>$value)
        {
            $signData = $signData.$key."=".$value;
            $signData = $signData . "&";
        }
        $str = $signData."key=".$md5Key;

        print("<info>md5 str:".$str."</info>\n");
        Log::notice("md5 str: ".$str);
        $sgin = md5($str);
        $data['sign'] = $sgin;

        //加密参数
        $ordersNotify = new OrdersNotify();
        $notify = Db('orders_notify')->where(['order_id'=>$orderId])->find();
        if (empty($notify)) {
            $n_data['order_id'] = $orderId;
            $n_data['times'] = 0;
            $n_data['is_status'] = 404;
            $n_data['sign_data'] = json_encode($data);
            $n_data['sign_md5'] = $str;
            //  Db('orders_notify')->save($n_data);
        } else {
            $result = [
                'sign_data' => json_encode($data),
                'sign_md5' => $str,
            ];
            Db('orders_notify')->where('order_id',$orderId)->update($result);
        }

        //返回
        return $data;
    }

}
