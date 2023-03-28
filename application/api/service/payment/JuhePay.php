<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/11
 * Time: 16:44
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class JuhePay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='937'){


        $url = 'https://pro.sanguou.com/Api.html';
        $data = [
            'pay_memberid'  =>  '10333',
            'pay_orderid'  =>  $order['trade_no'],
            'pay_bankcode'  =>  $type,
            'pay_notifyurl'  =>   $this->config['notify_url'],
            'pay_amount'  =>  sprintf("%.2f",$order["amount"]),
            'pay_attach'  =>  'goods',
            'pay_getip'  =>  get_userip(),
        ];


        $merkey = 'pb79vtldxfdhbj0mmt8adblyln6ttjb5';
        $data['sign'] =$this->sign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != 'success' )
        {
            Log::error('Create JuhePay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create JuhePay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payurl'];
    }


    /**
     * 查询
     */
    public function query($notifyData){
        $url = 'https://pro.sanguou.com/api_trade_query.html';
        $data = [
            'pay_memberid'  =>  '10333',
            'pay_orderid'  =>  $notifyData['orderid'],
        ];
        $merkey = 'pb79vtldxfdhbj0mmt8adblyln6ttjb5';
        $data['sign'] =$this->sign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        Log::notice('query JuhePay  API notice:'.json_encode($result));
        if(  $result['code'] != 'success' ){
            Log::error('query JuhePay  API Error:');
            return false;
        }
        if($result['data']['trade_state'] != 'success' ){
            return false;
        }
        return true;
    }




    function sign($parameters, $sign_key) {
        ksort($parameters);
        $signPars = "";
        foreach($parameters as $k => $v) {
            if("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
//        $signPars = substr($signPars,0,strlen($signPars) - 1).$key;
        $signPars .=  'key='.$sign_key;
        $sign = md5($signPars);
        return $sign;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'937');
        return [
            'request_url' => $url,
        ];
    }

  public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'937');
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
        $notifyData = $_POST;
        Log::notice("JuhePay notify data1".json_encode($notifyData));

        if($notifyData['code'] == "success" ){
            $datas = json_decode($notifyData['data'],true);
            if($this->query($datas)) {
                echo "SUCCESS";
                $data['out_trade_no'] = $datas['orderid'];
                return $data;
            }
        }
        echo "ERROR";
        Log::error('JuhePay API Error:'.json_encode($notifyData));
    }
}
