<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/22
 * Time: 22:42
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;


class GboPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='303'){


        $data['oid_partner'] = '202002231324264224';
        $data['user_id'] = '123456';
        $data['sign_type'] = 'MD5';
        $data['no_order'] = $order['trade_no'];
        $data['time_order'] = date('YmdHis');
        $data['money_order'] =sprintf("%.2f",$order["amount"]);
        $data['name_goods'] = 'goods';
        $data['pay_type'] = $type;
        $data['notify_url'] = $this->config['notify_url'];
        $data['return_url'] = $this->config['return_url'];

//        $parameter = $this->paraFilter($data);
        $parameter = $this->argSort($data);
        $signStr = urldecode(http_build_query($parameter));

        $sign = $this->md5Sign($signStr, 'a21075a36eeddd084e17611a238c7101');
        $data['sign'] = $sign;
        $url = 'http://api.djyq123.com/gateway/bankgateway/pay';
        $result =  json_decode(self::curlPost($url,http_build_query($data)),true);
        if($result['ret_code'] != '0000' )
        {
            Log::error('Create GboPay API Error:'.$result['ret_msg']);
            throw new OrderException([
                'msg'   => 'Create GboPay API Error:'.$result['ret_msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['redirect_url'];
    }


    /**
     * @param $params
     * 支付宝
     */
    public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'303');
        return [
            'request_url' => $url,
        ];
    }

    /**
     * @param $params
     * @return array
     * 微信
     */
    public function h5_vx($params)
    {
        //获取预下单
        $url = $this->pay($params,'304');
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

        $input = file_get_contents("php://input");
        \think\Log::notice("GboPay notify data".$input);
        $notifyData = json_decode($input,true);
//        {"money_order":197,"no_order":"115824587468026","oid_partner":"202002231324264224","oid_paybill":"58245874793342252","pay_type":303,"result_pay":"SUCCESS","sign_type":"MD5","time_order":"19700101080000","sign":"4498af97a0687d115a7bb6da3c299dc9"}
        if($notifyData['result_pay'] == "SUCCESS" ){
            echo "success";
            $data['out_trade_no'] = $notifyData['no_order'];
            return $data;
        }
        echo "error";
        Log::error('GboPay API Error:'.$input);
    }


    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para) {
        $para_filter = array();
//        while (list ($key, $val) = each ($para)) {
//            if($key == "sign" || $val == "")continue;
//            else	$para_filter[$key] = $para[$key];
//        }
        return $para_filter;
    }


    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para) {
        ksort($para);
        // var_dump($para);
        // exit();
        reset($para);
        return $para;
    }





    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

}