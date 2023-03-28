<?php

namespace app\api\service\payment;
use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

/*
 *WQpay  支付渠道服务类
 * Class WqPay
 * @package app\api\service\payment
 */
class WqPay extends ApiPayment
{

    /*
     *获取签名
     * @param $data
     * @return string
     */
    public function getSign($data)
    {
        $key  = '719A679A192A8BA3CDCDE09985B3177D';
        ksort($data);
        $signstr='';
        foreach($data as $k=>$v){
            $signstr.=$k.'='.$v.'&';
        }
        $signstr.='key='.$key;
        return   md5($signstr);
    }



    /*
    *  taobao _pay  统一下单
    *
    */
    private  function getPayUnifiedOrder($order, $type='ALIPAYH5')
    {
        $data = "";
        // 商户APINMAE，WEB渠道一般支付
        // 商户在支付平台的平台号
        $data['uid'] = '1079';
        // 支付平台分配给商户的账号
        // 商户通知地址
        $data['notifyUrl'] = $this->config['notify_url'];
        $data['returnUrl'] = $this->config['return_url'];

        //商户订单号
        $data['orderNo'] =$order['trade_no'];

        // 商户订单日期
        $data['tradeDate'] = date('Ymd');
        // 商户交易金额,必须保留为2位小数如100.00
        $data['tradeAmt'] = sprintf('%.2f',$order["amount"]);

        //微信或支付宝必填，网银不为空则直连，，不为空时银行编号必须不为空
        $data['payType'] =$type;
        // 银行代码，微信或支付宝必填，网银不为空则直连
        $data['bankCode'] = $type;
        $data['sign'] = $this->getSign($data);
        $data['request_post_url'] ="http://api.wqsr.com.cn/pay.php";
        return "http://aa.sviptb.com/pay.php?".http_build_query($data);

    }

    public function h5_zfb($params)
    {
        //获取预下单
        $url = self::getPayUnifiedOrder($params);
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
