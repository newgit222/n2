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

namespace app\common\service\worker;

use app\common\library\HttpHeader;
use app\api\service\Rest;
use app\common\model\Config;
use app\common\model\OrdersNotify;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\Db;
use think\Log;
use think\queue\Job;

class AutoOrderNotify
{
    const SUSSCE = 1;
    const FAIL = 1;
    /**
     * 延时
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @var int
     */
    protected static $delay = 1;

    /**
     * fire方法是消息队列默认调用的方法
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param Job $job
     * @param $data
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \app\common\library\exception\ParameterException
     */
    public function fire(Job $job,$data){
        // 如有必要,可以根据业务需求和数据库中的最新数据,判断该任务是否仍有必要执行.
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if(!$isJobStillNeedToBeDone){
            $job->delete();
            return;
        }
        //查单
        $order = (new OrdersNotify())->where(['order_id' => $data['id']]);
        //处理队列
        $result = $this->doJob($data,$job->attempts());
        if ($result) {
            //成功记录数据
            $order->update($result);
            //如果任务执行成功， 记得删除任务
            $job->delete();
            print("<info>The Order Job ID " . $data['id'] ." has been done and deleted"."</info>\n");
        }else{
            //失败记录数据
            $order->update([
                'times'   => $job->attempts(),
                'update_time'=>time()
            ]);
            if ($job->attempts() >= 3) {
                //超过5次  停止发送
                print("<warn>The Order Job ID " . $data['id'] . " has been deleted and retried more than 5 times!" . "</warn>\n");
                $job->delete();
            }else{
                print("<info>The Order Job ID " . $data['id'] ." will be availabe again after ". $job->attempts() * self::$delay ." s."."</info>\n");
               // $job->release($job->attempts() * self::$delay); //$delay为延迟时间，表示该任务延迟2秒后再执行
                $job->release(30);
            }

        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return bool
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     *
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \app\common\library\exception\ParameterException
     */
    private function doJob($data,$attempts) {
        // 根据消息中的数据进行实际的业务处理...
        if ($data['create_time'] <= time()){
            //要签名的数据

            $where = array();
            $where['uid'] = $data['uid'];
            $LogicApi = new \app\common\logic\Api();
            $appKey = $LogicApi->getApiInfo($where, "key",($data['uid']==100063||$data['uid']==100068|| $data['uid']==100067));
            $to_sign_data =  $this->buildSignData($data, $appKey["key"],($data['uid']==100063||$data['uid']==100068|| $data['uid']==100067));
            //签名串
            print("<info>posturl: ".$data['notify_url']."</info>\n");
            print("<info>sign data: ".json_encode($to_sign_data)."</info>\n");

            Log::notice("\r\n");
            Log::notice("posturl: ".$data['notify_url']);
            Log::notice("sign data: ".json_encode($to_sign_data));
            try{
                $client = new Client();
                $Config = new Config();
                $proxy_debug = $Config->where(['name'=>'transfer_ip_list'])->value('value');
                $orginal_host = $Config->where(['name'=>'orginal_host'])->value('value');
	       $time=5;
                if($data['uid']==100110||$data['uid']==100099){
                $time = 9;
                }
                if($proxy_debug  && $attempts >2 && $orginal_host )
//                if(config('proxy_debug') && $attempts >=2 )
                {
                    Log::notice('中转服务器回调'.$attempts);
                    Log::notice('中转服务器回调'.$orginal_host);
                    //是否需要代理服务器处理让代理请求
//                    $hosts = config('orginal_host');
                    $hosts = $orginal_host;
                    $url = $hosts.'?notify_url='.urlencode($data['notify_url']);
                    $response = $client->request(
                    'POST', $url, ['form_params' => $to_sign_data,'timeout'=>5]
                    );

                }else{
                    Log::notice('本服务器回调'.$attempts);
                    $response = $client->request(
                        'POST', $data['notify_url'], ['form_params' => $to_sign_data,'timeout'=>5]
                    );

                }
                $statusCode = $response->getStatusCode();
                $contents = $response->getBody()->getContents();
                Log::notice("response code: ".$statusCode." response contents: ".$contents);
                print("<info>response code: ".$statusCode." response contents: ".$contents."</info>\n");
                // JSON转换对象
                if ( $statusCode == 200 && !is_null($contents)){
                    //判断放回是否正确
                    if ($contents == "SUCCESS"){
                        //TODO 更新写入数据
                        return [
                            'result'   => $contents,
                            'is_status'   => $statusCode
                        ];
                    }
                    return false;
                }
                return false;
            }catch (RequestException $e){
                Log::error('Notify Error:['.$e->getMessage().']');
                return false;
            }
        }
        return false;
    }

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
