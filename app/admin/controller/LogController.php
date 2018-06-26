<?php


namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;


class LogController extends AdminbaseController {
    
    
    private $dir;
    
    public function _initialize() {
        parent::_initialize();
        $this->dir=getcwd().'/log/';
       
        
    }
    
    /**
     * 日志管理
     * @adminMenu(
     *     'name'   => '日志管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 100,
     *     'icon'   => '',
     *     'remark' => '日志管理',
     *     'param'  => ''
     * )
     */ 
    public function index(){
        
         
       $list=[
           ['name'=>'每日任务日志','file'=>'time.log'],
           ['name'=>'微信日志','file'=>'wx.log'],
           ['name'=>'数据库日志','file'=>'zz.log'],
           ['name'=>'日志操作','file'=>'log.log']
       ];
       $this->assign('list',$list);
       return $this->fetch();
        
    }
    
    
    /**
     * 日志清空
     * @adminMenu(
     *     'name'   => '日志清空',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '日志清空',
     *     'param'  => ''
     * )
     */
    public function clear(){
        $file=$this->request->param('id','');
        if($file=='log.log'){
            $this->error('日志操作不能清空');
        }
        $path=($this->dir).$file;
        $name=session('name');
       //写入文件为空字符即为删除
        if(file_put_contents($path,'')===0){
            zz_log($name.'已清空日志', $file);
            zz_log($name.'已清空日志'.$file, 'log.log');
           
            $this->success('该日志已清空');
        }else{
            $this->error('操作失败');
        } 
         
    }
    /**
     * 日志下载
     * @adminMenu(
     *     'name'   => '日志下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '日志下载',
     *     'param'  => ''
     * )
     */
    public function download(){
        $filename=$this->request->param('id',''); 
        $file=($this->dir).$filename; 
        
        $info=pathinfo($file);
        $ext=$info['extension'];
        $name=$info['basename'];
        header('Content-type: application/x-'.$ext);
        header('content-disposition:attachment;filename='.$name);
        header('content-length:'.filesize($file));
        readfile($file);
        exit;
    }
    
}

?>