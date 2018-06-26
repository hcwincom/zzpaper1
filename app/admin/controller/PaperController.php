<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
/**
 * Class PaperController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'借条管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'',
 *     'remark' =>'借条管理'
 * )
 *
 */
class PaperController extends AdminBaseController
{
    private $m;
    private $order;
    private $paper_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('paper');
        $this->order='id desc';
        $this->paper_status=config('paper_status');
        $this->assign('flag','未完成借条');
        
        $this->assign('paper_status', $this->paper_status);
    }
     
    /**
     * 未完成借条列表
     * @adminMenu(
     *     'name'   => '未完成借条列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '未完成借条列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(isset($data['status']) &&  $data['status']!='-1'){
            $where['status']=$data['status'];
        }else{
            $data['status']='-1';
        }
        
        if(empty($data['borrower_idcard'])){
            $data['borrower_idcard']='';
        }else{
            $where['borrower_idcard']=$data['borrower_idcard'];
        }
        if(empty($data['lender_idcard'])){
            $data['lender_idcard']='';
        }else{
            $where['lender_idcard']=$data['lender_idcard'];
        }
        
        $list= $m->where($where)->order($this->order)->paginate(10);
       
        // 获取分页显示
        $page = $list->appends($data)->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        $this->assign('paper_status', config('paper_status'));
        return $this->fetch();
    }
    /**
     * 未完成借条查看
     * @adminMenu(
     *     'name'   => '未完成借条查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '未完成借条查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此借条不存在');
        }
        $this->assign('info',$info); 
        return $this->fetch();
    }
    /**
     * 未完成借条编辑执行
     * @adminMenu(
     *     'name'   => '未完成借条编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '未完成借条编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $m=$this->m;
        $m_user=Db::name('user');
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('借条不存在，请刷新页面');
        }
        
        $statuss=$this->paper_status;
        $data['update_time']=time();
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'paper',
            'time'=>$data['update_time'],
            'ip'=>get_client_ip(),
            'action'=>'对借条'.$data['id'].'更改状态"'.$statuss[$info['status']].'"为"'.$statuss[$data['status']].'"',
            
        ];
       
        //如果是确认还款结束的就进入借条仓库，不同意借条就24小时后删除
        Db::startTrans();
        try {
            switch($data['status']){
                case '6':
                    $m->where($where)->delete();
                    unset($info['id']);
                    unset($info['status']);
                    unset($info['expire_day']);
                    $info['update_time']=$data['update_time'];
                    //管理员点击已还款则计算逾期还款
                    $info['final_money']=zz_get_money_overdue($info['real_money'],$info['money'],config('rate_overdue'),$info['overdue_day']);
                     
                    Db::name('paper_old')->insert($info);
                   
                     
                    //确认还款后更新用户信息
                    $user1=$m_user->where('id',$info['borrower_id'])->find();
                    $user2=$m_user->where('id',$info['lender_id'])->find();
                    $data_user1=['back'=>bcsub($user1['back'],$info['money'],2)];
                    $data_user2=['send'=>bcsub($user2['send'],$info['money'],2)];
                    //计算收益
                    $rates=bcsub($info['final_money'],$info['money'],2);
                    $data_user2['money']=bcadd($user2['money'],$rates,2);
                    $data_user1['money']=bcsub($user1['money'],$rates,2);
                    $m_user->where('id',$user1['id'])->update($data_user1);
                    $m_user->where('id',$user2['id'])->update($data_user2);
                    break;
               
                default :throw new \Exception('目前只能修改为已还款');break;
           } 
           Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('保存失败！'.$e->getMessage());
        }
       
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
         
    }
    
}
