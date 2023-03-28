<?php


namespace app\common\model;



use think\Log;

class TgQueryOrderRecords extends BaseModel
{

    protected $autoWriteTimestamp=false;

    /**
     * 添加查单记录（订单为完成的）
     * @param $uid
     * @param $order_no
     * @param $tg_message_id
     * @param $tg_group_id
     * @param int $success
     * @return false|int
     */
    public function saveRecords($uid, $order_no, $tg_message_id, $tg_group_id, $success = 0)
    {
        $TgQueryOrderRecords = $this->where('order_no', '=', $order_no)->find();
        $insert_data = [
            'uid'           => $uid,
            'order_no'      => $order_no,
            'tg_message_id' => $tg_message_id,
            'tg_group_id'   => $tg_group_id,
            'success'       => $success,
        ];
        if (!$TgQueryOrderRecords){
            $insert_data['create_time']  =time();
            $isUpdate = false;
        }else{
            $insert_data['id']  = $TgQueryOrderRecords->id;
            $isUpdate = true;
        }
        return $this->isUpdate($isUpdate)->save($insert_data);
    }

    /**
     * 转发tg消息，订单成功
     * @param $order_no
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tgForwardingOrderSuccess($order_no)
    {
        $TgQueryOrderRecords = $this->where('order_no', '=', $order_no)->find();
        print_r('$TgQueryOrderRecords:' . $TgQueryOrderRecords . PHP_EOL);
        if ($TgQueryOrderRecords && $TgQueryOrderRecords->success == 0 ){
            //发送消息到tg 订单完成
            $this->logicTgLogic->sendMessageTogroup($order_no.' 成功', $TgQueryOrderRecords->tg_group_id,
                ['reply_to_message_id' => $TgQueryOrderRecords->tg_message_id]);
            print_r('消息发送成功' . PHP_EOL);
            $TgQueryOrderRecords->success =  1;
            $TgQueryOrderRecords->save();
            Log::notice('发送tg商户群组订单成功消息:'.$order_no);
        }
    }
}