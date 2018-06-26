<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class ReplyController extends AdminBaseController
{
    private $m;
   
    private $reply_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('reply');
        
        $this->reply_status=config('reply_status');
        $this->assign('flag','借条申请');
        
        $this->assign('reply_types', config('reply_types'));
        $this->assign('reply_status', $this->reply_status);
    }
     
    /**
     * 借条申请列表
     * @adminMenu(
     *     'name'   => '借条申请列表',
     *     'parent' => 'admin/Paper/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '借条申请列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(isset($data['status']) &&  $data['status']!='-1'){
            $where['r.status']=$data['status'];
        }else{
            $data['status']='-1'; 
        }
        if(empty($data['type']) ){
            $data['type']='';
        }else{
            $where['r.type']=$data['type']; 
        }
        if(empty($data['borrower_idcard'])){
            $data['borrower_idcard']='';
        }else{
            $where['p.borrower_idcard']=$data['borrower_idcard'];
        }
        if(empty($data['lender_idcard'])){
            $data['lender_idcard']='';
        }else{
            $where['p.lender_idcard']=$data['lender_idcard'];
        }
        
        
        $list= $m
        ->alias('r')
        ->field('r.*,p.borrower_name,p.borrower_idcard,p.lender_name,p.lender_idcard')
        ->join('paper p','p.oid=r.oid')
        ->where($where)
        ->order('r.id desc')
        ->paginate(10);
       
        // 获取分页显示
        $page = $list->appends($data)->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 借条申请查看
     * @adminMenu(
     *     'name'   => '借条申请查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '借条申请查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此申请不存在或已失效');
        }
        $info1= Db::name('paper')->where('oid',$info['oid'])->find();
         
        if(empty($info1)){
            $this->error('此申请关联的借条已完成或已失效');
        }
       
        $this->assign('info1',$info1); 
        $this->assign('info',$info); 
        $this->assign('paper_status', config('paper_status'));
        return $this->fetch();
    }
    /**
     * 借条申请编辑执行
     * @adminMenu(
     *     'name'   => '借条申请编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '借条申请编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $this->error('暂时不能修改申请');
         
    }
    
}
