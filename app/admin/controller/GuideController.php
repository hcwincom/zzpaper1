<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class GuideController extends AdminBaseController
{
    private $m;
    private $order;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('guide');
        $this->order='type desc,sort asc,id asc';
       
        $this->assign('flag','协议手册');
        
        $this->assign('types', config('guide_types'));
    }
     
    /**
     * 协议手册管理
     * @adminMenu(
     *     'name'   => '协议手册管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '协议手册管理',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        
        $data=$this->request->param();
        
        $list= $m->order($this->order)->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
           $this->assign('page',$page);
        $this->assign('list',$list); 
      
        return $this->fetch();
    }
    
    /**
     * 协议手册编辑
     * @adminMenu(
     *     'name'   => '协议手册编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '协议手册编辑',
     *     'param'  => ''
     * )
     */
    function edit(){
        $m=$this->m;
        $id=$this->request->param('id');
        $info=$m->where('id',$id)->find();
        
        $this->assign('info',$info);
         
        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * 协议手册编辑1
     * @adminMenu(
     *     'name'   => '协议手册编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '协议手册编辑1',
     *     'param'  => ''
     * )
     */
    function editPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
       
        $data['content']=empty($_POST['content'])?'':$_POST['content'];
        $data['time']=time();
        $row=$m->where('id', $data['id'])->update($data);
        if($row===1){
            $this->success('修改成功',url('index'));
        }else{
            $this->error('修改失败');
        }
        
    }
    /**
     * 协议手册删除
     * @adminMenu(
     *     'name'   => '协议手册删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '协议手册删除',
     *     'param'  => ''
     * )
     */
    function delete(){
        $m=$this->m;
        $id = $this->request->param('id', 0, 'intval');
        
        $row=$m->where(['id'=>$id,'type'=>0])->delete();
        if($row===1){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
    
    /**
     * 协议手册添加
     * @adminMenu(
     *     'name'   => '协议手册添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '协议手册添加',
     *     'param'  => ''
     * )
     */
    public function add(){
        
        return $this->fetch();
    }
    
    /**
     * 协议手册添加1
     * @adminMenu(
     *     'name'   => '协议手册添加1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '协议手册添加1',
     *     'param'  => ''
     * )
     */
    public function addPost(){
        
        $m=$this->m;
        $data= $this->request->param();
        //$data['content']=$_POST['content'];
        $data['time']=time();
        $data['insert_time']= $data['time'];
        $row=$m->insertGetId($data);
        if($row>=1){
            $this->success('已成功添加',url('index'));
        }else{
            $this->error('添加失败');
        }
        exit;
    }
    
}
