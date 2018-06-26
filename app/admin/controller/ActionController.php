<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class ActionController extends AdminBaseController
{
    private $m;
    private $order;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('action');
        $this->order='time desc';
       
        $this->assign('flag','管理员操作记录');
        
        $this->assign('types', config('action_types'));
    }
     
    /**
     * 管理员操作记录
     * @adminMenu(
     *     'name'   => '管理员操作记录',
     *     'parent' => 'admin/User/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '管理员操作记录',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(empty($data['type'])){
            $data['type']='';
        }else{
            $where['a.type']=$data['type'];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['a.aid']=$data['aid'];
        }
        $list= $m
        ->alias('a')
        ->field('a.*,u.user_login as uname0,u.user_nickname as uname1')
        ->join('cmf_user u','u.id = a.aid','left')
        ->where($where)
        ->order($this->order)
        ->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
        $admins=Db::name('user')->where('user_type',1)->select();
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        $this->assign('admins',$admins); 
        return $this->fetch();
    }
    /**
     * 清空系统任务
     * @adminMenu(
     *     'name'   => '清空系统任务',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '清空系统任务',
     *     'param'  => ''
     * )
     */
    public function clear()
    {
        $m=$this->m;
        $m->where('type','system')->delete();
        $this->success('已清空');
    }
    
}
