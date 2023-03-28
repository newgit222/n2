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
use app\common\library\exception\SignatureException;
use think\Log;
use think\Request;

/**
 * 检验网关签名
 * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
 */
class CheckSign extends ApiCheck
{
    /**
     * 签名校验
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param Request $request
     *
     * @return mixed|void
     * @throws SignatureException
     * @throws \app\common\library\exception\ParameterException
     */
    public function doCheck(Request $request)
    {
        $data = $request->post();
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $where = array();
        $where['uid'] = $data['mchid'];
        $appKey = $this->logicApi->getApiInfo($where, "*");
        if(!$appKey){
            throw new SignatureException([
                'msg' => '商户不存在',
                'errorCode' => 400003,
            ]);
        }
        $md5key = $appKey->key;
        $signData = "";
        foreach ($data as $key=>$value)
        {
            $signData = $signData.$key."=".$value;
            $signData = $signData . "&";
        }

        $signData = $signData."key=".$md5key;

        Log::notice('得到请求的基础数据 http_build_query后:'.$signData);
        $signData = md5($signData);
        Log::notice('用户post里面的sign'.$sign.'　对比md5基础数据得到的sign:'.$signData);
        if($signData != $sign){
            //todo  后面解开  直接打到根目录后面好查看
            $log =  "【商户id:{$data['mchid']}请求支付签名错误提交签名".$sign."系统计算签名".$signData."】";
          //  file_put_contents('./error_sign.log',$log,FILE_APPEND);
            if($appKey->is_verify_sign) {
                throw new SignatureException([
                    'msg' => 'sign 验证失败',
                    'errorCode' => 400003,
                ]);
            }
        }
    }
}
