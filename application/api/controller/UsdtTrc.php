<?php

namespace app\api\controller;
use app\api\service\ApiPayment;
use app\common\controller\BaseApi;
use app\common\library\enum\CodeEnum;
use app\common\logic\EwmOrder;
use app\ms\Logic\SecurityLogic;
use GuzzleHttp\Client;
use think\Cache;
use think\Log;
use think\Db;
class UsdtTrc extends BaseApi
{
    public function callBack(){
        //查找所有在线的码商
        $start = time()-599;
        $end = time();
        $codes = Db::name('ewm_order')
            ->where([
                'code_type'=>53,
                'status'=>0,
            ])
            ->where('add_time','between',[$start,$end])
            ->order('id desc')
            ->column('code_id');
        $codes = array_unique($codes);
        if (empty($codes)){
            return 'null';
        }
        $online_usdt = Db::name('ewm_pay_code')
            ->where('id','in',$codes)
            ->where('code_type',53)
            ->where('is_delete',0)
            ->where('is_lock',0)
            ->where('status',1)
            ->field('id,account_number')
            ->select();

        foreach ($online_usdt as $k=>$v){
            $this->query_bill($v['account_number'],$v['id']);
        }
        echo "success";
    }


    private function query_bill($usdt_address,$code_id){
        $http_url = 'https://apilist.tronscanapi.com/api/new/token_trc20/transfers?limit=30&start=0&sort=-timestamp&count=true&filterTokenValue=1&relatedAddress=';
        $http_url .= $usdt_address;
        $client = new Client();
        $result = $client->get($http_url);
        if ($result->getStatusCode() == 200){
            $data = json_decode($result->getBody()->getContents(),true);
            $rangeTotal = Cache::get('usdt_chaxun_'.$usdt_address.'count');
            // print_r($rangeTotal);die;
            Cache::set('usdt_chaxun_'.$usdt_address.'count',$data['rangeTotal']);
//            $rangeTotal=55;
            if (empty($rangeTotal)){
                $bill = $data['token_transfers'];
                foreach ($bill as $key=>$val){
                    if (substr($val['block_ts'],0,10) < time() - 599){
                        unset($bill[$key]);
                        continue;
                    }
                }

                //查订单 订单有效期内 10分钟内的;
                foreach ($bill as $key=>$val){
                    if ($val['to_address'] == $usdt_address){
                        $usdt_num = bcdiv($val['quant'], str_pad(1,$val['tokenInfo']['tokenDecimal']+1,0,STR_PAD_RIGHT) , 3);
                        //   print_r($usdt_num);die;
                        $start = time()-599;
                        $end = time();
                        $find_order = Db::name('ewm_order')
                            ->where([
                                'code_id'=>$code_id,
                                'code_type'=>53,
                                'extra'=>trim($usdt_num),
                                'status'=>0,
                            ])
                            ->where('add_time','between',[$start,$end])
                            ->order('id desc')
                            ->find();

                        if ($find_order){
                            $ewm = new EwmOrder();
                            $ewm->setOrderSucess($find_order, "协议自动回调",$find_order['gema_userid'],'协议');
                        }
                        continue;
                    }
                    continue;
                }
            }else{
                $query_num = $data['rangeTotal'] - $rangeTotal;
                if ($query_num == 0 || $rangeTotal < 0){
                    return '没有可以更新的条目';
                }
                $bill = array_slice($data['token_transfers'],0,$query_num-1);
                foreach ($bill as $key=>$val){
                    if (substr($val['block_ts'],0,10) < time() - 599){
                        unset($bill[$key]);
                        continue;
                    }
                }

                //查订单 订单有效期内 10分钟内的;
                foreach ($bill as $key=>$val){
                    if ($val['to_address'] == $usdt_address){
                        $usdt_num = bcdiv($val['quant'], str_pad(1,$val['tokenInfo']['tokenDecimal']+1,0,STR_PAD_RIGHT) , 3);
                        $start = time()-599;
                        $end = time();
                        $find_order = Db::name('ewm_order')
                            ->where([
                                'code_id'=>$code_id,
                                'code_type'=>53,
                                'extra'=>trim($usdt_num),
                                'status'=>0,
                            ])
                            ->where('add_time','between',[$start,$end])
                            ->order('id desc')
                            ->find();

                        if ($find_order){
                            $ewm = new EwmOrder();
                            $ewm->setOrderSucess($find_order, "协议自动回调",$find_order['gema_userid'],'协议');
                            continue;
                        }
                        continue;
                    }

                }
            }


        }
    }
}