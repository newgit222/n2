<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/7/12
 * Time: 15:42
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use think\Log;

class VgPay extends ApiPayment
{

    /**
     * 统一下单
     */
    private function pay($order,$type='VXHF'){

        $url = 'https://www.vgzhifu.com/orders/public/tradePay/data/pay';

        $merkey = 'WL7W7KSE05IPX0GOFH8T2OJ4JJ1H2VYYQXPH3JPW2PRTU2M961WMUPBBEAYIKN4GU3MKLOVU107AOT37BWHFF6IT7GFDR0Z3642AQLM1141QFGM7QYNS03Q9EMBORWAI';


        $data = [
            'client_id' =>  'VG0133',
            'api_order_sn' =>  $order['trade_no'],
            'type' =>  $type,
            'total' =>  sprintf("%.2f",$order["amount"]),
            'content' =>  'fff',
            'goods_name' =>  'goods',
	    'sign_type' => 'MD5',
	    'client_pay_ip'=>'127.0.0.1',
            'timestamp'=>date('Y-m-d H:i:s'),
            'return_url' =>  $this->config['return_url'],
            'notify_url' =>  $this->config['notify_url'],
        ];
//var_dump($data);
        $data['sign'] = $this->sign($data,$merkey);
$data['data_type'] = 1;
//$data['request_post_url']=$url;
// return "http://paofen.byfbqgi.cn/pay.php?".http_build_query($data);



        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Accept: application/json",
        );
//var_dump(self::curlPost($url,($data),[CURLOPT_HTTPHEADER=>$headers]));die();
        $result =  json_decode(self::curlPost($url,($data),[CURLOPT_HTTPHEADER=>$headers]),true);
//var_dump($result);die();
        if($result['code']!=200)
        {
            Log::error('Create BaianPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create BaianPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['pay_params']['qr_url'];
    }


    private function query($notifyData){
        $url = 'http://59.153.74.103:7768/api/orders/query';

        $merkey = '5feeee3820aae4cbb37b7e01a807e455';

        $data = [
            'client_id' =>  '108',
            'out_trade_no' =>  $notifyData['out_trade_no'],
            'trade_no' =>  $notifyData['trade_no'],
            'trade_type' =>  $notifyData['trade_type'],
            'total_amount' =>  $notifyData['total_amount'],
            'nonce_str' =>  self::createNonceStr(),
        ];
        $data['sign'] = $this->sign($data,$merkey);
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Accept: application/json",
        );
        $result =  json_decode(self::curlPost($url,($data),[CURLOPT_HTTPHEADER=>$headers]),true);
        Log::notice('query BaianPay  API notice:'.json_encode($result));

        if(isset($result['message'])){
            Log::error('query BaianPay  API Error:'.$result['message']);
            return false;
        }
        if(!isset($result['status']) ){
            return false;
        }

        if( $result['status'] != '1' ){
            return false;
        }
        return true;
    }


    function sign($arr, $sign_key) {
        ksort($arr);
        $query = [];
        foreach ($arr as $key => $val) {
            if ($val != '' && $val != 'null' && $val != null && $key != 'sign' && $key != 'key') {
                $query[] = $key . "=" . $val;
            }
        }
        $str = implode('&', $query)  . $sign_key;
//echo $str;
        return md5($str);
    }


    /**
     * @param $params
     * 支付宝
     */
    public function wap_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay');
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
//$input ='client_id=VG0133&api_order_sn=116102752671253162491&order_sn=VG00T20210110184108524810&income=50.00&success_time=20210110184303&callbacks=CODE_SUCCESS&sign=b813577b628ef5ddbb6d2b3cc0a5fc30&attach=';
//parse_str($input,$notifyData);
//var_dump($notifyData);
       $input = file_get_contents("php://input");
        Log::notice("BaianPay notify data".$input);
parse_str($input,$notifyData);
      // $notifyData = json_decode($input,true);
//        if( isset($notifyData['status']) &&  $notifyData['status'] == "1" ){

//            if($this->query($notifyData)) {
                echo "success";
                $data['out_trade_no'] = $notifyData['api_order_sn'];
                return $data;
//            }
  //      }
        echo "error";  
        Log::error('BaianPay API Error:'.$input);
    }
}
