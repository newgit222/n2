<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *CartPay 支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class CartPay extends ApiPayment
{

    /*
     *获取签名
     * @param $data
     * @return string
     */
    public function getSign($data)
    {
        $key  = '71881e98f3f64974bc6cc7e60dc92eab';
        ksort($data);
        $signstr='';
        foreach($data as $k=>$v){
            $signstr.=$k.'='.$v.'&';
        }
        $signstr.='key='.$key;
        return   strtoupper(md5($signstr));
    }



    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $type='P_ZFBH5')
    {
        $data = "";
        // 商户APINMAE，WEB渠道一般支付
        // 商户在支付平台的平台号
        $data['customerid'] = '600005';
        //商户订单号
        $data['sdcustomno'] =$order['trade_no'];
        // 商户交易金额,必须保留为2位小数如100.00
        $data['orderamount'] = $order["amount"]*100;
        $data['cardno'] =$type;
        $data['zftype'] ='casher';
        $data['mark']='test123';
        $data['device']='mobile';
        $data['ordertime']=time();
        // 商户通知地址
        $data['noticeurl'] = $this->config['notify_url'];
        $data['backurl'] = $this->config['return_url'];
        $data['sign'] = $this->getSign($data);
        $request_url = "http://pay.catpay.xyz/gateway/payApiJson.asp";
        $ret =  self::curlPost($request_url,$data);
        dd($ret);
        $data['request_post_url'] ="http://pay.catpay.xyz/gateway/payApi.asp";
        return "http://aa.sviptb.com/pay.php?".http_build_query($data);

    }

    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
        dd($url);
        return [
            'request_url' => $url,
        ];
    }


    /*
     * WQ平台支付回调处理
     */
    public function notify()
    {
      try{

         // $notifyData = file_get_contents('php://input');
          $notifyData = $_POST;
         // file_put_contents('./test.log',json_encode($notifyData),FILE_APPEND);
          $sign=$notifyData['sign'];
          unset($notifyData['sign']);
          unset($notifyData['param']);//次参数要去掉 默认就是空的

          $mysign = $this->getSign($notifyData);

          if($mysign == $sign && $notifyData['status']==1){
              //处理业务逻辑
              echo 'success';
              $data["out_trade_no"] = $notifyData['orderNo'];
              return $data;
          }
          file_put_contents('./test.log','签名不正确',FILE_APPEND);

          throw new OrderException([
              'msg' => 'Create QH API Error:',
              'errCode' => 200009
          ]);
      }catch (\Exception $e){
          file_put_contents('./test.log','回调发生错误错误信息：'.$e->getMessage(),FILE_APPEND);
      }
    }



    /*
     *
     *同步通知地址处理逻辑
     */
    public function  callback()
    {
        // $plat_order_no= $_POST['out_trade_no'];
        //todo 查询订单信息的同步通知地址

        return [
            'return_url' =>'http://www.baidu.com'
        ];
    }

}
