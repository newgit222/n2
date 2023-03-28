<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2019/12/10
 * Time: 21:38
 */

namespace app\ms\Logic;


class Base extends \app\common\logic\BaseLogic
{

    public function error($msg,$code=0,$data=[]){
        return ['msg'=>$msg,'code'=>$code,'data'=>$data];
    }

    public function success($msg,$code=1,$data=[]){
        return ['msg'=>$msg,'code'=>$code,'data'=>$data];
    }

    /**
     * 简易验证器
     */
    public function validate($param,$rule){
        if($rule){
            foreach ($rule as $k => $v){
                if(!isset($param[$k])){
                    return $this->error($param[$v]);
                }
                if(is_null($param[$k])){
                    return $this->error($param[$v]);
                }
            }
        }
        return $this->success('请求成功');
    }

}