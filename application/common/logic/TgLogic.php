<?php


namespace app\common\logic;


/**
 * Tg逻辑出处理
 * Class TgLogic
 * @package app\common\logic
 */
class TgLogic extends BaseLogic
{


    protected $tgToken;

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (isset($data['tg_bot_type'])&&$data['tg_bot_type'] === 'df'){
            $this->tgToken = \app\common\model\Config::where(['name' => 'daifu_tgbot_token'])->value('value');
        }else{
            $this->tgToken = \app\common\model\Config::where(['name' => 'global_tgbot_token'])->value('value');
        }

    }

    /**
     * 设置全机器人回调通知地址
     * @param $webHookUrl
     * @return mixed
     */
    public function setWebHook($webHookUrl)
    {
        $url = 'https://api.telegram.org/bot' . $this->tgToken . '/setwebhook';
        $data = [
            'url' => $webHookUrl,
        ];
        return json_decode(httpRequest($url, 'POST', $data), true);
    }


    /**
     * 特殊处理的text文本前缀
     */
    protected function setPrefix()
    {
        $prefixes = [
            'channel', 'mch', 'ms'
        ];
        return $prefixes;
    }


    /**
     * 向某个群组发送消息
     * @param $text
     * @param $chat_id
     * @param array $option
     * @return mixed
     *
     */
    public function sendMessageTogroup($text, $chat_id, $option = [])
    {
        $url = 'https://api.telegram.org/bot' . $this->tgToken . '/sendMessage';
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
        ];
        $data = array_merge($data, $option);
        return json_decode(httpRequest($url, 'POST', $data), true);
    }


    /**
     * 转发消息到另外的群组
     * @param $from_chat_id
     * @param $message_id
     * @param $chat_id
     * @return mixed
     */
    protected function forwardMessageTogroup($from_chat_id, $message_id, $chat_id)
    {
        $url = 'https://api.telegram.org/bot' . $this->tgToken . '/forwardMessage';
        $data = [
            'from_chat_id' => $from_chat_id,
            'message_id' => $message_id,
            'chat_id' => $chat_id,
        ];
        return json_decode(httpRequest($url, 'POST', $data), true);
    }


    /**
     * 发送图片
     * @param $chat_id
     * @param $photo
     * @param array $option
     * @return mixed
     */
    public function sendPhoto($chat_id, $photo, $option = [])
    {
        $url = 'https://api.telegram.org/bot' . $this->tgToken . '/sendPhoto';
        $data = [
            'chat_id' => $chat_id,
            'photo' => $photo,
        ];
        $data = array_merge($data, $option);
        return json_decode(httpRequest($url, 'POST', $data), true);
    }


    /**
     * 处理文本信息
     *
     * @param $message
     */
    public function handleText($message)
    {
        $text = $message['text'];
        if (!isset($text) || empty($text)) {
            return;
	}
        //解析每个文本
        if (strpos($text, ':') !== false && in_array(explode(':', $text)[0], $this->setPrefix())) {
            $prefix = explode(':', $text);

            if ($prefix[0] == 'channel' && strlen($prefix[1]) == 32) {
                //当前群组和渠道绑定
                $this->logicPay->bindTgGroupIdtoChannelBySercert($prefix[1], $message['chat']['id']);
                $this->sendMessageTogroup("恭喜绑定成功", $message['chat']['id']);
                return;
            }

            if ($prefix[0] == 'mch' && strlen($prefix[1]) == 32) {
                //当前群组和商户绑定
                $this->logicUser->bindTgGroupIdtoUserBySercert($prefix[1], $message['chat']['id']);
                $this->sendMessageTogroup("恭喜绑定成功", $message['chat']['id']);
                return;
	        }

            if ($prefix[0] == 'ms' && strlen($prefix[1]) == 32) {
		        //当前群组和商户绑定
                $TgLogic = new TgLogic(['tg_bot_type' => 'df']);
                $this->logicMs->bindTgGroupIdtoUserBySercert($prefix[1], $message['chat']['id']);
                $TgLogic->sendMessageTogroup("恭喜绑定成功", $message['chat']['id']);
                return;
            }

        }

        if ($text== '成功') {
            //默认是渠道群对于查单success
            $orderNo = trim(str_replace('成功', '', $text), ' ');
            if (empty($orderNo) && array_key_exists('reply_to_message', $message)) {
                //尝试从回复中取出订单号
                $orderNo = $message['reply_to_message']['caption'];
            }
            $tg_group_id = $this->modelOrders->alias('a')
                ->join('user b', 'a.uid=b.uid')
                ->where(['out_trade_no' => $orderNo])
                ->value('tg_group_id');
            //查单的消息id
            $messageId = cache('search_order_no_' . $orderNo);
    //        $tg_group_id && $messageId && $this->sendMessageTogroup($orderNo . ' 成功', $tg_group_id, ['reply_to_message_id' => $messageId]);

        }
        if ($text == '下发' && !array_key_exists('photo',$message)) {
            //是否来自商户群
            $isMessageFromMch = $this->modelUser->where('tg_group_id', $message['chat']['id'])->count();
            $isMessageFromMch && $this->sendMessageTogroup("请稍等，下发正在为您处理中", $message['chat']['id']);
        }
    }


    /**
     * 处理图片事件
     * @param $message
     */
    public function handlePhoto_old($message)
    {
        if (isset($message['caption'])) {
            //查找的单号
            $orderNo = trim($message['caption']);
            //查询对应渠道
            $tg_group_id = $this->modelOrders->alias('a')
                ->join('pay_account b', 'a.cnl_id=b.id')
                ->join('pay_channel c', 'b.cnl_id=c.id')
                ->where(['trade_no' => $orderNo])
                ->value('c.tg_group_id');
            if ($tg_group_id == $message['chat']['id']) {
                return true;

            }
            //发送正在处理中请稍后
            if ($tg_group_id) {
                $this->sendMessageTogroup('请稍等，马上为您处理', $message['chat']['id'], ['reply_to_message_id' => $message['message_id']]);
                //转发到渠道群
                $optionPhoto['caption'] = $orderNo;
                //待回复的消息消息#####通过这个消息发送的查单请求
                cache('search_order_no_' . $orderNo, $message['message_id'], 3600);
                $this->sendPhoto($tg_group_id, $message['photo'][0]['file_id'], $optionPhoto);
                return true;
            }
        }
    }
     public function handlePhoto($message)
    {
        if (isset($message['caption'])) {
            //查找的单号
            $text = trim($message['caption']);
            if($text == '下发')
            {
                //是否来自商户群
                $isMessageFromMch = $this->modelUser->where('tg_group_id', $message['chat']['id'])->count();
                $isMessageFromMch && $this->sendMessageTogroup("请稍等，下发正在为您处理中", $message['chat']['id']);
                return true;
            }
            $orderNo = $text;


            //查询对应渠道
            $order = $this->modelOrders->alias('a')
                ->join('pay_account b', 'a.cnl_id=b.id')
                ->join('pay_channel c', 'b.cnl_id=c.id')
                ->where(['trade_no' => $orderNo])
                ->field('a.uid, a.status, c.tg_group_id')
                ->find();
//                ->value('c.tg_group_id');
            if (isset($order['tg_group_id']) && $order['tg_group_id'] == $message['chat']['id']) {
                return true;

            }

            //如果为码商订单&&姓名金额不符合
            $ewmOrder = $this->modelEwmOrder->where('order_no', $orderNo)->find();
            if ($ewmOrder && ($ewmOrder['name_abnormal'] or $ewmOrder['money_abnormal'])){
                $abnormalStr = '';
                $ewmOrder['name_abnormal'] && $abnormalStr .= $orderNo  .  ' 不是本人充值，这笔触发了我们的反洗钱机制，怀疑是洗黑钱的，看看这笔是要上分还是原路退回，半年以上充值记录好的老会员可以上分，新会员原路退回';
                $ewmOrder['money_abnormal'] && $abnormalStr .= ($ewmOrder['name_abnormal'] ? '&' : '') . $orderNo.' 会员下单金额和实际金额不符合,正在核实请耐心等待';
                $this->sendMessageTogroup($abnormalStr, $message['chat']['id'], ['reply_to_message_id' => $message['message_id']]);
                if ($order['status'] != 2){
                    $this->modelTgQueryOrderRecords->saveRecords($order['uid'], $orderNo, $message['message_id'], $message['chat']['id'], 0 );
                }
                return true;
            }

            //发送正在处理中请稍后
            if (isset($order['tg_group_id']) && $order['tg_group_id']) {
                //转发到渠道群
                $optionPhoto['caption'] = $orderNo;
                //待回复的消息消息#####通过这个消息发送的查单请求
                cache('search_order_no_' . $orderNo, $message['message_id'], 3600);
                //写入查单记录 (如果订单支付发送消息成功)
                if ($order['status'] == 2){
                    $this->sendMessageTogroup($orderNo.' 成功', $message['chat']['id'], ['reply_to_message_id' => $message['message_id']]);
                }else{
                   $this->sendMessageTogroup('请稍等，马上为您处理', $message['chat']['id'], ['reply_to_message_id' => $message['message_id']]);
                   $this->modelTgQueryOrderRecords->saveRecords($order['uid'], $orderNo, $message['message_id'], $message['chat']['id'], 0 );
                   $this->sendPhoto($order['tg_group_id'], $message['photo'][0]['file_id'], $optionPhoto);
                }
                return true;
            }
        }
    }
}
