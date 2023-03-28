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

namespace app\common\service\command;


use app\common\logic\EwmOrder;
use app\common\logic\Queue;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;

/**
 * 关闭码商订单
 * Class CloseMsEwmOrder
 * @package app\common\service\command
 */
class CloseMsEwmOrder extends Command
{

    protected function configure()
    {
        $this->setName('closeMsEwmOrder')->setDescription('Here is the task of the remark ');
    }


    /**
     * 一个excute  最好只执行一个logic 否者可能会造成阻塞
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        try{
            $controller  = new EwmOrder();
            $controller->closeOrder();
            $output->writeln('['.date('Y-m-d H:i:s').']close the ewm_order command  execute successed');
        }catch(\Exception $e)
        {
            $output->writeln('close the order command  execute error['.$e->getMessage().']');
        }
    }

}