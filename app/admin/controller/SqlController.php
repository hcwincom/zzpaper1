<?php


namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;


class SqlController extends AdminbaseController {
    
    
    private $dir;
    private $line;
    public function _initialize() {
        parent::_initialize();
        $this->dir=getcwd().'/data/';
        $this->line="\r\n";
        
    }
    
    /**
     * 数据库操作
     * @adminMenu(
     *     'name'   => '数据库操作',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 100,
     *     'icon'   => '',
     *     'remark' => '数据库操作',
     *     'param'  => ''
     * )
     */ 
    public function index(){
        
        $dir=$this->dir;
        $files=scandir($dir);
        $list=[];
        foreach($files as $v){ 
            if(is_file($dir.$v) && substr($v,strrpos($v, '.'))=='.sqlsql'){ 
                $list[]=$v;
            } 
        }
        if($list){
            rsort($list);
        }
       
        $this->assign('list',$list);
       return $this->fetch();
       
        
    }
    
    /**
     * 数据库备份
     * @adminMenu(
     *     'name'   => '数据库备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库备份',
     *     'param'  => ''
     * )
     */
    public function add(){
        
        //设置超时时间为0，表示一直执行。当php在safe mode模式下无效，此时可能会导致导入超时，此时需要分段导入
        set_time_limit(0);
        $db=config('database');
        $dname=$db['database'];
        $dir=$this->dir; 
       
        import('SqlBack',EXTEND_PATH);
        $msqlback=new \SqlBack($db['hostname'], $db['username'], $db['password'], $dname,  $db['hostport'],$db['charset'],$dir);
        $url=url('index');
        if($msqlback->backup()){
            zz_log('管理员'.session('name').'备份了数据库','zz.log');
            $this->success('数据备份成功',$url);
        }else{
            echo "备份失败! <a href='.$url.'>返回</a>";
        }
        exit();
        
    }
    
    
    /**
     * 数据库还原
     * @adminMenu(
     *     'name'   => '数据库还原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库还原',
     *     'param'  => ''
     * )
     */
    public function restore()
    {
        $filename=$this->request->param('id','');
        set_time_limit(0);
        $db=config('database');
        $dname=$db['database'];
        $dir=$this->dir;
        $filename=$dir.$filename;
        if(file_exists($filename)){
            import('SqlBack',EXTEND_PATH);
            $msqlback=new \SqlBack($db['hostname'], $db['username'], $db['password'], $dname,  $db['hostport'],$db['charset'],$dir);
            $url=url('index');
            
             if($msqlback->restore($filename)){
                 zz_log('管理员'.session('name').'还原了数据库'.$filename,'zz.log');
                 $this->success('数据还原成功',$url);
            }else{
                echo "还原失败! <a href='.$url.'>返回</a>";
            }
        }else{
            echo "文件不存在! <a href='.$url.'>返回</a>";
        }
        exit;
        
    }
    /**
     * 数据库删除备份
     * @adminMenu(
     *     'name'   => '数据库删除备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库删除备份',
     *     'param'  => ''
     * )
     */
    public function del(){
        $file=$this->request->param('id','');
        if(unlink(($this->dir).$file)===true){
             zz_log('管理员'.session('name').'删除了备份数据库'.$file,'zz.log');
            $this->success('备份已删除');
        }else{
            $this->error('删除失败');
        }
        
    }
    /**
     * 数据库批量删除备份
     * @adminMenu(
     *     'name'   => '数据库批量删除备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库批量删除备份',
     *     'param'  => ''
     * )
     */
    public function dels(){
      
        $date=$this->request->param(); 
        $dir=$this->dir;
        foreach($date['ids'] as $file){
            if(unlink($dir.$file)===false){
                $this->error('删除失败');
            }
        }
        zz_log('管理员'.session('name').'批量删除了数据库','zz.log');
         
        $this->success('备份已删除');
         
    }
    
    /**
     * 数据库查询
     * @adminMenu(
     *     'name'   => ' 数据库查询',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => ' 数据库查询',
     *     'param'  => ''
     * )
     */
    public function query(){
        $data=$this->request->param();
        if(empty($data['type'])){
            $data['type']=0;
        }
       if(empty($data['sql'])){
           $data['sql']='';
       }else{
           
           try {
               if($data['type']==0){
                   $list=Db::query($data['sql']);
                   $row=count($list);
                   $this->assign('list',$list);
               }else{
                   $row=Db::execute($data['sql']);
               }
           } catch (\Exception $e) {
               $msg=$e->getMessage();
               $this->assign('msg',$msg);
           }
           if(empty($row)){
               $row=0;
           }
           $this->assign('row',$row);
           zz_log('管理员'.session('name').'使用了Sql语句'.($this->line).$data['sql'],'zz.log');
           
       }
        $this->assign('data',$data);
         
        return $this->fetch();
        
    }
     
    
}

?>