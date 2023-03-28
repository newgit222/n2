<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/2/14
 * Time: 20:15
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/**壹付宝支付
 * Class YfbPay
 * @package app\api\service\payment
 */
class YifubaoPay extends ApiPayment
{


    protected $md5Key = 'ba6ca4c659a086b150d333ad456ebb59';



    /**
     * 生成签名
     * @param $data
     * @param $md5Key
     * @param $privateKey
     * @return mixed
     */
    public function getSign($data, $md5Key)
    {
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $val) {
            //空值不参与签名
            if ($val == '' || $key == 'sign') {
                continue;
            }
            $arg .= ($key . '=' . $val . '&');
        }
        $arg = $arg . 'key=' . $md5Key;
        //签名数据转换为大写
        $sign = strtoupper(sha1($arg));
        return $sign;
    }


    /**
     * 统一下单
     */
    private function pay($order, $type = 'weixin-h5')
    {
        //请求格式
        $merId = '1610355086103';
//        $appnoNo ='1';
        $post  = [
            'mid' => $merId,
           // 'appno_no'      => $appnoNo,
            'm_order_no'      => $order['trade_no'],
            'amount'  => sprintf("%.2f", $order["amount"]),
            'trans_time'    => date('YmdHis'),
            'good_name'  => 'goods_' . rand(0000, 9999),
            'good_code'  => 'goods_code' . rand(0000, 9999),
            'user_no'       => 'user_' . rand(0000, 9999),
            'notify_url'    => $this->config['notify_url'],
            'pay_code'      => $type,
            'success_url'    => $this->config['return_url'],
            'm_ip'   => request()->ip(),
        ];
//var_dump($data);die();
        $url   = 'https://api.yfb77.com/createOrder';
        //私钥签名
        $post['sign']     = $this->getSign($post, $this->md5Key);
//        $post['signtype'] = 'md5';
  //      $post['transdata'] = urlencode(json_encode($data));
        $headers = array("Content-type: application/json;charset='utf-8'");
        $result =  json_decode(self::curlPost($url,json_encode($post),[CURLOPT_HTTPHEADER=>$headers],20),true);
//var_dump($result);die();
        if ($result['code'] != '0') {
            Log::error('Create YfbPay API Error:' . $result['message']);
            throw new OrderException([
                'msg'     => 'Create YfbPay API Error:' . $result['message'],
                'errCode' => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }


    /**
     * 微信扫码支付
     * @param $params
     */
    public function wap_vx($params)
    {
        //获取预下单
        $url = $this->pay($params, 'weixin-h5');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params)
    {
        //获取预下单
        $url = $this->pay($params, 'weixin-h5');
        return [
            'request_url' => $url,
        ];
    }

 public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, 'zhifubao');
        return [
            'request_url' => $url,
        ];
    }


 public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params, 'zhifubao');
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
//$input = '{"sign":"azgTrP%2FmthL7nWjd6Q8%2BWs3MEavsn%2BYVzFkuLDrSmYVIvNtswVmv%2Bc5TJxobnUKPtxn1J68qsBKY%0D%0A%2BGBcdxn9y%2FTrIfhItQNYC12H4FvrDB57blWZiglUH39k9m0A%2BIvwfIr5ndU6L6AAmKqWyB%2FSIPBc%0D%0ArN5WQOaZO%2BO%2BttjJctw%3D","transdata":"%7B%22order_no%22%3A%22116103878583743892989%22%2C%22order_amount%22%3A%22100.00%22%2C%22pay_type%22%3A%22weixin-h5%22%2C%22payment%22%3A%22%E6%94%AF%E4%BB%98%E6%88%90%E5%8A%9F%22%2C%22order_time%22%3A1610387859000%2C%22product_code%22%3A%22goods_code8515%22%2C%22product_name%22%3A%22goods_2684%22%2C%22user_no%22%3A%22user_3745%22%7D"}';
        $input = file_get_contents("php://input");
        Log::notice("YfbPay notify data" . $input);
        $notifyData = json_decode($input, true);
//        $notifyData = json_decode(urldecode($notifyData['transdata']));
//var_dump( $notifyData->order_no);die();
        if ( 1) {
            echo "success";
            $data['out_trade_no'] = $notifyData['m_order_no'];
            return $data;
        }
        echo "error";
        Log::error('YfbPay API Error:' . $input);
    }
}

