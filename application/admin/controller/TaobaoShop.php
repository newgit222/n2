<?php
namespace app\admin\controller;
use app\common\library\enum\CodeEnum;
class TaobaoShop extends BaseAdmin
{
    public function index(){
        return $this->fetch();
    }

    public function edit(){
        // post 是提交数据
        $this->request->isPost() && $this->result($this->logicUserShop->saveUserShop($this->request->post()));
       $this->assign('shop',$this->logicUserShop->getShopInfo(['id' =>$this->request->param('id')]));
        return $this->fetch();
    }
	
	public function add()
	{
		// post 是提交数据
        $this->request->isPost() && $this->result($this->logicUserShop->saveUserShop($this->request->post()));
      
        return $this->fetch();
	}
	
	public function getList(){
        $where = [];
        //组合搜索
        !empty($this->request->param('uid')) && $where['uid']
            = ['eq', $this->request->param('uid')];

        $data = $this->logicUserShop->getShopList($where, '*', 'id desc', false);

        $count = $this->logicUserShop->getShopCount($where);

        $this->result($data || !empty($data) ?
            [
                'code' => CodeEnum::SUCCESS,
                'msg'=> '',
                'count'=>$count,
                'data'=>$data
            ] : [
                'code' => CodeEnum::ERROR,
                'msg'=> '暂无数据',
                'count'=>$count,
                'data'=>$data
            ]
        );
    }

    public function del(){
        // post 是提交数据
        $this->request->isPost() && $this->result(
            $this->logicUserShop->delShop(
                [
                    'id' => $this->request->param('id')
                ])
        );
        // get 直接报错
        $this->error([ CodeEnum::ERROR,'未知错误']);
    }
}