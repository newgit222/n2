<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/23
 * Time: 20:41
 */

namespace app\common\service\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;

class EverydayBalance extends Command
{
    /**
     * 配置定时器的信息
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     */
    protected function configure()
    {
        $this->setName('EverydayBalance')->setDescription('EverydayBalance at 00:00:00');
    }

    /**
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {


        // 输出到日志文件
        $output->writeln("EverydayBalance data  start");
        // 定时器需要执行的内容

        try{
            $time = time();
            //记录每天的商户余额添加到cm_balance_everyday
            $balance = db("balance")->where(['status'=>'1'])->select();
            $install = [];
            foreach ($balance as $k => $v){
                $install[] = [
                    'uid'   =>  $v['uid'],
                    'enable'   =>  $v['enable'],
                    'disable'   =>  $v['disable'],
                    'createtime'   =>  $time,
                ];
            }

            db("balance_everyday")->insertAll($install);
        }catch (\Exception $e){
            $output->writeln($e->getMessage());
            Log::error("EverydayBalance data Fail:[".$e->getMessage()."]");
        }
        // .....
        $output->writeln("EverydayBalance data  end....");
    }
}