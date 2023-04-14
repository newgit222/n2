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
use think\Cache;
use think\Db;
use think\Log;
use think\Request;

class Cron
{


    /**
     * 定时回调
     */
    public function orderCallback(Request $request)
    {
        $num = trim($request->param('number'));
        if (empty($num)){
            $num = 50;
        }
        for ($i = 1; $i <=$num; $i++) {
            $this->at();
        }
    }

    /**
     * 定时回调
     */
    private function at()
    {
        if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1'){
            // echo 'error'; die();
        }
        $order_wh = [
            'is_status' => ['eq', 404],
            'create_time' => ['gt', time()-60*20],
            'times' => ['lt', 5],
            'result' => ['neq', 'SUCCESS']
        ];

        //对比成功订单跟异步订单差异
        $diffCache = Cache::get('order_notify_diff');
        if (empty($diffCache) || $diffCache < time()-2*60){
            $ordersWhere = [
                'status' => 2,
                'create_time' => ['gt', time()-20*60]
            ];
            $orders = Db::name('orders')->where($ordersWhere)->column('id');
            if ($orders){
                $orders_notify_0 = Db::name('orders_notify')->where('order_id', 'in', $orders)->column('order_id');
                $diff_temp_orders = array_diff($orders, $orders_notify_0);
                $add_order_notify = [];
                foreach ($diff_temp_orders as $val){
                    $add_order_notify[] = ['order_id' => $val];
                    \think\Log::error('订单成功，异步未写入的订单号：' . $val);
                }

                $modelOrderNotify = new OrdersNotify();
                count($add_order_notify) && $modelOrderNotify->saveAll($add_order_notify);
            }
            Cache::set('order_notify_diff', time());
        }

        $cron_map = [1,2,3,7,10];
        $orders_notify = Db::name('orders_notify')
            ->where($order_wh)
            ->order('update_time asc')
            ->limit(1)
            ->field('order_id,times,create_time,content')
            ->select();
//        halt($orders_notify);
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
                    $result['content'] =  $take_out_order['content'] . (empty($take_out_order['content']) ? '' : ',,,,,') .   $result['result'];
                    (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->update($result);

                    \think\Log::notice('订单回调成功，订单号：' . $order['id']);
                }else if ($result  && $result['result'] != 'SUCCESS'){
                    //失败记录数据
                    (new OrdersNotify())->where(['order_id' => $take_out_order['order_id']])->update([
                        'times'   => $orderNotify['times'] + 1,
                        'update_time'=>time(),
                        'result' => $result['result'],
                        'content' => $take_out_order['content'] . (empty($take_out_order['content']) ? '' : ',,,,,') .   $result['result']
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
            }else{
                (new OrdersNotify())->where(['order_id' => $order['order_id']])->update([
                    'update_time'=>time(),
                ]);
            }

        }

    }


    private function doOrderCallback($data, $times)
    {
        //要签名的数据
        $where = array();
        $where['uid'] = $data['uid'];
        $LogicApi = new \app\common\logic\Api();
        //$admin_id =Db::name('user')->where($where)->value('admin_id');
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
            if ($times == 0){
                \think\Log::notice('中转服务器97.74.83.35回调第'.($times + 1).'次');
                \think\Log::notice('中转服务器97.74.83.35回调'.$orginal_host);
                $url = 'http://97.74.83.35/zz.php?notify_url='.urlencode($data['notify_url']);
                $response = $client->request(
                    'POST', $url, ['form_params' => $to_sign_data,'timeout'=>5]
                );
            }elseif ($times == 1){
                \think\Log::notice('中转服务器45.207.58.203回调'.($times + 1).'次');
                \think\Log::notice('中转服务器45.207.58.203回调'.$orginal_host);
                $url = 'http://45.207.58.203/zz.php?notify_url='.urlencode($data['notify_url']);
                $response = $client->request(
                    'POST', $url, ['form_params' => $to_sign_data,'timeout'=>5]
                );
            }else{
                \think\Log::notice('本服务器回调'.($times + 1).'次');
                $response = $client->request(
                    'POST', $data['notify_url'], ['form_params' => $to_sign_data,'timeout'=>5]
                );

            }


//                if($proxy_debug  && $times >=2 && $orginal_host )
////                if(config('proxy_debug') && $attempts >=2 )
//                {
//                    \think\Log::notice('中转服务器回调'.$times);
//                    \think\Log::notice('中转服务器回调'.$orginal_host);
//                    //是否需要代理服务器处理让代理请求
////                    $hosts = config('orginal_host');
//                    $hosts = $orginal_host;
//                    $url = $hosts.'?notify_url='.urlencode($data['notify_url']);
//                    $response = $client->request(
//                        'POST', $url, ['form_params' => $to_sign_data,'timeout'=>5]
//                    );
//
//                }else{
//                    \think\Log::notice('本服务器回调'.$times);
//                    $response = $client->request(
//                        'POST', $data['notify_url'], ['form_params' => $to_sign_data,'timeout'=>5]
//                    );

//                }

            $statusCode = $response->getStatusCode();
            $contents = $response->getBody()->getContents();
            Log::error('['. date('Y-m-d H:i:s',time()).']订单'.  $data['out_trade_no'] . '从'. ($zhongzhuan_address ?? '本机') .',第' . ($times+1) . '次回调，返回内容：' . $contents);
            \think\Log::notice("订单回调 notify url " . $data['notify_url'] . "data" . json_encode($to_sign_data).'返回内容:'.$contents);
            \think\Log::notice("response code: ".$statusCode." response contents: ".$contents);

            $notifyContent = (new \app\common\logic\OrdersNotify())->where(['order_id' => $data['id']])->value('content');
            (new \app\common\logic\OrdersNotify())->where(['order_id' => $data['id']])->update([
                'content' =>$notifyContent . (empty($notifyContent) ? '' : ',,,,,') .  $contents
            ]);

            print("<info>response code: ".$statusCode." response contents: ".$contents."</info>\n");
            // JSON转换对象
            if ( $statusCode == 200 && !is_null($contents)){
                //判断放回是否正确
//                    if ($contents == "SUCCESS"){
                //TODO 更新写入数据
                return [
                    'result'   => $contents,
                    'is_status'   => $statusCode,
                    'update_time' => time()
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
    const SUSSCE = 1;
    const FAIL = 1;
    /**
     * 构建返回数据对象
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return array
     */
    private function buildSignData($data,$md5Key,$need_remark=false){
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
        if(!$need_remark){
            unset($data['remark']);
        }
        unset($data['visite_show_time']);
        unset($data['real_need_amount']);
        unset($data['image_url']);
        unset($data['request_log']);
        unset($data['visite_time']);
        unset($data['request_elapsed_time']);

        $data['amount'] = sprintf("%.2f", $data['amount']);
        $data['order_status'] = self::SUSSCE;
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
        //返回
        return $data;
    }




}
