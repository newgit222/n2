<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/27
 * Time: 0:52
 */

namespace app\api\service\payment;
//接口地址 /api/unifiedorder 参数 pass_code out_trade_no notify_url mch_id

use app\api\service\ApiPayment;
use think\Log;
use app\common\library\exception\OrderException;
use app\common\model\OrdersRequest;

class GuangyuanjsonPay extends ApiPayment
{
    /**
     * 统一下单
     */
    public function pay($order,$type='908'){

        $url = 'http://www.kezpay.com:8083/api/unifiedorder';

        $mch_key = 'c6a78a2966722ed8b39e24b42f430b68';
        $data = [
            'mch_id'    =>  '1605628677808599042',
            'pass_code'    =>  $type,
            'subject'    =>  'pay',
            'body'    =>  'pay',
            'out_trade_no'    =>  $order['trade_no'],
            'amount'    =>  sprintf("%.2f",$order["amount"]),
            'client_ip'    =>  get_userip(),
            'notify_url'    =>  $this->config['notify_url'],
            'return_url'    =>  $this->config['return_url'],
            'timestamp'    =>  date('Y-m-d H:i:s'),
        ];
	$data['sign'] = $this->getSign($data,$mch_key);
        Log::error('GuangyuanjsonPay Api Data:'.json_encode($data,true));

	$headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
        );
        $result =  self::curlPost($url,json_encode($data),[CURLOPT_HTTPHEADER=>$headers],20);
        Log::error('GuangyuanjsonPay Return Data:'.$result);
        //记录订单请求日志

        $result = json_decode($result, true);
        if($result['code'] != '0' )
	{
		var_dump($result);die();
        }
        return $result['data']['pay_url'];

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



    private function getSign($data, $secret)
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a,0,strlen($string_a) - 1);

        Log::error('GuangyuanjsonPay Sign Str:'.$string_a  . $secret);
        //签名步骤三：MD5加密
        $sign = md5($string_a  . $secret);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * 签名验证-平台
     * $datas 数据数组
     * $key 密钥
     */
    private function sign ($datas = [], $key = "")
    {
        $str = urldecode(http_build_query($this->argSorts($this->paraFilters($datas))));
        $sign = strtoupper(md5($str.$key));

        return $sign;
    }
   /**
     * @param $params
     * 支付宝
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, '101');
        return [
            'request_url' => $url,
        ];
    }


    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '201');
        return [
            'request_url' => $url,
        ];
    }
  public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, '201');
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
        if(1) {
            if (1) {
                if (1) {
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

