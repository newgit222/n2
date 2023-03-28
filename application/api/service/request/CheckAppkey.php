<?php

/**
 *  +----------------------------------------------------------------------
 *  | 中通支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\api\service\request;

use app\common\library\exception\ParameterException;
use app\common\library\HttpHeader;
use think\Log;
use think\Request;

/**
 * 检验app授权key
 *
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 *
 */
class CheckAppkey extends ApiCheck
{
    public static $bankPayCodes = [
        'guma_yhk', 'guma_bzk'
    ];


    /**
     * 校验app key
     *
     * @param Request $request
     * @return mixed|void
     * @throws ParameterException
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    public function doCheck(Request $request)
    {
        // 获取app key Map
        /*  $appKeyMap = (array)$this->logicApi->getAppKeyMap();
          if (!in_array(self::get(HttpHeader::X_CA_AUTH),$appKeyMap)) {
              throw new ParameterException([
                  'msg'=>'Invalid Request.[ Auth Key No permission or nexistencet.]',
                  'errorCode'=> 400003
              ]);
          }
         }
          */

        //支付方式判断
        $appCodeMap = (array)$this->logicPay->getAppCodeMap();
        $channel = $request->param('channel');

        if (empty($channel) || !in_array($channel, $appCodeMap)) {

            throw new ParameterException([
                'msg' => 'Invalid Request.[ Payment Code Does Not Allowed.]' . $channel,
                'errorCode' => 400003
            ]);
        }
        if (strtolower($request->action() != 'query')) {
            //对接的渠道是 guma_yhk 或者 guma_bzk 必传支付用户名
            if (in_array($channel, self::$bankPayCodes) && empty($request->param('body', ''))) {
                throw new ParameterException([
                    'msg' => 'Invalid Request When Payment Code in' . json_encode(self::$bankPayCodes) . 'And The Param body Can Not Be empty',
                    'errorCode' => 400003
                ]);
            }
        }


        $codeInfo = $this->logicPay->getCodeInfo(['code' => $channel, 'status' => 1], 'id');
        if (empty($codeInfo)) {
            throw new ParameterException([
                'msg' => 'Invalid Request.[ Payment Code Does Not Allowed.]' . $channel,
                'errorCode' => 400003
            ]);
        }

        $userPaycode = $this->logicUser->userPayCode(['uid' => $request->param('mchid'), 'co_id' => $codeInfo['id']]);
        if (empty($userPaycode) || $userPaycode['status'] == 0) {
            throw new ParameterException([
                'msg' => 'Invalid Request.[ 该渠道没有开通,请联系管理员开通渠道.]',
                'errorCode' => 400003
            ]);
        }

//        if (empty(self::get('payload')['channel']) ?: !in_array(self::get('payload')['channel'],$appCodeMap)) {
//            throw new ParameterException([
//                'msg'=>'Invalid Request.[ Payment Code Does Not Allowed.]',
//                'errorCode'=> 400003
//            ]);
//        }

    }
}
