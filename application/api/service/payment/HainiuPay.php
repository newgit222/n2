<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/27
 * Time: 0:52
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class HainiuPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='909'){

        $url = 'http://gdyhyq.cn/api/unifiedorder';

        $mch_key = '3e3520c48cbe488aaeb0cec0b42cb9d7';
        $data = [
            'mch_id'    =>  '1010',
            'pass_code'    =>  $type,
            'subject'    =>  'goods',
            'body'    =>  'goods',
            'out_trade_no'    =>  $order['trade_no'],
            'amount'    =>  sprintf("%.2f",$order["amount"]),
            'client_ip'    =>  get_userip(),
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    =>  $this->config['return_url'],
            'timestamp'    =>  date('Y-m-d H:i:s'),
        ];
        $data['sign'] = $this->sign($data,$mch_key);
        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        if($result['code'] != '0' )
        {
            Log::error('Create SitongPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create SitongPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['pay_url'];

    }


    public function query($notifyData){
	return true;

        $url = 'http://niuee.cn/api/query';

        $mch_key = '0fa67e3026584509b812746147d6b934';

        $data = [
            'mch_id'    =>  '1008',
            'out_trade_no'    =>  $notifyData['out_trade_no'],
            'timestamp'    =>  date('Y-m-d H:i:s'),
        ];


        $data['sign'] = $this->sign($data,$mch_key);

        $result =  json_decode(self::curlPost($url,json_encode($data)),true);
        Log::notice('query SitongPay  API notice:'.json_encode($result));
        if($result['code'] != '0' ){
            return false;
        }
        if($result['data']['status'] != '2' ){
            return false;
        }
        return true;
    }


    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilters($para)
    {
        $para_filter = [];

        while (list ($key, $val) = each ($para)) {

            if ($key == "sign" || $val == "") continue;

            else $para_filter[$key] = $para[$key];
        }

        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    private function argSorts($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }




    /**
     * 签名验证-平台
     * $datas 数据数组
     * $key 密钥
     */
    private function sign ($datas = [], $key = "")
    {
        $str = urldecode(http_build_query($this->argSorts($this->paraFilters($datas))));
        $sign = md5($str.$key);

        return $sign;
    }






    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'909');
        return [
            'request_url' => $url,
        ];
    }






    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
        //获取预下单
        $url = $this->pay($params,909);
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {

        $input = file_get_contents("php://input");
        Log::notice("SitongPay notify data".$input);
        $notifyData = json_decode($input,true);
//        $notifyData =$_POST;
//        Log::notice("SitongPay notify data1".json_encode($notifyData));
        if($notifyData) {
            if (isset($notifyData['out_trade_no'])) {
                if ($this->query($notifyData)) {
                    echo "success";
                    $data['out_trade_no'] = $notifyData['out_trade_no'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('SitongPay API Error:'.json_encode($notifyData));
    }
}
