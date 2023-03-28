<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/23
 * Time: 14:41
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class FuchouzheV2Pay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='ali_scan_pay'){

        $url = 'http://api.fuchoupay.com/api/pay';  
        $mch_key = 'a228177d1d2e0472556e09d4214b4a6c';

        $data = [
            'merchant_no'  =>  'CZ202007230133485078',
            'merchant_order_no'  =>  $order['trade_no'],
            'notify_url'  =>  $this->config['notify_url'],
            'return_url'  =>  $this->config['return_url'],
            'start_time'  =>  date('YmdHis'),
            'amount'  =>  sprintf("%.2f",$order["amount"]),
            'pay_sence'  =>  $type,
            'goods_name'  =>  'goods',
            'goods_desc'  =>  'goods',
            'client_ip'  =>  get_userip(),
            'client_broswer'  =>  'pc',
            'sign_type'  =>  '1',
        ];


        $data['sign'] = $this->sign($data,$mch_key);

         $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '1' )
        {
            Log::error('Create FuchouzheV2Pay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create FuchouzheV2Pay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['code_url'];

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
        $sign = md5($str."&key=".$key);

        return $sign;
    }



    public function query($notifyData){

        $url = 'http://api.fuchoupay.com/api/find.html';

        $mch_key = 'a228177d1d2e0472556e09d4214b4a6c';
        $p_data=array(
            'merchant_no'   =>  'CZ202007230133485078',
            'trade_no'   =>  $notifyData['trade_no'],
            'merchant_order_no'   =>  $notifyData['merchant_order_no'],
            'sign_type'   =>  '1',
        );
        $p_data['sign'] = $this->sign($p_data,$mch_key);

         $result =  json_decode(self::curlPost($url,$p_data),true);
        Log::notice('query FuchouzheV2Pay  API notice:'.json_encode($result));
        if($result['code'] != '1' ){
            return false;
        }
        if($result['data']['status'] != '1' ){
            return false;
        }
        return true;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ali_scan_pay');
        return [
            'request_url' => $url,
        ];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'ali_bank_pay');
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
        $url = $this->pay($params);
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

        $notifyData =$_POST;
        Log::notice("FuchouzheV2Pay notify data1".json_encode($notifyData));
        if($notifyData) {
            if ($notifyData['status'] == "Success") {
                if ($this->query($notifyData)) {
                    echo "success";
                    $data['out_trade_no'] = $notifyData['merchant_order_no'];
                    return $data;
                }
            }
        }
        echo "error";
        Log::error('FuchouzheV2Pay API Error:'.json_encode($notifyData));
    }
}
